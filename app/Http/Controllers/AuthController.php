<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Mail;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
use App\Mail\AccountActivationMail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    /**
     * used for handling login 
     * When user success login will retrive callback as api_token

    */

    public function register(Request $request){
        // validate fields
        $this->validate($request, [
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',   
            'name'  =>   'required',
        ]);
        // password hash
        $password   =   Hash::make($request->input('password'), [
            'rounds'    =>  12,
        ]);
        $email  =   $request->input('email');
        $name   =   trim($request->input('name'));
        // user create in Table
        $user =User::create([
            'name'  =>  $name,
            'email' =>  $email,
            'password'  =>  $password,
            'email_verification_token' => Str::random(32),
        ]);
        // Mail for account verification
        Mail::to($email)->send(new AccountActivationMail($user));



        $res['success'] =   true;
        $res['message'] =   "Successfully Registered, Please check your email to activate your account";
        return response()->json($res, Response::HTTP_OK);
        
    }



    public function login(Request $request){
        // validate incoming request parameters
        $this->validate($request, [
            'email' => 'required',
            'password' => 'required|min:6',   
        ]);


        $email  =   $request['email'];
        $password   =   $request->input('password');
        // users exists or not with such email
        $user  =   User::where('email', $email)->first();
        if(! $user){
            $res['success'] =   false;
            $res['message'] =   'Account with this Email id does not exits.';
            return  response($res);
        }
        else{
            // is Account verified or not ?
            if($user->email_verified){
                // password is correct or not
                if(Hash::check($password,$user->password)){
                    // this api_token generated here will be used for checking request are from loggined user or not
                    $api_token = sha1(time());
                    User::where('email', $email)->update(['api_token' => $api_token,'last_login' => Carbon::now()]);;
                    return response()->json(['status' => 'success','api_token' => $api_token]);
                    return redirect()->route('Profile');
                    
                }
                // password does not change case
                else{
                    $res['success'] =   false;
                    $res['message'] =   'You entered incorrect password please Try again!';
                    return  response($res);    
                }
    
            }
            // Account exist but account is not activated yet because its not verified yet
            else{
                $res['success'] =   false;
                $res['message'] =   'Please check your mail box and activate your account!';
                return  response($res);
    
            }
        }

    }


    public function request_account_activation_mail(Request $request){
        // validate incoming request parameters
        $this->validate($request, [
            'email' => 'required|email|exists:users,email',
        ]);
        $email  =   $request['email'];
        $user  =   User::where('email', $email)->first();
        // check account is already verified or not . If already verified send messsage for login
        if($user->email_verified){
            $res['success'] =   true;
            $res['message'] =   "Your account with this email is already verified. Please Login";
            return response()->json($res, Response::HTTP_OK); 
        }
        // account is not verified 
        $user->email_verification_token =Str::random(32);
        $user->save();
        Mail::to($email)->send(new AccountActivationMail($user));

        $res['success'] =   true;
        $res['message'] =   "Please check your email to activate your account";
        return response()->json($res, Response::HTTP_OK); 

    }


    public function logout(){
        $user = Auth::user();
        $user->api_token =sha1(time());
		$user->save();
		return [
			'status' => 'success',
			'message' => 'Logout successfully.'
		];

    }


}
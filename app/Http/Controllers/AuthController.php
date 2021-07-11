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
        $this->validate($request, [
            'email' => 'required|email|unique:users',
            'password'  =>  'required',
            'name'  =>   'required',
        ]);

        $password   =   Hash::make($request->input('password'), [
            'rounds'    =>  12,
        ]);
        $email  =   strtolower($request->input('email'));
        $name   =   $request->input('name');

        $user =User::create([
            'name'  =>  $name,
            'email' =>  $email,
            'password'  =>  $password,
            'email_verification_token' => Str::random(32),
        ]);

        Mail::to($email)->send(new AccountActivationMail($user));



        $res['success'] =   true;
        $res['message'] =   "Successfully Registered, Please check your email to activate your account";
        $res['data']    =   $user;
        return response()->json($res, Response::HTTP_OK);
        
    }



    public function login(Request $request){
        $this->validate($request, [
            'email' => 'required',
            'password' => 'required|min:6',   
        ]);


        $email  =   $request['email'];
        $password   =   $request->input('password');

        $user  =   User::where('email', $email)->first();
        if(! $user){
            $res['success'] =   false;
            $res['message'] =   'Your email or password incorrect, User is NUll';
            $res['data']    = $user;
            return  response($res);
        }
        else{
            if($user->email_verified){
                if(Hash::check($password,$user->password)){
                    // $api_token  =   sha1(time());
                    // $create_token   =   User::where('id', $login->id)->update(['api_token' => $api_token]);
                    
                    $user->last_login = Carbon::now();
                    $api_token = sha1(time());
                    User::where('email', $email)->update(['api_token' => $api_token]);;
                    
                    return response()->json(['status' => 'success','api_token' => $api_token]);
                    // return redirect()->route('Profile');
                    
                }
                else{
                    $res['success'] =   false;
                    $res['message'] =   'Your email or password incorrect!';
                    return  response($res);    
                }
    
            }
            else{
                $res['success'] =   false;
                $res['message'] =   'Please check your mail box and activate your account!';
                return  response($res);
    
            }
        }

    }

    public function verify() {

		return [
			'status' => 'success',
			'message' => 'Token is verified.'
		];
	}


}
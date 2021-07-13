<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Mail;
use Firebase\JWT\JWT;
use Firebase\JWT\ExpiredException;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
use App\Mail\AccountActivationMail;
use App\Mail\ForgetPasswordMail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    /**
     * used for handling login 
     * When user success login will retrive callback as api_token

    */

    private $request;

    public function __construct(Request $request) {
        $this->request = $request;
    }

    /**
     * Create a new token.
     * 
     * @param  \App\User   $user
     * @return string
     */
    protected function jwt(User $user) {
        $payload = [
            'iss' => "Collabit-jwt", // Issuer of the token
            'sub' => $user->id, // Subject of the token
            'iat' => time(), // Time when JWT was issued. 
            'exp' => time() + 60*60 // Expiration time
        ];

        // As you can see we are passing `JWT_SECRET` as the second parameter that will 
        // be used to decode the token in the future.
        return JWT::encode($payload, env('JWT_SECRET'));
    } 



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
                    User::where('email', $email)->update(['last_login' => Carbon::now()]);;
                    return response()->json(['status' => 'success','token' => $this->jwt($user)]);
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
                $res['success'] =   true;
                $res['message'] =   'Please check your mail box and activate your account!';
                return  response($res);
    
            }
        }

    }


    public function requestAccountActivationMail(Request $request){
        // validate incoming request parameters
        $this->validate($request, [
            'email' => 'required|email|exists:users,email',
        ]);
        $email  =   $request['email'];
        $user  =   User::where('email', $email)->first();

        if(! $user){
            $res['success'] =   false;
            $res['message'] =   "Your account with this email does not  exist. Please SignUp";
            return response()->json($res, Response::HTTP_OK); 
        }
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
		return [
			'status' => 'success',
			'message' => 'Logout successfully.'
		];

    }

    public function forgetPassword(Request $request){

        // validate incoming request parameters
        $this->validate($request, [
            'email' => 'required|email|exists:users,email',
        ]);
        $email  =   $request['email'];
        $user  =   User::where('email', $email)->first();
        if(! $user){
            $res['success'] =   false;
            $res['message'] =   'Account with this Email id does not exits.';
            return  response($res);
        }
        $user->api_token =sha1(time());
		$user->save();

        Mail::to($email)->send(new ForgetPasswordMail($user));

        $res['success'] =   true;
        $res['message'] =   "We have sent you a mail on registered email id , Please check your email to change your account password";
        return response()->json($res, Response::HTTP_OK);

    }

    public function forgetPasswordChange(Request $request){
        // field validation
        $this->validate($request, [
            'password' => 'required|confirmed|min:6'
        ]);
        $token  =   $request['token'];
        $password   =   $request->input('password');
        // token exists or not in url
        if(! $token){
            $res['success'] =   false;
            $res['message'] =   'token is NULL, Please check your mail for valid password change url';
            return  response($res);
        }
        else{
            $user  =   User::where('api_token', $token)->first();
            if(! $user){
                $res['status'] =false;
                $res['message'] ="Invalid url. Please check your Mail Inbox and click on Change Password button";
                return  response($res);
            }
            else{
                $password   =   Hash::make($request->input('password'), [
                    'rounds'    =>  12,
                ]);
                $user->password =$password;
                $user->save();
                $res['status'] =true;
                $res['message'] ="Your password has been updated .please login with updated credentials";
                return  response($res);

            }
        }


    }

}
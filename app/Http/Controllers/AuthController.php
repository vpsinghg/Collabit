<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Firebase\JWT\JWT;
use Firebase\JWT\ExpiredException;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
use App\Mail\AccountActivationMail;
use App\Mail\ForgetPasswordMail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;


// import Model Classes 
use App\Models\User;
use App\Models\EmailVerificationtokens;
use App\Models\Passwordtokens;

// multilevel query builder class
use Illuminate\Database\Eloquent\Builder;

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

        // create User Model class object
        $user   =   new  User;

        // filling User table fields as Class attributes
        $user->name     =   $name;
        $user->email    =   $email;
        $user->password =   $password;
        $user->save();

        // create EmailVerificationtokens Model class object
        $emailVerificationtoken =   new EmailVerificationtokens;
        $emailVerificationtoken->verificationCode =   Str::random(32);
        
        $user->emailVerificationToken()->save($emailVerificationtoken);


        // create Passwordtokens Model class object
        $passwordtoken  =   new Passwordtokens;
        $passwordtoken->verificationCode    =   Str::random(32);
        
        $user->passwordToken()->save($passwordtoken);
        // Mail for account verification
        $mailData =['name'=>$user->name,'email_verification_token'=>$emailVerificationtoken->verificationCode];
        Mail::to($email)->send(new AccountActivationMail($mailData));


        $res['success'] =   true;
        $res['message'] =   "Successfully Registered, Please check your email to activate your account";
        return response()->json($res, Response::HTTP_OK);
        
    }



    public function login(Request $request){
        // validate incoming request parameters
        $this->validate($request, [
            'email' => 'required|email|exists:users,email',
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
            if($user->isVerified){
                // password is correct or not
                if(Hash::check($password,$user->password)){
                    // this api_token generated here will be used for checking request are from loggined user or not
                    $passwordtoken =$user->passwordToken()->first();
                    return $this->respondWithToken($this->jwt($user),$user->only(['id','name','role']),$passwordtoken->verificationCode);
                    
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
        if($user->isVerified){
            $res['success'] =   true;
            $res['message'] =   "Your account with this email is already verified. Please Login";
            return response()->json($res, Response::HTTP_OK); 
        }
        // account is not verified 
        $verificationCode =   Str::random(32);
        // update verificationCode
        $user->emailVerificationToken()->update(['verificationCode' => $verificationCode]);
        $mailData =['name'  =>$user['name'],'email_verification_token'  =>$verificationCode];
        Mail::to($email)->send(new AccountActivationMail($mailData));

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
        $verificationCode =   Str::random(32);
        $user->passwordToken()->update(['verificationCode'=>$verificationCode]);
        $mailData   =   ['name'=>$user->name,'token'=>$verificationCode];
        Mail::to($email)->send(new ForgetPasswordMail($mailData));

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
                $user   =   User::whereHas('passwordToken',function  (Builder $query) use ($token){
                    $query->where('verificationCode',$token);
                })->first();
                if(! $user){
                $res['status'] =false;
                $res['message'] ="Invalid token,    Might be tampered. Please check your Mail Inbox and click on Change Password button";
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



    public function createPassword(Request $request){
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
                $user   =   User::whereHas('passwordToken',function  (Builder $query) use ($token){
                    $query->where('verificationCode',$token);
                })->first();
                if(! $user){
                $res['status'] =false;
                $res['message'] ="Invalid token, Might be tampered. Please check your Mail Inbox and click on Create Password button";
                return  response($res);
            }
            else{
                $password   =   Hash::make($request->input('password'), [
                    'rounds'    =>  12,
                ]);
                $user->password =   $password;
                $user->isVerified   =   1;
                $user->save();
                $res['status'] =true;
                $res['message'] ="Your password has been updated .please login with updated credentials";
                return  response($res);

            }
        }
        
    }
}
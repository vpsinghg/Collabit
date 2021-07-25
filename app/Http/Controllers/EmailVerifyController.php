<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use App\Models\User;

class EmailVerifyController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    //
    public function verifyEmail(Request $request)
    {
        $token =$request['email_verification_token'];
        // token is present or not
    	if($token == null) {
            $res['message'] =   'Invalid Login Attempt, Token is null';
            return response()->json($res, 401);

    	}
        // whereHas helps use build query for child table here emailVerificationToken is child table of User
        $user   =   User::whereHas('emailVerificationToken',function  (Builder $query) use ($token){
            $query->where('verificationCode',$token);
        })->first();
        // token tempered
        if($user == null ){
            $res['message'] =   'Invalid Verification token,    Token is tampered';
            return response()->json($res, 401);
        }
        // user is found with given token 
        else{
            // Account is already verified
            if($user->isVerified    ==  1){
                $res['message'] =   'Your account is already verified, You can login';      
                return response()->json($res,200);  
            }
            // verify account
            $user->isVerified =1;
            $user->save();
            $res['message'] =   'Your account is activated, you can log in now';
    
            return response()->json($res, Response::HTTP_OK);
    
        }
    }
}

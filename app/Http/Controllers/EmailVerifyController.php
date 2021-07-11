<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
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
    public function VerifyEmail($token = null)
    {
    	if($token == null) {

            $res['success'] =   false;
            $res['message'] =   'Invalid Login Attempt, Token is null';
            $res['data']    =   $user;

            return response()->json($res, Response::HTTP_OK);

    	}

       $user = User::where('email_verification_token',$token)->first();

       if($user == null ){


        $res['success'] =   false;
        $res['message'] =   'Invalid Login Attempt, Token Invalid User NULL';
        $res['data']    =   $user;

        return response()->json($res, Response::HTTP_OK);

       }

       $user->update([
        'email_verified' => 1,
        'email_verified_at' => Carbon::now(),
        'email_verification_token' => ''

       ]);
       
        $res['success'] =   true;
        $res['message'] =   'Your account is activated, you can log in now';
        $res['data']    =   $user;

        return response()->json($res, Response::HTTP_OK);

        // return redirect()->route('login');

    }
}

<?php

namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;
use App\Services\UserService;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * Handles user registration .
     *
     * @return void
     */
    public function __construct()
    {
        //

    }

    //
    /**
     * Get user by id
     *
     * URL /user/{id}
     */ 
    public function getUser(Request $request) {
        $id =$request['id'];
        $user = User::where('id', $id)->first();
        
        if($user){
            $res['message'] =   $user;
            return  response($res,200);
        }
        else{
            $res['message'] =   "Cannot find user with given id";

            return  response($res,404);

        }
    }

    public function listUsers(Request $request) {
        return[
            'success' =>true,
            'data' => User::All(),
        ];

    }


    public function changePassword(Request $request) {

        // validate incoming fields
        $this->validate($request, [
            'oldpassword' =>    'required|min:6',   
            'newpassword'  =>   'required|min:6|confirmed',
        ]);
        $logginedUser =Auth::user();
        $user = User::where('email', $logginedUser['email'])->first();
        // Email check if user exits or not with such email
        if (!$user) {
			return UserService::unauthorizedResponse();
		}

		// Old Password check
		if (!Hash::check($request['oldpassword'], $user->password)) {
			return UserService::unauthorizedResponse();
		}
        // new password hashing
        $newpassword   =   Hash::make($request->input('newpassword'), [
            'rounds'    =>  12,
        ]);
        // password update and save
        $user->password =   $newpassword;

        $user->api_token =sha1(time());
        $user->save();

        return [
			'message' => 'Password is updated successfully.'
		];


    }


	public function profile()
	{
        return[
            'data' => Auth::user()
        ];
	}


}

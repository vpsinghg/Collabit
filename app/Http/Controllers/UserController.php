<?php

namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;

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
    public function get_user(Request $request, $id) {
        $user   =   User::where('id',$id)->get();
        if($user){
            $res['success'] =   true;
            $res['message'] =   $user;
            return  response($res);
        }
        else{
            $res['success'] =   false;
            $res['message'] =   "Cannot find user with given id";

            return  response[$res];

        }
    }

	public function profile()
	{
        return[
            'data' => Auth::user()
        ];
	}

    public function verify() {

		return [
			'status' => 'success',
			'message' => 'Token is verified.'
		];
	}

}

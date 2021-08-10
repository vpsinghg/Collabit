<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// string
use Illuminate\Support\Str;

// use Mail
use Illuminate\Support\Facades\Mail;

//use DB
use Illuminate\Support\Facades\DB;

// use CreatePasswordMail Job
use App\Jobs\CreatePasswordJob;

// import Model Classes 
use App\Models\User;
use App\Models\EmailVerificationtokens;
use App\Models\Passwordtokens;

// multilevel query builder class
use Illuminate\Database\Eloquent\Builder;



class AdminController extends Controller
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

    

    // List users based on role filters
    public function showFilteredUsers(Request $request){
        $loggin_user =Auth::user();
        $role   =   $request['role'];
        $name   =   $request['name'];
        $email  =   $request['email'];
        $createdBy  =  (int)$request['createdBy'];
        $verified  =   $request['verified'];
        $users =    DB::table('users')->where('deleted_at', NULL)
            ->when($role, function ($query, $role) {
                if($role =='all'){
                    return $query;
                }
                return $query->where('role', $role);
            })->when($name, function($query,$name){
                return $query->where('name','LIKE','%'.$name    .'%');
            })->when($email,function($query,$email){
                return $query->where('email','LIKE','%'.$email.'%');
            })->when($createdBy,function($query,$createdBy){
                if($createdBy ==='all'){
                    return $query;
                }
                return $query->where('createdBy', $createdBy);
            })->when($verified,function($query,$verified){
                if($verified ==='verifiedusers'){
                    return $query->where('isVerified',1);
                }
                elseif ($verified ==="notverifiedusers") {
                    return $query->where('isVerified',0);
                }
                else{
                    return $query;
                }
            })
            ->paginate(5);
        // $res['users']   =   $users;
        return response()->json($users,200);
    }


    // create user
    public function createUser(Request $request){
        $loggin_user =Auth::user();

        $this->validate($request, [
            'email' => 'required|email|unique:users',
            'role' => 'required',   
            'name'  =>   'required',
        ]);

        $email  = $request['email'];
        $role   =$request['role'];
        $name   =$request['name'];

        $user =new User;
        $user->email =$email;
        $user->name =$name;
        $user->role =$role;
        $user->createdBy =$loggin_user->id;
        $user->save();
        
        // create EmailVerificationtokens Model class object
        $emailVerificationtoken =   new EmailVerificationtokens;
        $emailVerificationtoken->verificationCode =   Str::random(32);
        
        $user->emailVerificationToken()->save($emailVerificationtoken);


        // create Passwordtokens Model class object
        $passwordtoken  =   new Passwordtokens;
        $passwordtoken->verificationCode    =   Str::random(32);
        
        $user->passwordToken()->save($passwordtoken);
        
        $mailData =['email' => $user->email,'name'=>$user->name,'token'=>$passwordtoken->verificationCode];
        dispatch(new CreatePasswordJob($mailData));

        $res['message'] =   'User has been created and We have sent mail to create password';

        return response()->json($res,201);

    }

    public function deleteUser(Request $request){
        $userloggedIn   =   Auth::user();
        $toDeleteUserId   =   $request['user_id'];

        $toDeleteUser   =   User::find($toDeleteUserId);
        if(! $toDeleteUser){
            $res['message'] =   "User is unavaible for deletion";
            return response()->json($res,404);
        }
        if($toDeleteUser->role =='admin'){
            $res['message'] =   "You can't delete Admin User";
            return response()->json($res,403);

        }
        else{
            $toDeleteUser->deletedBy    =   $userloggedIn->id;
            $toDeleteUser->save();
            $toDeleteUser->delete();
            $res['message'] =   "User has be deleted";
            return response()->json($res,200);

        }

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

    public function getAdminUsers(Request $request) {
        $admins =   User::where('role','admin')
            ->select('id', 'name','email')
            ->get();
        return response()->json(['admins'=>$admins],200);
    }

}

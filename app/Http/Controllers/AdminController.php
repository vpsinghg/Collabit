<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// string
use Illuminate\Support\Str;

// use Mail
use Illuminate\Support\Facades\Mail;

use App\Mail\CreatePassword;

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
        $users  =   User::where('role','normal');
        
        if($request->has('name')){
            $users->where('name','LIKE','%'.$request['name']    .'%');
        }
        if($request->has('email')){
            $users->where('email','LIKE','%'.$request['email'].'%');
        }

        if($request->has('createdBy')){
            $users->where('createdBy',$request['createdBy']);
        }
        if($request->has('radio')){
            if($request->get('radio')=="verified"){
                $users->where('isVerified',1);
            }
            elseif ($request->get('radio')=='nonverified') {
                $users->where('isVerified',0);
            }
        }

        $res['status']  =   'success';
        $res['users']   =   $users->get();
        return response()->json($res,200);
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
        
        $mailData =['name'=>$user->name,'token'=>$passwordtoken->verificationCode];
        Mail::to($email)->send(new CreatePassword($mailData));

        $res['success'] =   true;
        $res['message'] =   'User has been created and We have sent mail to create password';

        return response()->json($res,200);

    }

    public function deleteUser(Request $request){
        $userloggedIn   =   Auth::user();
        $toDeleteUserId   =   $request['user_id'];
        $toDeleteUser   =   User::find($toDeleteUserId);
        if(! $toDeleteUser){
            $res['success'] =   false;
            $res['message'] =   "User is unavaible for deletion";
            return response()->json($res,200);
        }
        if($toDeleteUser->role =='admin'){
            $res['success'] =   false;
            $res['message'] =   "You can't delete Admin User";
            return response()->json($res,200);

        }
        else{
            $toDeleteUser->deletedBy    =   $userloggedIn->id;
            $toDeleteUser->save();
            $toDeleteUser->delete();
            $res['success'] =   true;
            $res['message'] =   "User has be deleted";
            return response()->json($res,200);

        }

    }

    public function showUsers(Request $request){
        $userloggedIn   =   Auth::user();
        $filteredUsers =   User::where('role',$request['role']);
        $res['success'] =   true;
        $res['message'] =   $filteredUsers;
        return response()->json($res,200);

    }


}

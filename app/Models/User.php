<?php

namespace App\Models;

/* Import Models which have relationship with User Model */
use App\Models\EmailVerificationtokens;
use App\Models\Passwordtokens;



use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;

// import Auth Contrats which we want to implement
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

// Eloquent ORM softdelete Trait import
use Illuminate\Database\Eloquent\SoftDeletes;


class User extends Model implements AuthenticatableContract,    AuthorizableContract
{
    // Traits we will use
    use Authenticatable,    Authorizable,   SoftDeletes;

    // EmailVerificationtokens relationship register with User class , using User()->emailVerificationToken() we can access emailverification token class or table
    // child Model of User used for child table creation
    public function emailVerificationToken(){
        return $this->hasOne(EmailVerificationtokens::class);
    }

    // Passwordtokens relationship register with User class  and using User()->emailVerificationToken() we can access emailverification token class or table
    // child Model of User, used for child table creation
    public function passwordToken(){
        return $this->hasOne(Passwordtokens::class);
    }

    // fields to be filled while creating User class Instance/ object
    protected $fillable =['email','role','createdBy'];

    protected $hidden =['password','VerificationCode'];


}

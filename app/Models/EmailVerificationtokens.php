<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\SoftDeletes;


class EmailVerificationtokens extends Model
{
    use SoftDeletes;

    // relationship with User model class
    public  function user(){
        return $this->belongsTo(User::class);
    }

    protected $table    =   'email_verificationtokens';

    protected $fillable =   ['user_id'];

    protected $hidden   =   ['verificationCode'];
}

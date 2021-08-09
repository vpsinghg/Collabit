<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

use Illuminate\Database\Eloquent\SoftDeletes;

class Passwordtokens extends Model
{
    //
    use SoftDeletes;

    // relationship with User Model class
    public function user(){
        return  $this->belongsTo(user::class);
    }
    protected $table    =   'passwordtokens';

    protected $fillable =   ['user_id'];

    protected $hidden   =   ['verificationCode'];
}

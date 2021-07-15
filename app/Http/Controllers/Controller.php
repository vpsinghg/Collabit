<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
class Controller extends BaseController
{
    //

    protected function respondWithToken($token,$user,$ftoken){
        return response()->json([
            'user'  =>  $user,
            'ftoken'    =>  $ftoken,
            'token '    =>  $token,
            'token_type'    =>  'bearer',
        ]);
    }
}

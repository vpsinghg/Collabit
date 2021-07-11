<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;
$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->get('/key', function() {
    return \Illuminate\Support\Str::random(32);
});



$router->group(['prefix' => 'api/'], function() use ($router) {
    // register route
    $router->post('auth/register','AuthController@register');
    // login route
    $router->post('auth/login', 'AuthController@login');
    // email verification route
    $router->get('auth/verify_email/{token}',[
        'as' => 'Verify', 'uses' => 'EmailVerifyController@VerifyEmail'
    ]);
    // request new token for account activation
    $router->get('auth/request_account_activation_mail',['as'    =>'request_account_activation_mail', 'uses' =>'AuthController@request_account_activation_mail']);
    // following route use auth middleware
    $router->group(['middleware' => 'auth'], function () use ($router) {
        // access user by id
        $router->get('user/{id}', ['uses' =>  'UserController@get_user']);
        // access user profile of loggedIn user
        $router->get('/profile', ['as' =>'profile','uses' =>'UserController@profile']);
        // logout 
        $router->get('/auth/logout',['as'=>'logout','uses' =>'AuthController@logout']);
        // password change
        $router->post('/auth/password_change',['as' =>  'PasswordChange','uses' =>'UserController@changePassword']);
    });

});




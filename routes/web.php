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
use App\Models\User;
$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->get('/key', function() {
    return \Illuminate\Support\Str::random(32);
});



$router->group(['prefix' => 'api/'], function() use ($router) {



    // register route
    $router->post('auth/register',['as' =>'Register', 'uses'    =>  'AuthController@register']);

    // login route
    $router->post('auth/login', 'AuthController@login');
    // email verification route
    $router->get('auth/verify_email/',[
        'as' => 'Verify', 'uses' => 'EmailVerifyController@verifyEmail'
    ]);
    // forget password 
    $router->post('auth/forget_password',['as'   =>  'forgetPassword',   'uses'  =>  'AuthController@forgetPassword']);
    $router->post('auth/forget_password_update/',['as'   =>  'forgetPasswordChange',   'uses'  =>  'AuthController@forgetPasswordChange']);

    // request new token for account activation
    $router->post('auth/request_account_activation_mail',['as'    =>'requestAccountActivationMail', 'uses' =>'AuthController@requestAccountActivationMail']);
    
    // create password
    $router->post('auth/create_password',['as' =>  'CreatePassword',   'uses'  =>  'AuthController@createPassword']);


    
    // following route use auth middleware
    $router->group(['middleware' => 'jwt.auth'], function () use ($router) {
        // access user profile of loggedIn user
        $router->get('/profile', ['as' =>'profile','uses' =>'UserController@profile']);
        // logout 
        $router->get('/auth/logout',['as'=>'logout','uses' =>'AuthController@logout']);
        // password change
        $router->post('/auth/password_change',['as' =>  'PasswordChange','uses' =>'UserController@changePassword']); 
        

        // Admin routes 
        $router->group(['prefix' => 'admin', 'middleware'   =>'adminControl'],function () use ($router){
            $router->delete('/users/delete_user',   ['as'   =>'adminDeleteUser', 'uses'  =>  'AdminController@deleteUser']);

            $router->post('/users/create_user',['as'   =>'AdminCreateUser',    'uses'  =>  'AdminController@createUser']);
            $router->get('/users', ['as'  =>  'adminListUsers', 'uses'  =>  'AdminController@showUsers']);
            $router->get('/users/filter/',['as' =>  'AdminFilteredUser',    'uses'=>    'AdminController@showFilteredUsers']);

            // list all users
            $router->get('users',['as'  =>  'ListUsers',    'uses'  =>  'UserController@listUsers']);
            // access user by id
            $router->get('user/', ['uses' =>  'UserController@getUser']);

        });

    });

});




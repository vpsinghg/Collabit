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
    $router->post('auth/register','AuthController@register');
    $router->post('auth/login', 'AuthController@login');
    $router->get('auth/verify', 'AuthController@verify');
    $router->get('auth/verify_email/{token}',[
        'as' => 'Verify', 'uses' => 'EmailVerifyController@VerifyEmail'
    ]);

    $router->group(['middleware' => 'auth'], function () use ($router) {

        $router->get('user/{id}', ['uses' =>  'UserController@get_user']);
        $router->get('/profile', ['as' =>'profile','uses' =>'UserController@profile']);
        $router->get('/users', function () use ($router) {
			return \App\User::all();
		});

    });

});




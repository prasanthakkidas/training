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

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group(['prefix' => 'training'], function () use ($router) {
    // Matches "/training/signup
    $router->post('signup', 'UserController@signUp');
    $router->post('login', 'AuthController@authenticate');
 
});

 $router->group(['prefix' => 'training/registered', 'middleware' => 'jwt.auth'],  function() use ($router) {

        $router->post('users', 'featureController@allUsers');
        $router->post('user/{id}', 'featureController@singleUser');
        $router->post('update/{id}', 'featureController@update');
        $router->post('delete/{id}', 'featureController@delete');
    }
);

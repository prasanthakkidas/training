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
    $router->post('signup', 'AuthController@signUp');
    $router->post('login', 'AuthController@authenticate');
    $router->get('verification', 'AuthController@verify');
    $router->post('forget-password', 'AuthController@forgetPassword');
    $router->post('reset-password','AuthController@resetPassword');
    $router->post('email-request','AuthController@emailRequest');
});

 $router->group(['prefix' => 'training/auth', 'middleware' => 'jwt.auth'],  function() use ($router) {
        $router->get('users', 'FeatureController@allUsers');
        $router->get('user', 'FeatureController@singleUser');
        $router->post('delete', 'FeatureController@delete');
        $router->post('new-user', 'FeatureController@createUser');
        $router->post('change-password', 'FeatureController@changePassword'); 
});

$router->group(['prefix' => 'training/auth', 'middleware' => 'jwt.auth'],  function() use ($router) {
    // $router->get('user', 'featureController@singleUser');
    $router->post('delete-task', 'TaskController@deleteTask');
    $router->post('create-task', 'TaskController@createTask');
    $router->post('update-task', 'TaskController@updateTask');
    $router->post('update-task-status', 'TaskController@updateTaskStatus');
    $router->get('user-tasks', 'TaskController@userTasks');
    $router->get('today-tasks', 'TaskController@todayTasks');
    $router->get('all-tasks', 'TaskController@allTasks');
});

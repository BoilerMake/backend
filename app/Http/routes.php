<?php

/*
|--------------------------------------------------------------------------
| Routes File
|--------------------------------------------------------------------------
|
| Here is where you will register all of the routes in an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| This route group applies the "web" middleware group to every route
| it contains. The "web" middleware group is defined in your HTTP
| kernel and includes session state, CSRF protection, and more.
|
*/

Route::group(['middleware' => ['web']], function () {
    //
});

/**
 * API ROUTES
 */
Route::group(['prefix' => 'v1','namespace'=>'API'], function()
{
    //splash page signups
    Route::get('test','GeneralController@test');
    Route::post('auth', 'AuthController@login');
    Route::post('users', 'AuthController@signUp');
    Route::get('debug', 'AuthController@debug');

    Route::group(array('prefix' => 'users/me'), function() {
        Route::get('/', 'UsersController@getMe');
        Route::put('/', 'UsersController@updateMe');
        Route::get('attributes', 'UsersController@getAttributes');
        Route::post('app', 'UsersController@application');
    });
});

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
    Route::get('schools', 'GeneralController@getSchools');
    Route::post('interest/signup','GeneralController@interestSignup');
    Route::get('interest','ExecController@getInterestData');
    Route::get('calendar', 'ExecController@generateCalendar');


    Route::post('users/reset/send','UsersController@sendPasswordReset');
    Route::post('users/reset/perform','UsersController@performPasswordReset');

    Route::post('pods/scan','PodController@scan');
    Route::get('pods/list','PodController@listPods');
    Route::get('pods/events','PodController@listEvents');
    Route::get('pods/scans','PodController@listScans');
    Route::post('pods/heartbeat','PodController@heartbeat');

    Route::get('events', 'GeneralController@getEvents');
    Route::put('analytics/event', 'AnalyticsController@event');


    Route::get('sponsor/info','SponsorController@info');
    Route::group(array('prefix' => 'users/me'), function() {
        Route::get('/', 'UsersController@getMe');
        Route::put('/', 'UsersController@updateMe');
        Route::put('leaveteam', 'UsersController@leaveCurrentTeam');
        Route::get('resumePUT','UsersController@getResumePutUrl');
        Route::get('application', 'UsersController@getApplication');
        Route::post('application', 'UsersController@updateApplication');
    });
    Route::group(array('prefix' => 'execs'), function() {
        Route::get('hackers', 'ExecController@getHackers');
        Route::post('hackers/bulk', 'ExecController@getHackersBulk');
        Route::put('hackers/bulk', 'ExecController@putHackersBulk');
        Route::get('users', 'ExecController@getUsers');
        Route::get('allstats', 'ExecController@getAllStats');
        Route::get('applications/next','ExecController@getNextApplicationID');
        Route::get('applications/{id}/view', 'ExecController@getApplication');
        Route::put('applications/{id}/rate', 'ExecController@rateApplication');
        Route::post('applications/{id}/notes', 'ExecController@addApplicationNote');

        Route::get('teams', 'ExecController@getTeams');

        Route::post('events/create', 'ExecController@createEvent');
        Route::post('events/update', 'ExecController@editEvent');
        Route::post('events/delete', 'ExecController@deleteEvent');
    });
});

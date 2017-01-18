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
Route::get('/fe', function () {
    return env('FRONTEND_ADDRESS');
});

/*
 * API ROUTES
 */
Route::group(['prefix' => 'v1', 'namespace'=>'API'], function () {
    //splash page signups
    Route::get('ping', 'GeneralController@ping');
    Route::post('auth', 'AuthController@login');
    Route::post('users', 'AuthController@signUp');
    Route::get('debug', 'AuthController@debug');
    Route::get('schools', 'GeneralController@getSchools');
    Route::post('interest/signup', 'GeneralController@interestSignup');
    Route::get('interest', 'ExecController@getInterestData')->middleware(['jwt.auth', 'role:exec']);;
    Route::get('calendar', 'ExecController@generateCalendar');
    Route::get('sponsor/info', 'SponsorController@info');

    // Analytics
    Route::get('events', 'GeneralController@getEvents');
    Route::put('analytics/event', 'AnalyticsController@event');

    Route::post('users/reset/send', 'UsersController@sendPasswordReset');
    Route::post('users/reset/perform', 'UsersController@performPasswordReset');
    Route::get('users/verify/{code?}', 'AuthController@confirm');

    Route::group(['prefix' => 'users/me'], function () {
        Route::get('/', 'UsersController@getMe');
        Route::put('/', 'UsersController@updateMe');
        Route::put('leaveteam', 'UsersController@leaveCurrentTeam');
        Route::get('resumePUT', 'UsersController@getResumePutUrl');
        Route::get('application', 'UsersController@getApplication');
        Route::post('application', 'UsersController@updateApplication');
        Route::post('puzzles', 'UsersController@completePuzzle');
        Route::get('puzzles', 'UsersController@getCompletedPuzzleIDs');
    });

    Route::group(['middleware' => ['jwt.auth', 'role:exec'], 'prefix' => 'execs'], function () {
        Route::get('hackers', 'ExecController@getHackers');
        Route::post('hackers/bulk', 'ExecController@getHackersBulk');
        Route::put('hackers/bulk', 'ExecController@putHackersBulk');
        Route::get('users', 'ExecController@getUsers');
        Route::get('users/{id}/view', 'ExecController@getUser');
        Route::get('users/{id}/analytics', 'ExecController@getUserAnalytics');
        Route::post('users/{id}/action', 'ExecController@doAction');
        Route::get('messaging/group', 'ExecController@getGroupMessages');
        Route::post('messaging/group', 'ExecController@sendGroupMessage');
        Route::get('allstats', 'ExecController@getAllStats');
        Route::get('applications/next', 'ExecController@getNextApplicationID');
        Route::get('applications/{id}/view', 'ExecController@getApplication');
        Route::put('applications/{id}/rate', 'ExecController@rateApplication');
        Route::post('applications/{id}/notes', 'ExecController@addApplicationNote');
        Route::get('teams', 'ExecController@getTeams');
        Route::post('events/create', 'ExecController@createEvent');
        Route::post('events/{event}/update', 'ExecController@editEvent');
        Route::post('events/{event}/delete', 'ExecController@deleteEvent');
    });

    Route::group(['prefix' => 'pods'], function () {
        Route::post('scan', 'PodController@scan');
        Route::get('list', 'PodController@listPods');
        Route::get('events', 'PodController@listEvents');
        Route::get('scans', 'PodController@listScans');
        Route::post('heartbeat', 'PodController@heartbeat');
    });
});

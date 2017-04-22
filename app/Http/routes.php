<?php
//route route
Route::any('/','API\GeneralController@info');
/**
 * PHPdocs
 */
Route::get('/docs', function () {
    return File::get(public_path() . '/docs/index.html');
});
/*
 * API ROUTES
 */
Route::group(['prefix' => 'v1', 'namespace'=>'API'], function () {
    //heartbeat
    Route::get('ping', 'GeneralController@ping');
    //signup form
    Route::get('schools', 'GeneralController@getSchools');
    Route::post('interest/signup', 'GeneralController@interestSignup');

    //why is this here
    Route::get('interest', 'ExecController@getInterestData')->middleware(['jwt.auth', 'role:exec']);
    Route::get('calendar', 'ExecController@generateCalendar');

    //day-of routes
    Route::get('events', 'GeneralController@getEvents');
    Route::get('announcements', 'GeneralController@getAnnouncements');
    Route::get('activity', 'GeneralController@getActivity');

    // Analytics todo: refactor
    Route::put('analytics/event', 'AnalyticsController@event');

    //auth
    Route::post('users/login', 'AuthController@login');
    Route::post('users/register', 'AuthController@register');

    //password reset + account confirmation
    Route::post('users/reset/send', 'AuthController@sendPasswordReset');
    Route::post('users/reset/perform', 'AuthController@performPasswordReset');
    Route::get('users/verify/{code?}', 'AuthController@confirmEmail');
    /**
     * User routes
     */
    Route::group(['middleware'=>['jwt.auth'],'prefix' => 'users/me'], function () {

        //get update me
        Route::get('/', 'UsersController@getMe');
        Route::put('/', 'UsersController@updateMe');
        //returns URL to PUT upload resume pdf to
        Route::get('resumePUT', 'UsersController@getResumePutUrl');
        //user application
        Route::get('application', 'UsersController@getApplication');
        Route::post('application', 'UsersController@updateApplication');
        //user puzzle sdtatus
        Route::post('puzzles', 'UsersController@completePuzzle');
        Route::get('puzzles', 'UsersController@getCompletedPuzzleIDs');
    });
    /**
     * Exec routes
     */
    Route::group(['middleware' => ['jwt.auth', 'role:exec'], 'prefix' => 'execs'], function () {

        //list hackers
        Route::get('hackers', 'ExecController@getHackers');
        //old routes?
        Route::post('hackers/bulk', 'ExecController@getHackersBulk');
        Route::put('hackers/bulk', 'ExecController@putHackersBulk');
        //get users list/overview by ID/analytics by ID/perform action by ID
        Route::get('users', 'ExecController@getUsers');
        Route::get('users/{id}/view', 'ExecController@getUser');
        Route::get('users/{id}/analytics', 'ExecController@getUserAnalytics');
        Route::post('users/{id}/action', 'ExecController@doAction');
        //application review
        Route::get('applications/next', 'ExecController@getNextApplicationID');
        Route::get('applications/{id}/view', 'ExecController@getApplication');
        Route::put('applications/{id}/rate', 'ExecController@rateApplication');
        Route::post('applications/{id}/notes', 'ExecController@addApplicationNote');
        //day-of annoucements
        Route::post('announcements/add', 'ExecController@addAnnouncement');
        //calendar events
        Route::post('events/create', 'ExecController@createEvent');
        Route::post('events/{event}/update', 'ExecController@editEvent');
        Route::post('events/{event}/delete', 'ExecController@deleteEvent');
    });

    /**
     * PODPOD
     */
    Route::group(['prefix' => 'pods'], function () {
        Route::post('scan', 'PodController@scan');
        Route::get('list', 'PodController@listPods');
        Route::get('events', 'PodController@listEvents');
        Route::get('scans', 'PodController@listScans');
        Route::post('heartbeat', 'PodController@heartbeat');
    });

    //fallthrough
    Route::get('/', function () {
        return ['ok'];
    });
});

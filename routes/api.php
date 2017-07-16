<?php


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
//route route
Route::any('/', 'GeneralController@info');
/*
 * PHPdocs
 */
Route::get('/docs', function () {
    return File::get(public_path().'/docs/index.html');
});
//used by sponsors.boilermake.org/packet/{secret}
Route::get('packet/{secret}', 'SponsorsController@packet');

/*
 * API ROUTES
 */
Route::prefix('v1')->group(function () {
    //heartbeat
    Route::get('ping', 'GeneralController@ping');
    Route::post('stats', 'GeneralController@recordStat');
    Route::any('slackapp', 'SlackController@index');

    //signup form
    Route::get('schools', 'GeneralController@getSchools');
    Route::post('interest/signup', 'GeneralController@interestSignup');

    //day-of routes
    Route::get('events', 'GeneralController@getEvents');
    Route::get('announcements', 'GeneralController@getAnnouncements');
    Route::get('activity', 'GeneralController@getActivity');

    //auth
    Route::post('users/login', 'AuthController@login');
    Route::post('users/register', 'AuthController@register');
    Route::post('users/auth/github/{code}', 'AuthController@githubAuth');

    //password reset + account confirmation
    Route::post('users/reset/send', 'AuthController@sendPasswordReset');
    Route::post('users/reset/perform', 'AuthController@performPasswordReset');
    Route::post('users/verify/{code?}', 'AuthController@confirmEmail');
    /*
     * User routes
     */
    Route::middleware(['jwt.auth'])->prefix('users/me')->group(function () {
        //get update me
        Route::get('/', 'UsersController@getMe');
        Route::put('/', 'UsersController@updateMe');

        Route::middleware(['hackersOnly'])->group(function () {
            //user application
            Route::get('application', 'UsersController@getApplication');
            Route::put('application', 'UsersController@updateApplication');

            //user puzzle status
            Route::post('puzzles', 'UsersController@completePuzzle');
            Route::get('puzzles', 'UsersController@getCompletedPuzzleIDs');
        });
    });
    /*
     * Exec routes
     */
    Route::middleware(['jwt.auth', 'role:exec'])->prefix('execs')->group(function () {
        //list hackers
        Route::get('hackers', 'ExecController@getHackers');
        //old routes?
        Route::post('hackers/bulk', 'ExecController@getHackersBulk');
        Route::put('hackers/bulk', 'ExecController@putHackersBulk');
        //get users list/overview by ID/perform action by ID
        Route::get('users', 'ExecController@getUsers');
        Route::get('users/{id}/view', 'ExecController@getUser');
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

        //?
        Route::get('interest', 'ExecController@getInterestData')->middleware(['jwt.auth', 'role:exec']);
        Route::get('calendar', 'ExecController@generateCalendar');
    });

    /*
     * PODPOD
     */

    Route::prefix('pods')->group(function () {
        Route::post('scan', 'PodController@scan');
        Route::get('list', 'PodController@listPods');
        Route::get('events', 'PodController@listEvents');
        Route::get('scans', 'PodController@listScans');
        Route::post('heartbeat', 'PodController@heartbeat');
    });
});

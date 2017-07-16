<?php

use Carbon\Carbon;
use App\Models\Application;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('applications:calculate', function () {
    foreach (Application::all() as $app) {
        $app->completed_calculated = $app->completed;
        $app->save();
    }
})->describe('calculate application status and put it in the DB');

Artisan::command('applications:expiredrsvp', function () {
    $expiredAppsIds = [];
    foreach (Application::where('decision', Application::DECISION_ACCEPT)->whereNull('rsvp')->get() as $app) {
        if (Carbon::parse($app->rsvp_deadline)->lt(Carbon::now())) {
            $app->decision = Application::DECISION_EXPIRED;
            $app->save();
            $expiredAppsIds[] = $app->id;
        }
    }
    $message = 'ExpiredRSVP: '.count($expiredAppsIds).' applications expired';
    $this->comment($message);
    Log::info($message, ['application_ids'=>$expiredAppsIds]);
})->describe('process expired RSVPs');

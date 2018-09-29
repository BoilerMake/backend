<?php

use Carbon\Carbon;
use App\Models\Application;

Artisan::command('applications:calculate', function () {
    Application::calculateCompleted();
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
    Log::debug($message, ['application_ids'=>$expiredAppsIds]);
})->describe('process expired RSVPs');

Artisan::command('applications:incompleteEmails', function () {
    Application::calculateCompleted();
    foreach (Application::with('user')->where('completed_calculated', false)->get() as $app) {
        $this->info($app->user->first_name.','.$app->user->email);
    }
})->describe('get info for incomplete applications');

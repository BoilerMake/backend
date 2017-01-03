<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    protected $appends = ['hackers'];

    public function getHackersAttribute()
    {
        $hackers = [];
        foreach (Application::where('team_id', $this->id)->get() as $app) {
            $hackers[] = User::find($app->user_id);
        }

        return $hackers;
    }

    public function getHackersWithRating()
    {
        $hackers = [];
        foreach (Application::where('team_id', $this->id)->get() as $app) {
            $hacker = User::with('application', 'application.ratings', 'application.school')->find($app->user_id);
            $hacker['application']['ratinginfo'] = $hacker->application->ratingInfo();
            $hackers[] = $hacker;
        }

        return $hackers;
    }
}

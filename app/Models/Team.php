<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
	protected $appends = ['hackers'];
    public function getHackersAttribute() {
    	$hackers = [];
        foreach (Application::where("team_id",$this->id)->get() as $app) {
        	$hackers[]=User::find($app->user_id);
        }
        return $hackers;
    }
}

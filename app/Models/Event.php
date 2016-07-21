<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    public function scans()
    {
        return $this->hasMany('App\Models\PodScan');
    }
    public function active_pods()
    {
        return $this->hasMany('App\Models\Pod','current_event_id');
    }

}

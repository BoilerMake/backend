<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PodEvent extends Model
{
    public function scans()
    {
        return $this->hasMany('App\Models\PodScan');
    }
    public function active_pods()
    {
        return $this->hasMany('App\Models\Pod','current_pod_event_id');
    }

}

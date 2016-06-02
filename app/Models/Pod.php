<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pod extends Model
{
    public function event()
    {
        return $this->hasOne('App\Models\PodEvent','id','current_pod_event_id');
    }
    public function scans()
    {
        return $this->hasMany('App\Models\PodScan');
    }
}

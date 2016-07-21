<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pod extends Model
{
    public function event()
    {
        return $this->hasOne('App\Models\Event','id','current_event_id');
    }
    public function scans()
    {
        return $this->hasMany('App\Models\PodScan');
    }
}

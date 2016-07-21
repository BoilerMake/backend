<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PodScan extends Model
{
    public function user() {
        return $this->belongsTo('App\Models\User');
    }
    public function pod() {
        return $this->belongsTo('App\Models\Pod');
    }
    public function event() {
        return $this->belongsTo('App\Models\Event');
    }
}

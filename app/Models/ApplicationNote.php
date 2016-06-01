<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationNote extends Model
{

    public function user() {
        return $this->hasOne('App\Models\User', 'id', 'user_id');
    }

    public function application() {
        return $this->hasOne('App\Models\Application', 'id', 'application_id');
    }
}

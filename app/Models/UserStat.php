<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserStat extends Model
{
    protected $guarded = ['id'];
    public function getContextAttribute($value) {
        return json_decode($value);
    }
}

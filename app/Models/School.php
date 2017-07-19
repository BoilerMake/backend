<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class School extends Model
{
    const FIELD_EMAIL_DOMAIN = 'email_domain';
    protected $fillable = [
        '*',
    ];
}

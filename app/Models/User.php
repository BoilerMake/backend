<?php

namespace App\Models;
use Tymon\JWTAuth\JWTAuth;
use Zizaco\Entrust\Traits\EntrustUserTrait;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use EntrustUserTrait;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name', 'last_name', 'email', 'password',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];
    public function postSignupActions()
    {
        $this->attachRole(Role::where('name','hacker')->first());
    }
    public function slug() {
        return $this->first_name." ".$this->last_name." (#".$this->id.")";
    }
}

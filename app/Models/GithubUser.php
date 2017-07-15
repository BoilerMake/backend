<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class GithubUser extends Model
{
    protected $guarded = ['id'];

    /**
     * Saves github API response data to the db
     * @param $data
     * @param null $token
     * @return mixed
     */
    public static function store($data, $token = null) {
        $username = $data['login'];

        $g = self::firstOrCreate(['username'=>$username]);

        if($token) {
            $g->access_token = $token;
        }

        $g->username                    = $username;
        $g->github_user_id              = $data['id'];
        $g->avatar_url                  = $data['avatar_url'];
        $g->gravatar_id                 = $data['gravatar_id'];
        $g->type                        = $data['type'];
        $g->name                        = $data['name'];
        $g->company                     = $data['company'];
        $g->blog                        = $data['blog'];
        $g->location                    = $data['location'];
        $g->email                       = $data['email'];
        $g->bio                         = $data['bio'];
        $g->public_repos                = $data['public_repos'];
        $g->public_gists                = $data['public_gists'];
        $g->followers                   = $data['followers'];
        $g->following                   = $data['following'];
        $g->github_account_created_at   = Carbon::parse($data['created_at']);
        $g->last_fetched_at             = Carbon::now();
        $g->save();

        $action = $g->wasRecentlyCreated ? 'created' : 'updated';
        \Log::info("GithubUser {$action} for {$username}");
        return $g;
    }
}

<?php

namespace App\Models;

use Log;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class GithubUser extends Model
{
    protected $guarded = ['id'];

    /**
     * Saves github API response data to the db.
     * @param $data
     * @param null $token
     * @return mixed
     */
    public static function store($data, $token = null)
    {
        $username = $data['login'];

        $g = self::firstOrCreate(['username'=>$username]);

        if ($token) {
            $g->access_token = $token;
        }

        $g->username = $username;
        $g->github_user_id = $data['id'];
        $g->avatar_url = $data['avatar_url'];
        $g->gravatar_id = $data['gravatar_id'];
        $g->type = $data['type'];
        $g->name = $data['name'];
        $g->company = $data['company'];
        $g->blog = $data['blog'];
        $g->location = $data['location'];
        $g->email = $data['email'];
        $g->bio = $data['bio'];
        $g->public_repos = $data['public_repos'];
        $g->public_gists = $data['public_gists'];
        $g->followers = $data['followers'];
        $g->following = $data['following'];
        $g->github_account_created_at = Carbon::parse($data['created_at']);
        $g->last_fetched_at = Carbon::now();
        $g->save();

        $action = $g->wasRecentlyCreated ? 'created' : 'updated';
        \Log::info("GithubUser {$action} for {$username}");

        return $g;
    }

    /**
     * Gets a github Oauth token from an code.
     * @codeCoverageIgnore
     * @param $code
     * @return bool
     */
    public static function getGithubAuthToken($code)
    {
        $client = new \GuzzleHttp\Client();
        $response = $client->request('POST', 'https://github.com/login/oauth/access_token', [
            'form_params' => [
                'client_id'     => env('GITHUB_CLIENT_ID'),
                'client_secret' => env('GITHUB_CLIENT_SECRET'),
                'code'          => $code,
            ],
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);
        //TODO: error handling
        $result = json_decode($response->getBody(), true);

        if (isset($result['error'])) {
            //todo: handle error here...
            Log::error('getGithubAuthToken access token fetching error', $result);

            return false;
        }
        Log::info('getGithubAuthToken successfully fetched userdata from access token', $result);
        $access_token = $result['access_token'];

        return $access_token;
    }

    /**
     * @param $gitHub_token
     * @return mixed
     * @codeCoverageIgnore
     */
    public static function fetchFromOauthToken($gitHub_token)
    {
        $client = new \GuzzleHttp\Client();
        $response = $client->request('GET', "https://api.github.com/user?access_token={$gitHub_token}", [
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);
        $thisGithubUser = json_decode($response->getBody(), true);
        Log::info('fetchFromOauthToken: user API response, user:'.$thisGithubUser['login'], $thisGithubUser);
        //so the prior response might not include an email unless it's a public email, so we need to explicitly grab email

        $emailResponse = $client->request('GET', "https://api.github.com/user/emails?access_token={$gitHub_token}", [
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);
        $emailResult = json_decode($emailResponse->getBody(), true);
        Log::info('fetchFromOauthToken: $emailResult API response', $emailResult);
        foreach ($emailResult as $email) {
            if ($email['primary']) {
                $thisGithubUser['email'] = $email['email'];
                Log::info('found primary email for user! '.$email['email']);
            }
        }

        return self::store($thisGithubUser, $gitHub_token);
    }
}

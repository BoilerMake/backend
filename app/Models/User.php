<?php

namespace App\Models;

use JWTAuth;
use Carbon\Carbon;
use App\Services\Notifier;
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

    protected $appends = ['launch', 'name'];

    public function getLaunchAttribute()
    {
        return $this->id.''.substr($this->first_name, 0, 1);
    }

    public function getNameAttribute()
    {
        return $this->first_name.' '.$this->last_name;
    }

    /**
     * makes a user a hacker by default and gives them an application.
     */
    public function postSignupActions($roles = ['hacker'])
    {
        foreach ($roles as $role) {
            $this->attachRole(Role::where('name', $role)->first());
            if ($role == 'hacker') {
                $app = new Application();
                $app->user_id = $this->id;
                $app->save();
            }
        }
        $this->generateUniqueIdentifier();
    }

    public function generateUniqueIdentifier()
    {
        if (! $this->identifier) {
            $rand = substr(str_shuffle(str_repeat('0123456789', 9)), 0, 9);
            //appending ID will ensure uniqueness as well as allow for easier visual debugging without
            $this->identifier = $rand.str_pad($this->id, 4, '0', STR_PAD_LEFT);
            $this->save();
        }
    }

    public function slug()
    {
        return $this->first_name.' '.$this->last_name.' (#'.$this->id.')';
    }

    public function application()
    {
        return $this->hasOne('App\Models\Application');
    }

    public function sendPasswordResetEmail()
    {
        $token = md5(Carbon::now().env('APP_KEY'));
        $reset = new PasswordReset();
        $reset->user_id = $this->id;
        $reset->token = $token;
        $reset->save();

        $n = new Notifier($this);
        $n->sendEmail('BoilerMake Password Reset!', 'password-reset', ['token_url'=>getenv('FRONTEND_ADDRESS').'/pwr?tok='.$token]);
    }

    public function getApplication($exec = false)
    {
        if ($exec) {
            $application = Application::with('school', 'team', 'ratings', 'notes')->firstOrCreate(['user_id' => $this->id]);
        } else {
            $application = Application::with('school', 'team', 'ratings')->firstOrCreate(['user_id' => $this->id]);
        }

        if (! $application->team_id) {
            //assign them to a team of 1 in lieu of no team
            $team = new Team();
            //adding user ID to the end of a hash guaruntee it to be unique, even if md5 isn't, without doing a DB check
            $team->code = substr(md5(Carbon::now().getenv('APP_KEY')), 0, 4).$this->id;
            $team->save();
            $application->team_id = $team->id;
        }
        $application->save();
        $application->teaminfo = $application->team;
        $application->schoolinfo = $application->school;
        $application->resume_uploaded = (int) $application->resume_uploaded;

        return $application;
    }

    public function getToken()
    {
        $roles = $this->roles()->get()->lists('name');

        return JWTAuth::fromUser($this, ['exp' => strtotime('+1 year'), 'roles'=>$roles, 'slug'=>$this->slug(), 'user_id'=>$this->id]);
    }
}

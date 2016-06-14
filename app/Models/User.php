<?php

namespace App\Models;
use App\Services\Notifier;
use Tymon\JWTAuth\JWTAuth;
use Zizaco\Entrust\Traits\EntrustUserTrait;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Carbon\Carbon;
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
    /**
    * makes a user a hacker by default and gives them an application
    */
    public function postSignupActions($roles=["hacker"])
    {
        foreach ($roles as $role)
        {
            $this->attachRole(Role::where('name',$role)->first());
            if($role=="hacker")
            {
                $app = new Application();
                $app->user_id=$this->id;
                $app->save();
            }
        }
        $this->generateUniqueIdentifier();
    }
    public function generateUniqueIdentifier()
    {
        if(!$this->identifier) {
            $this->identifier = substr(str_shuffle(str_repeat('0123456789', 13)), 0, 13);
            $this->save();//todo: check for conflict
        }
    }
    public function slug() {
        return $this->first_name." ".$this->last_name." (#".$this->id.")";
    }
    public function application() {
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
        $n->sendEmail("BoilerMake Password Reset!",'password-reset',['token_url'=>getenv('FRONTEND_ADDRESS')."/pwr?tok=".$token]);
    }
    public function getApplication()
    {
        $application = Application::with('school','team','ratings','notes')->firstOrCreate(['user_id' => $this->id]);
        if(!$application->team_id)
        {
            //assign them to a team of 1 in lieu of no team
            $team = new Team();
            $team->code = md5(Carbon::now().getenv("APP_KEY"));
            $team->save();
            $application->team_id = $team->id;
        }
        $application->save();
        $application->teaminfo = $application->team;
        $application->schoolinfo = $application->school;
        return $application;
    }
}

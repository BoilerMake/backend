<?php

namespace App\Models;

use App\Mail\UserRegistration;
use Hash;
use Illuminate\Support\Str;
use JWTAuth;
use Carbon\Carbon;
use App\Services\Notifier;
use Log;
use Mail;
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

    protected $hidden = [ 'password' ];
    protected $appends = ['launch', 'name'];

    const ROLE_HACKER = "hacker";

    const FIELD_EMAIL = "email";
    const FIELD_FIRSTNAME = "first_name";
    const FIELD_LASTNAME = "last_name";
    const FIELD_PHONE = "phone";

    public function getLaunchAttribute()
    {
        return $this->id.''.substr($this->first_name, 0, 1);
    }

    public function getNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * @param string $email
     * @param null $password
     * @param bool $needToConfirmEmail
     * @param array $roles
     * @return User
     * @internal param bool $shouldConfirmEmail
     */
    public static function addNew($email, $password = null, $needToConfirmEmail = true, $roles = [self::ROLE_HACKER]) {
        $user = new self;
        if(!$password) {
            //if a password was not provided during signup (i.e. GitHub OAuth, use something random)
            $password = Str::random(10);
        }
        $user->password = Hash::make($password);
        $user->email = $email;
        $user->save();
        $user->postSignupActions($roles); // Attach roles

        if($needToConfirmEmail) {
            $code = str_random(24);
            $user->confirmation_code = $code;
            $link = env('FRONTEND_ADDRESS') . '/confirm?tok=' . $code;
            //todo: clean up this email building
            Mail::to($user->email)->send(new UserRegistration($user, $link));
        } else {
            $user->confirmed = true;
        }
        $user->save();
        return $user;
    }

    /**
     * Checks if a a User exists with the given email
     * @param $email
     * @return mixed
     */
    public static function isEmailUsed($email) {
        return self::where('email',$email)->exists();
    }

    /**
     * makes a user a hacker by default and gives them an application.
     * @param $roles
     */
    private function postSignupActions($roles)
    {
        foreach ($roles as $role) {
            $this->attachRole(Role::where('name', $role)->first());
            Log::info("Attaching role: {$role} to user: {$this->id}",['user_id'=>$this->id]);
            if ($role == self::ROLE_HACKER) {
                //this will create the application
                $this->getApplication();
            } else {
                //TODO: implement
                Log::error("postSignupActions: need to implement role {$role}");
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
        return "{$this->first_name} {$this->last_name} (#{$this->id})";
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

    /**
     * @param bool $execInfo
     * @return Application
     */
    public function getApplication($execInfo = false)
    {
        //TODO: make sure user is a hacker
        if(!$this->hasRole(self::ROLE_HACKER)) {
            Log::error("tried to get application for user {$this->id}, but they are not a hacker");
        }

        if ($execInfo) {
            $application = Application::with('school', 'ratings', 'notes')->firstOrCreate(['user_id' => $this->id]);
        } else {
            $application = Application::with('school')->firstOrCreate(['user_id' => $this->id]);
        }
        if($application->wasRecentlyCreated) {
            Log::info("Creating application for user {$this->id}",['user_id'=>$this->id, 'application_id'=>$application->id]);
        }
        $application->save();

        return $application;
    }

    public function getToken()
    {
        $roles = $this->roles()->get()->pluck('name');

        return JWTAuth::fromUser($this, ['exp' => strtotime('+1 year'), 'roles'=>$roles, 'slug'=>$this->slug(), 'user_id'=>$this->id]);
    }
}

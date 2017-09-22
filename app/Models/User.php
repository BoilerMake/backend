<?php

namespace App\Models;

use App;
use AWS;
use Log;
use Hash;
use Mail;
use JWTAuth;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Mail\UserRegistration;
use OwenIt\Auditing\Auditable;
use Zizaco\Entrust\Traits\EntrustUserTrait;
use App\Mail\PasswordReset as PasswordResetEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Hashids\Hashids;

class User extends Authenticatable implements AuditableContract
{
    use Auditable;
    use EntrustUserTrait;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name', 'last_name', 'email', 'password',
    ];

    protected $hidden = ['password', self::FIELD_CONFIRMATION_CODE];
    protected $appends = ['launch', 'name','hashid'];

    const ROLE_HACKER = 'hacker';

    const FIELD_EMAIL = 'email';
    const FIELD_FIRSTNAME = 'first_name';
    const FIELD_LASTNAME = 'last_name';
    const FIELD_PHONE = 'phone';
    const FIELD_CONFIRMATION_CODE = 'confirmation_code';

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
    public static function addNew($email, $password = null, $needToConfirmEmail = true, $roles = [self::ROLE_HACKER])
    {
        $user = new self;
        if (! $password) {
            //if a password was not provided during signup (i.e. GitHub OAuth, use something random)
            $password = Str::random(10);
        }
        $user->password = Hash::make($password);
        $user->email = $email;
        $user->save();
        Log::info('User::addNew', ['user_id'=>$user->id]);
        $user->postSignupActions($roles); // Attach roles
        if ($needToConfirmEmail) {
            $code = str_random(10);
            $user[self::FIELD_CONFIRMATION_CODE] = $code;
            $link = env('FRONTEND_ADDRESS').'/confirm/'.$code;
            //todo: clean up this email building
            Log::info("going to send UserRegistration to user_id {$user->id}, email {$email} ", ['user_id'=>$user->id]);
            Mail::to($email)->send(new UserRegistration($user, $link));
        } else {
            $user->confirmed = true;
        }
        $user->save();

        return $user;
    }

    /**
     * Checks if a a User exists with the given email.
     * @param $email
     * @return mixed
     */
    public static function isEmailUsed($email)
    {
        return self::where('email', $email)->exists();
    }

    /**
     * makes a user a hacker by default and gives them an application.
     * @param $roles
     */
    private function postSignupActions($roles)
    {
        foreach ($roles as $role) {
            $this->attachRole(Role::where('name', $role)->first());
            Log::info("Attaching role: {$role} to user: {$this->id}", ['user_id'=>$this->id]);
            if ($role == self::ROLE_HACKER) {
                //this will create the application
                $application = $this->getApplication();
                $application->school_id = $this->hintSchoolIdFromEmail();
            } else {
                //TODO: implement
                Log::error("postSignupActions: need to implement role {$role}");
            }
        }
    }
    public function getHashIDAttribute() {
        $hashids = new Hashids('', 0, 'abcdefghijklmnopqrstuvwxyz'); // all lowercase
        return $hashids->encode($this->id);
    }
    public static function getFromHashID($h) {
        $hashids = new Hashids('', 0, 'abcdefghijklmnopqrstuvwxyz');
        $res = $hashids->decode($h);
        if(sizeof($res) != 1) {
            Log::error("bad hashID: {$h}, could not decode");
            return null;
        }
        return self::find($res[0]);
    }

    public function slug()
    {
        return "{$this->first_name} {$this->last_name} (#{$this->id})";
    }

    public function application()
    {
        return $this->hasOne('App\Models\Application');
    }

    /**
     * Sends the user a password reset email.
     */
    public function sendPasswordResetEmail()
    {
        $token = md5(Carbon::now().env('APP_KEY'));
        $reset = new PasswordReset();
        $reset->user_id = $this->id;
        $reset->token = $token;
        $reset->save();

        $link = getenv('FRONTEND_ADDRESS').'/reset/'.$token;
        Log::info("going to send PasswordReset to user_id {$this->id}, email {$this->email} ", ['user_id'=>$this->id]);
        Mail::to($this->email)->send(new PasswordResetEmail($this, $link));
    }

    /**
     * Gets the user's application.
     */
    public function getApplication()
    {
        if (! $this->hasRole(self::ROLE_HACKER)) {
            Log::error("tried to get application for user {$this->id}, but they are not a hacker");

            return;
        }

        $application = Application::with('school')->firstOrCreate(['user_id' => $this->id]);

        if ($application->wasRecentlyCreated) {
            Log::info("Creating application for user {$this->id}", ['user_id'=>$this->id, 'application_id'=>$application->id]);
        }

        return $application;
    }

    public function getToken()
    {
        $roles = $this->roles()->get()->pluck('name');

        return JWTAuth::fromUser($this, ['exp' => strtotime('+1 year'), 'roles'=>$roles, 'slug'=>$this->slug(), 'user_id'=>$this->id]);
    }

    /**
     * Pre signs an S3 URL pointing to a given user id.
     * @param $id user ID
     * @param string $method GET or PUT
     * @return string the signed
     * @codeCoverageIgnore
     */
    public function resumeURL($method = 'get')
    {
        if (App::environment() == 'testing') {
            return "http://s3-mock-resumes/{$this->id}.pdf";
        }
        $s3 = AWS::createClient('s3');
        switch ($method) {
            case 'get':
                $cmd = $s3->getCommand('getObject', [
                    'Bucket' => getenv('S3_BUCKET'),
                    'Key'    => getenv('S3_PREFIX').'/resumes/'.$this->id.'.pdf',
                    'ResponseContentType' => 'application/pdf',
                ]);
                break;
            case 'put':
                $cmd = $s3->getCommand('PutObject', [
                    'Bucket' => getenv('S3_BUCKET'),
                    'Key'    => getenv('S3_PREFIX').'/resumes/'.$this->id.'.pdf',
                ]);
                break;
        }
        $request = $s3->createPresignedRequest($cmd, '+7 days');

        return (string) $request->getUri();
    }

    /**
     * Tries to get school ID based on email.
     * @return null|int
     */
    public function hintSchoolIdFromEmail()
    {
        $domain = substr(strrchr($this->email, '@'), 1);
        $match = School::where(School::FIELD_EMAIL_DOMAIN, $domain)->first();
        if ($match) {
            return $match->id;
        }
    }

    /**
     * Turns strings like github.com/username into username.
     * @param $value
     */
    public static function extractUsernameFromURL($url)
    {
        if (strpos($url, '/') === false) {
            //no slashes in it probably means it's a vanilla username
            return $url;
        }

        return substr($url, strrpos($url, '/') + 1);
    }
}

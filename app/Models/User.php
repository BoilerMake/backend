<?php

namespace App\Models;

use App;
use Log;
use Hash;
use Mail;
use JWTAuth;
use Storage;
use Carbon\Carbon;
use Hashids\Hashids;
use Illuminate\Support\Str;
use App\Mail\UserRegistration;
use OwenIt\Auditing\Auditable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Zizaco\Entrust\Traits\EntrustUserTrait;
use App\Mail\PasswordReset as PasswordResetEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class User extends Authenticatable implements AuditableContract, JWTSubject
{
    use Auditable;
    use EntrustUserTrait;
    use Notifiable;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name', 'last_name', 'email', 'password',
    ];

    protected $hidden = ['password', self::FIELD_CONFIRMATION_CODE];
    protected $appends = ['launch', 'name', 'hashid'];

    const ROLE_HACKER = 'hacker';
    const ROLE_ORGANIZER = 'organizer';
    const ROLE_EXEC = 'exec';
    const ROLE_SPONSOR = 'sponsor';
    const ROLE_GUEST = 'guest';
    const ROLES = [
        self::ROLE_HACKER,
        self::ROLE_GUEST,
        self::ROLE_SPONSOR,
        self::ROLE_ORGANIZER,
        self::ROLE_EXEC,
    ];

    const FIELD_EMAIL = 'email';
    const FIELD_FIRSTNAME = 'first_name';
    const FIELD_LASTNAME = 'last_name';
    const FIELD_PHONE = 'phone';
    const FIELD_CONFIRMATION_CODE = 'confirmation_code';
    const FIELD_PROJECT_IDEA = 'project_idea';
    const FIELD_TEAM_NAME_1 = 'team_name_1';
    const FIELD_TEAM_NAME_2 = 'team_name_2';
    const FIELD_TEAM_NAME_3 = 'team_name_3';

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
            Log::debug("Attaching role: {$role} to user: {$this->id}", ['user_id'=>$this->id]);
            if ($role == self::ROLE_HACKER) {
                //this will create the application
                $application = $this->getApplication();
                $application->school_id = $this->hintSchoolIdFromEmail();
            }
        }
    }

    public function getHashIDAttribute()
    {
        $hashids = new Hashids('', 0, 'abcdefghijklmnopqrstuvwxyz'); // all lowercase
        return $hashids->encode($this->id);
    }

    public static function getFromHashID($h)
    {
        $hashids = new Hashids('', 0, 'abcdefghijklmnopqrstuvwxyz');
        $res = $hashids->decode($h);
        if (count($res) != 1) {
            Log::error("bad hashID: {$h}, could not decode");

            return;
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
        Log::debug("going to send PasswordReset to user_id {$this->id}, email {$this->email} ", ['user_id'=>$this->id]);
        Mail::to($this->email)->send(new PasswordResetEmail($this, $link));
    }

    /**
     * Gets the user's application.
     */
    public function getApplication()
    {
        if (! $this->hasRole(self::ROLE_HACKER)) {
            Log::alert("tried to get application for user {$this->id}, but they are not a hacker");

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
        return JWTAuth::fromUser($this);
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

        $expiry = '+7 days';

        if ($method == 'get') {
            return Storage::cloud()->temporaryUrl($this->getResumeFilePath(), now()->modify($expiry), ['ResponseContentType' => 'application/pdf']);
        }

        $client = Storage::cloud()->getDriver()->getAdapter()->getClient();
        $command = $client->getCommand('PutObject', [
            'Bucket' => getenv('AWS_BUCKET'),
            'Key'    => $this->getResumeFilePath(),
        ]);
        $request = $client->createPresignedRequest($command, $expiry);

        return (string) $request->getUri();
    }

    public function getResumeFilePath()
    {
        return getenv('S3_PREFIX').'/resumes/'.$this->id.'.pdf';
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

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        $roles = $this->roles()->get()->pluck('name');

        return [
            'exp' => strtotime('+1 year'),
            'roles' => $roles,
            'user_id' => $this->id,
        ];
    }
}

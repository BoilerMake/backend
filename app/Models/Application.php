<?php

namespace App\Models;

use Cache;
use GuzzleHttp;
use Carbon\Carbon;
use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Application extends Model implements AuditableContract
{
    use Auditable;
    use SoftDeletes;
    const DECISION_EXPIRED = 4;
    const DECISION_ACCEPT = 3;
    const DECISION_WAITLIST = 2;
    const DECISION_REJECT = 1;
    const DECISION_UNDECIDED = 0;

    const PHASE_INTEREST_SIGNUPS = 1;
    const PHASE_APPLICATIONS_OPEN = 2;
    const PHASE_DECISIONS_REVEALED = 3;

    const USER_FIELDS_TO_INJECT = [
        User::FIELD_FIRSTNAME,
        User::FIELD_LASTNAME,
        User::FIELD_EMAIL,
        User::FIELD_PHONE,
    ];

    const FIELD_GENDER = 'gender';
    const FIELD_MAJOR = 'major';
    const FIELD_GRAD_YEAR = 'grad_year';
    const FIELD_DIET = 'diet';
    const FIELD_DIET_RESTRICTIONS = 'diet_restrictions';
    const FIELD_GITHUB = 'github';
    const FIELD_LINKEDIN = 'linkedin';
    const FIELD_RESUME_FILENAME = 'resume_filename';
    const FIELD_RESUME_UPLOADED_FLAG = 'resume_uploaded';
    const FIELD_RSVP_FLAG = 'rsvp';
    const FIELD_IS_FIRST_HACKATHON = 'isFirstHackathon';
    const FIELD_RACE = 'race';
    const FIELD_HAS_NO_GITHUB = 'has_no_github';
    const FIELD_COMPLETED_CALCULATED = 'completed_calculated';
    const FIELD_SKILLS = 'skills';
    const FIELD_RSVP_DEADLINE = 'rsvp_deadline';
    const FIELD_HAS_NO_LINKEDIN = 'has_no_linkedin';
    const FIELD_EMAILED_DECISION = 'emailed_decision';
    const FIELD_DECISION = 'decision';
    const FIELD_CHECKED_IN_AT = 'checked_in_at';
    const FIELD_GITHUB_ETAG = 'github_etag';
    const FIELD_TSHIRT = 'tshirt';
    const FIELD_TERMS_AND_CONDITIONS_1 = 'tandc_1'; //over 18
    const FIELD_TERMS_AND_CONDITIONS_2 = 'tandc_2'; //MLH code of conduct
    const FIELD_TERMS_AND_CONDITIONS_3 = 'tandc_3'; //privacy policy

    const INITIAL_FIELDS = [
        self::FIELD_GRAD_YEAR,
        self::FIELD_GENDER,
        self::FIELD_MAJOR,
        self::FIELD_RACE,
        self::FIELD_RESUME_FILENAME,
        self::FIELD_RESUME_UPLOADED_FLAG,
        self::FIELD_IS_FIRST_HACKATHON,
        self::FIELD_HAS_NO_GITHUB,
        self::FIELD_HAS_NO_LINKEDIN,
        'school_id',
        self::FIELD_TERMS_AND_CONDITIONS_1,
        self::FIELD_TERMS_AND_CONDITIONS_2,
        self::FIELD_TERMS_AND_CONDITIONS_3,
    ];

    public $schoolinfo = null;

    protected $dates = [
        'created_at',
        'updated_at',
        self::FIELD_RSVP_DEADLINE,
    ];
    protected $guarded = ['id'];
    protected $appends = ['completed'];

    public static function calculateCompleted()
    {
        $count = 0;
        foreach (self::all() as $app) {
            $app->completed_calculated = $app->completed;
            if ($app->completed) {
                $count++;
            }
            $app->save();
        }

        return $count;
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    public function school()
    {
        return $this->belongsTo('App\Models\School');
    }

    public function notes()
    {
        return $this->hasMany('App\Models\ApplicationNote');
    }

    public function getCompletedAttribute()
    {
        return $this->validationDetails()['valid'];
    }

    public function validationDetails($includeRSVPFields = false)
    {
        $reason_label = [];
        $reason_field = [];
        if (! $this->user->first_name) {
            $reason_label[] = 'First name not set.';
            $reason_field[] = 'first_name';
        }
        if (! $this->user->last_name) {
            $reason_label[] = 'Last name not set.';
            $reason_field[] = 'last_name';
        }
        if (! $this->user->phone) {
            $reason_label[] = 'Phone number not set.';
            $reason_field[] = 'phone';
        }
        if (! isset($this->school_id)) {
            $reason_label[] = 'School not set.';
            $reason_field[] = 'school_id';
        }
        if (! ($this->resume_uploaded)) {
            $reason_label[] = 'Resume not uploaded.';
            $reason_field[] = self::FIELD_RESUME_FILENAME;
        }
        if (! ($this->github) && ! ($this->has_no_github)) {
            $reason_label[] = "Github username not provided. If you don't have a github, indicate that.";
            $reason_field[] = self::FIELD_GITHUB;
        }
        if (! ($this->linkedin) && ! ($this->has_no_linkedin)) {
            $reason_label[] = "LinkedIn username not provided. If you don't have a LinkedIn, indicate that.";
            $reason_field[] = self::FIELD_LINKEDIN;
        }
        if (! isset($this->grad_year)) {
            $reason_label[] = 'Grad year not provided.';
            $reason_field[] = self::FIELD_GRAD_YEAR;
        }
        if (! isset($this->gender)) {
            $reason_label[] = 'Gender not provided.';
            $reason_field[] = self::FIELD_GENDER;
        }
        if (! ($this->major)) {
            $reason_label[] = 'Major not provided.';
            $reason_field[] = self::FIELD_MAJOR;
        }
        if (! isset($this->isFirstHackathon)) {
            $reason_label[] = 'First hackathon? not provided.';
            $reason_field[] = self::FIELD_IS_FIRST_HACKATHON;
        }
        if (! isset($this->race)) {
            $reason_label[] = 'Race not provided.';
            $reason_field[] = self::FIELD_RACE;
        }
        if (! $this[self::FIELD_TERMS_AND_CONDITIONS_1]) {
            $reason_label[] = 'Please confirm if you are 18 years of age or older.';
            $reason_field[] = self::FIELD_TERMS_AND_CONDITIONS_1;
        }
        if (! $this[self::FIELD_TERMS_AND_CONDITIONS_2]) {
            $reason_label[] = 'Please confirm if you agree to the MLH Code of Conduct.';
            $reason_field[] = self::FIELD_TERMS_AND_CONDITIONS_2;
        }
        if (! $this[self::FIELD_TERMS_AND_CONDITIONS_3]) {
            $reason_label[] = 'Please confirm that you agree to both the MLH Contest Terms and Conditions and the MLH Privacy Policy.';
            $reason_field[] = self::FIELD_TERMS_AND_CONDITIONS_3;
        }

        if ($includeRSVPFields) {
            if (! $this->diet) {
                $reason_label[] = 'Dietary info not provided';
            }
        }

        return [
            'valid'   => (count($reason_label) === 0),
            'reason_label' => $reason_label,
            'reason_field' => $reason_field,
        ];
    }

    /**
     * Pulls in some github info.
     * @return array|mixed
     * @codeCoverageIgnore
     */
    public function getGithubSummary()
    {
        $github_username = $this->github;
        if (! $github_username) {
            return ['success'=>false, 'message'=>'github username not provided'];
        }

        if (Cache::has('github_summary-'.$github_username)) {
            return Cache::get('github_summary-'.$github_username);
        }
        $client = new GuzzleHttp\Client();

        try {
            $response = $client->get('https://api.github.com/users/'.$github_username.'/repos?sort=updated',
            [
            'auth' => [
                env('GITHUB_API_USERNAME'),
                env('GITHUB_API_TOKEN'),
            ],
        ]);
        } catch (GuzzleHttp\Exception\ClientException $e) {
            return ['success'=>false, 'message'=>'github username was invalid'];
        }
        $response = json_decode($response->getBody(), true);
        $summary = [];
        foreach ($response as $each) {
            $summary[] = [
                'url'=>$each['html_url'],
                'full_name'=>$each['full_name'],
                'language'=>$each['language'],
                'description'=>$each['description'],
            ];
        }
        $data = ['success'=>true, 'summary'=> $summary];
        Cache::put('github_summary-'.$github_username, $data, Carbon::now()->addDays(10));

        return $data;
    }

    public static function getCurrentPhase()
    {
        return intval(getenv('APP_PHASE'));
    }

    /**
     * if $phase is less than or equal to current, it is in effect.
     * @param $phase
     * @return bool
     */
    public static function isPhaseInEffect($phase)
    {
        return $phase <= self::getCurrentPhase();
    }

    public function getPriorityLevelForAdmittance()
    {
        if ($this->decision == self::DECISION_ACCEPT && $this->rsvp) {
            return "SUPER HIGH (rsvp'd)";
        }
        if ($this->decision == self::DECISION_ACCEPT) {
            return 'HIGH (did not RSVP, but not expired)';
        }
        if ($this->decision == self::DECISION_EXPIRED) {
            return 'MEDIUM (did not RSVP, expired)';
        }
        if ($this->decision == self::DECISION_WAITLIST) {
            return 'LOW (waitlisted)';
        }
        if (! $this->getCompletedAttribute() || $this->decision == null) {
            return 'SUPER LOW (incomplete app or applied after deadline)';
        }

        return '???';
    }
}

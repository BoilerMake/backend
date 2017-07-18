<?php

namespace App\Models;

use Cache;
use GuzzleHttp;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Application extends Model
{
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
        'school_id'
    ];

    public $schoolinfo = null;

    protected $dates = [
        'created_at',
        'updated_at',
        self::FIELD_RSVP_DEADLINE,
    ];
    protected $guarded = ['id'];
    protected $appends = ['completed', 'reviews'];

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

    public function validationDetails()
    {
        $reasons = [];
        $phase = intval(getenv('APP_PHASE'));
        if ($phase >= self::PHASE_APPLICATIONS_OPEN) {
            if (! $this->user->first_name) {
                $reasons[] = 'First name not set.';
            }
            if (! $this->user->last_name) {
                $reasons[] = 'Last name not set.';
            }
            if (! isset($this->school_id)) {
                $reasons[] = 'School not set.';
            }
            if (! ($this->resume_uploaded)) {
                $reasons[] = 'Resume not uploaded.';
            }
            if (! ($this->github) && ! ($this->has_no_github)) {
                $reasons[] = "Github username not provided. If you don't have a github, indicate that.";
            }
            if (! ($this->linkedin) && ! ($this->has_no_linkedin)) {
                $reasons[] = "LinkedIn username not provided. If you don't have a LinkedIn, indicate that.";
            }
            if (! isset($this->grad_year)) {
                $reasons[] = 'Grad year not provided.';
            }
            if (! isset($this->gender)) {
                $reasons[] = 'Gender not provided.';
            }
            if (! ($this->major)) {
                $reasons[] = 'Major not provided.';
            }
            if (! isset($this->isFirstHackathon)) {
                $reasons[] = 'First hackathon? not provided.';
            }
            if (! isset($this->race)) {
                $reasons[] = 'Race not provided.';
            }
        }
        if ($phase >= self::PHASE_DECISIONS_REVEALED) {
            if (! $this->diet) {
                $reasons[] = 'Dietary info not provided';
            }
        }
        $valid = true;
        if (count($reasons) != 0) {
            $valid = false;
        }

        return ['valid'=>$valid, 'reasons'=>$reasons];
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
     * if $phase is less than or equal to current, it is in effect
     * @param $phase
     * @return bool
     */
    public static function isPhaseInEffect($phase) {
        return $phase <= self::getCurrentPhase();
    }
}

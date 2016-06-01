<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use GuzzleHttp;
use Cache;
use Carbon\Carbon;
class Application extends Model {
    use SoftDeletes;
    public $teaminfo = null;
    public $schoolinfo = null;
	protected $fillable =  ['user_id', 'age', 'gender', 'major', 'grad_year', 'diet', 'diet_restrictions', 'tshirt', 'phone', 'created_at', 'updated_at', 'deleted_at'];
	public function user() {
		return $this->belongsTo('App\Models\User');
	}
    public function school() {
        return $this->belongsTo('App\Models\School');
    }
    public function team() {
        return $this->belongsTo('App\Models\Team');
    }
    public function ratings()
    {
        return $this->hasMany('App\Models\ApplicationRating');
    }
    public function ratingInfo()
    {
        $count = ApplicationRating::where('application_id',$this->id)->get()->count();
        $sum = 0;
        $ratings=[];
        foreach ($this->ratings as $each) {
            $rating=intval($each->rating);
            $sum+=$rating;
            $ratings[]=$rating;
        }
        $min=0; $max=0; $avg=0;
            if($count!=0)
            {
                $avg = $sum/$count;
                $min = min($ratings);
                $max = max($ratings);
                $avg = $sum/$count;
            }
        return 
        [
            "count"=>$count,
            "min"=>$min,
            "max"=>$max,
            // "ratings"=>$ratings,
            "average"=>$avg
        ];
    }
	protected $appends = ['completed','reviews'];
    
    public function getCompletedAttribute()
    {
        return isset($this->age, $this->gender, $this->major, $this->grad_year, $this->essay1, $this->essay2);
    }
    public function validationDetails()
    {
        $reasons = [];
        $phase = intval(getenv('APP_PHASE'));
        if($phase >= 2)
        {
            if(!$this->school_id)
                $reasons[]="School not set.";
            if(!$this->resume_uploaded)
                $reasons[]="Resume not uploaded.";
            if(!$this->github)
                $reasons[]="Github handle not provided";
            if(!$this->essay1)
                $reasons[]="Essay 1 requirements not met";
            if(!$this->essay2)
                $reasons[]="Essay 1 requirements not met";
            if(!$this->age)
                $reasons[]="Age not provided.";
            if(!$this->grad_year)
                $reasons[]="Grad year not provided.";
            if(!$this->gender)
                $reasons[]="Gender not provided.";
            if(!$this->major)
                $reasons[]="Major not provided.";
        }
        if($phase >= 3)
        {
            if(!$this->diet)
                $reasons[]="Dietary info not provided";
            if(!$this->tshirt)
                $reasons[]="T-shirt size not provided";
        }
        $valid = true;
        if(sizeof($reasons)!=0)
            $valid=false;
        return ['valid'=>$valid, 'reasons'=>$reasons];
    }
    /**
    * Determine number of times the application has been reviewed
    */
    public function getReviewsAttribute()
    {
        return ApplicationRating::where('application_id',$this->id)->get()->count();
    }
    public function getGithubSummary()
    {
        $github_username = $this->github;//todo: catch bad username
        if(!$github_username)
            return ['success'=>false, 'message'=>'github username not provided'];

        if (Cache::has('github_summary-'.$github_username)) {
            return Cache::get('github_summary-'.$github_username);
        }
        $client = new GuzzleHttp\Client();

        try {
        $response = $client->get('https://api.github.com/users/'.$github_username.'/repos?sort=updated',
            [
            'auth' => [
                env('GITHUB_API_USERNAME'),
                env('GITHUB_API_TOKEN')
            ]
        ]);
        }
        catch (GuzzleHttp\Exception\ClientException $e) {
            return ['success'=>false, 'message'=>'github username was invalid'];
        }
        $response = json_decode($response->getBody(),true);
        $summary = [];
        foreach ($response as $each)
        {
            $summary[]=[
                'url'=>$each['html_url'],
                'full_name'=>$each['full_name'],
                'language'=>$each['language'],
                'description'=>$each['description'],
                'description'=>$each['description'],
            ];
        }
        $data = ['success'=>true, 'summary'=> $summary];
        Cache::put('github_summary-'.$github_username, $data, Carbon::now()->addDays(10));

         return $data;
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
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
    /**
    * Determine number of times the application has been reviewed
    */
    public function getReviewsAttribute()
    {
        return ApplicationRating::where('application_id',$this->id)->get()->count();
    }
}
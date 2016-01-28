<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Application extends Model {
    use SoftDeletes;
	protected $fillable =  ['user_id', 'age', 'gender', 'major', 'grad_year', 'diet', 'diet_restrictions', 'tshirt', 'phone', 'created_at', 'updated_at', 'deleted_at'];
	public function user() {
		return $this->belongsTo('App\Models\User');
	}
    public function school() {
        return $this->belongsTo('App\Models\School');
    }
	protected $appends = array('completed');

    public function getCompletedAttribute()
    {
    	//TODO: logic for determining if an app is 'complete'
        return true;   
    }
}
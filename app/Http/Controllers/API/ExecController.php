<?php namespace App\Http\Controllers\API;

use App\Models\User;
use App\Http\Controllers\Controller;
use Auth;
use Validator;
use Illuminate\Http\Request;
use App\Models\Application;
use Input;
use Log;
use DB;
use App\Models\Team;
use App\Models\Role;
use App\Models\ApplicationRating;
class ExecController extends Controller {

	public function __construct() {
       $this->middleware('jwt.auth', ['except' => []]);
	}
	public function getHackers() {
		if(!Auth::user()->hasRole('exec'))//TODO middleware perhaps?
			return;
		$users = User::whereHas('roles', function($q)
		{
		    $q->where('name', 'hacker');
		})->with('application','application.school')->get();
		foreach ($users as $user) {
			$user['application']['rating_info']=$user->application->ratingInfo();
		}
		return $users;
	}
	public function getNextApplicationID()
	{
		$user = Auth::user();
		foreach(Application::orderBy(DB::raw('RAND()'))->get() as $app)
		{
			//we must find the applications that are completed and have fewer than 3 reviews and that i didn't review
			if($app->completed)
				if($app->reviews < 3)
					if(!ApplicationRating::where('application_id',$app->id)->where('user_id',$user->id)->first())
						return($app->id);
		}
		return null;

	}
	public function getApplication($id)
	{
		$user = Auth::user();
		if(!Auth::user()->hasRole('exec'))//TODO middleware perhaps?
			return;
		$app = Application::with('user','school','team')->find($id);
		$app->myrating = ApplicationRating::where('application_id',$id)->where('user_id',$user->id)->first();
		return $app;
	}
	public function rateApplication(Request $request, $id)
	{
		$user = Auth::user();
		$rating = $request->all()['rating'];
		$ranking = ApplicationRating::firstOrNew(['application_id'=>intval($id),'user_id'=>$user->id]);
		$ranking->application_id =intval($id);
		$ranking->user_id =$user->id;
		$ranking->rating =$rating;
		$ranking->save();
		return ['next'=>self::getNextApplicationID()];
	}
	public function getTeams()
	{
		$teams = Team::all();
		foreach ($teams as $team ) {
			$team['hd']=$team->getHackersWithRating();
			$hackerRatings=[];
			$ratingSum=0;
			$ratingCount=0;
			foreach ($team['hd'] as $eachHackerDetail) {
				$eachHackerRating=$eachHackerDetail['application']['ratinginfo']['average'];
				$hackerRatings[]=$eachHackerRating;
				$ratingCount+=$eachHackerDetail['application']['ratinginfo']['count'];
				$ratingSum+=$eachHackerRating;
			}
			$min=0; $max=0; $avg=0;
			if($ratingCount!=0)
			{
				$avg = $ratingSum/$ratingCount;
				$min = min($hackerRatings);
				$max = max($hackerRatings);
			}
			$team['overall_ratings']=[
            "count"=>$ratingCount,
            "min"=>$min,
            "max"=>$max,
            // "ratings"=>$ratings,
            "average"=>$avg
        ];
		}
		return $teams;
	}
	public function getAllStats() {
		$users = User::whereHas('roles', function($q) {
		    $q->where('name', 'hacker');
		})->with('application','application.school')->get();
		$gender = array();
		$grad_year = array();
		$school = array();
		$completed = array(0, 0);
		foreach($users as $user) {
			if($user->application->completed) {
				if(!isset($gender[$user->application->gender])) {
					$gender[$user->application->gender] = 1;
				}
				else {
					$gender[$user->gender]++;
				}

				if(!isset($grad_year[$user->application->grad_year])) {
					$grad_year[$user->application->grad_year] = 1;
				}
				else {
					$grad_year[$user->application->grad_year]++;
				}
				if(!isset($school[$user->application->school])) {
					$school[$user->application->school] = 1;
				}
				else {
					$school[$user->application->school]++;
				}
				$completed[1]++;
			}
			else {
				$completed[0]++;
			}
		}
		var_dump($gender);
		var_dump($grad_year);
		// var_dump($school);
		var_dump($completed);
	}

	public function getStatsBySchool() {
		
	}
}
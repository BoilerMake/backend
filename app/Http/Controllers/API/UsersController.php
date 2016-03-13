<?php namespace App\Http\Controllers\API;

use App\Models\User;
use App\Http\Controllers\Controller;
use Auth;
use Validator;
use Illuminate\Http\Request;
use App\Models\Application;
use App\Models\Team;
use Input;
use Log;
use Carbon\Carbon;
class UsersController extends Controller {

	public function __construct() {
       // Apply the jwt.auth middleware to all methods in this controller
       // Except allows for fine grain exclusion if necessary
       $this->middleware('jwt.auth', ['except' => []]);
	}

	// Example method that is automatically authenticated by middleware
	public function getAttributes() {
		 return Auth::user()->getAttributes();
		//return Auth::user()->application->toArray();
	}
	public function updateMe(Request $request)
	{
		Log::info($request);
		$user = Auth::user();
		$data = $request->all();
		foreach($data as $key => $value)
		{
			//update the user info
			if(in_array($key,['email','first_name','first_name','phone']))
			{
				$user->$key=$value;
				$user->save();
			}
		}
		if(isset($data['application']))
		{
			//update the application
			$application = self::getApplication();
			foreach ($data['application'] as $key => $value) {
				if(in_array($key,['age','grad_year', 'gender','major','diet','diet_restrictions','tshirt','github','essay1','essay2','school_id']))
				{
					$application->$key=$value;
				}
				if($key=="team_code")
				{
					$team = Team::where("code",$value)->get()->first();
					if($team)//todo return status of this
						$application->team_id=$team->id;
				}
				if($key=="school")
				{
					$application->school_id=$value['id'];
				}
			}
			$application->save();
		}

	}

	public function getApplication()
	{
		//todo: only send along the application if they are a hacker!
		$application = Application::firstOrCreate(['user_id' => Auth::user()->id]);
		if(!$application->team_id)
		{
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
	public function leaveCurrentTeam()
	{
		$app = self::getApplication();
		$old_team_id = $app->team_id;
		$app->team_id=null;
		$app->save();
		if(Application::where("team_id",$old_team_id)->get()->count()==0)//we don't want empty teams
			Team::find($old_team_id)->delete();
		return ['ok'];
	}
	// public function updateApplication(Request $request) {
	// 	$validator = Validator::make($request->all(), [
 //            'age' => 'integer|min:0|max:255',
 //            'gender' => 'string',
 //            'major' => 'string',
 //            'grad_year' => 'integer', // should be string
 //            'diet' => 'string|max:255',
 //            'diet_restrictions' => 'string',
 //            'tshirt' => 'integer|min:0|max:255',
 //            'phone' => 'string|max:255',

 //        ]);

 //        if ($validator->fails()) {
 //        	// uh oh
 //        }
 //        else {
	// 		$application = Application::firstOrCreate(['user_id' => Auth::user()->id]);
	// 		$application->user_id = Auth::user()->id;
	// 		$application->age = $request->input('age');
	// 		$application->gender = $request->input('gender');
	// 		$application->major = $request->input('major');
	// 		$application->grad_year = $request->input('grad_year');
	// 		$application->diet = $request->input('diet');
	// 		$application->diet_restrictions = $request->input('diet_restrictions');
	// 		$application->tshirt = $request->input('tshirt');
	// 		$application->phone = $request->input('phone');
	// 		$application->save();
	// 	}
	// }
}
<?php namespace App\Http\Controllers\API;

use App\Models\PasswordReset;
use App\Models\User;
use App\Http\Controllers\Controller;
use App\Services\Notifier;
use Auth;
use Validator;
use Illuminate\Http\Request;
use App\Models\Application;
use App\Models\Team;
use Input;
use Log;
use Carbon\Carbon;
use AWS;
class UsersController extends Controller {

	public function __construct() {
       // Apply the jwt.auth middleware to all methods in this controller
       // Except allows for fine grain exclusion if necessary
       $this->middleware('jwt.auth', ['except' => ['sendPasswordReset','performPasswordReset']]);
	}
	public function getMe() {
		 return Auth::user();
	}
	public function updateMe(Request $request)
	{
		$user = Auth::user();
		$data = $request->all();
		foreach($data as $key => $value)
		{
			//update the user info
			if(in_array($key,['email','first_name','last_name','phone']))
			{
				$user->$key=$value;
				$user->save();
				Log::info($user);
			}
		}
		$hasApplication = false;
		if(isset($data['application']))
		{
			$hasApplication=true;
			//update the application
			$application = self::getApplication()['application'];
			foreach ($data['application'] as $key => $value) {
				if(in_array($key,['age','grad_year', 'gender','major','diet',
					'diet_restrictions','tshirt','github','essay1','essay2',
					'resume_filename','resume_uploaded','travellingFrom', 'isTravellingFromSchool']))
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
					if(isset($value['id']))
						$application->school_id=$value['id'];
					else
						$application->school_id=NULL;
				}
			}
			$application->save();
		}
		if($hasApplication)
		{
			return [
				'application'=>$application,
				'validation'=>$application->validationDetails(),
				'phase'=>intval(getenv('APP_PHASE')),
				'status'=>'ok',
			];
		}
		return ['status'=>'ok'];

	}

	public function getApplication()
	{
		$user = Auth::user();
		if(!Auth::user()->hasRole('hacker'))//TODO middleware perhaps?
			return;
		$application = $user->getApplication();
		
		$phase = intval(getenv('APP_PHASE'));
		if($phase < 3) //don't reveal decisions early
			$application->setHidden(['decision']);
		return [
			'application'=>$application,
			'validation'=>$application->validationDetails(),
			'phase'=>$phase,
			'teamsEnabled'=> (getenv('TEAMS') === 'true')
		];

	}
	public function getResumePutUrl()
	{
		$user = Auth::user();
        return GeneralController::resumeUrl($user->id,'put');
	}
	public function leaveCurrentTeam()
	{
		$app = self::getApplication()['application'];
		$old_team_id = $app->team_id;
		$app->team_id=null;
		$app->save();
		if(Application::where("team_id",$old_team_id)->get()->count()==0)//we don't want empty teams
			Team::find($old_team_id)->delete();
		return ['ok'];
	}

	public function sendPasswordReset(Request $request)
	{
		$email = $request->email;
		$user = User::where('email',$email)->first();
		if(!$user)
			return('No user '.$email);
		$user->sendPasswordResetEmail();
		return 'ok';
	}
	public function performPasswordReset(Request $request)
	{
		$token = $request->token;
		$password = $request->password;

		$reset = PasswordReset::where('token',$token)->first();
		if(!$reset)
			return 'oops';
		if(Carbon::parse($reset->created_at)->addHour(48)->lte(Carbon::now()))
			return 'expired';
		if($reset->is_used)
			return 'already used';
		$user = User::find($reset->user_id);
		$user->password = bcrypt($password);
		$user->save();

		$reset->is_used = true;
		$reset->save();
		return 'ok';
	}
}
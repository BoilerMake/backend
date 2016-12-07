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
use App\Models\PuzzleProgress;
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
			}
		}
		$hasApplication = false;
		if(isset($data['application']))
		{
			$hasApplication=true;
			//update the application
			$application = self::getApplication()['application'];
			foreach ($data['application'] as $key => $value) {
				if(in_array($key,['age','grad_year', 'gender','major','diet','diet_restrictions','github','race',
					'resume_filename','resume_uploaded','needsTravelReimbursement', 'isFirstHackathon','has_no_github']))
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
			'teamsEnabled'=> (getenv('TEAMS') === 'true'),
            'resume_view_url'=>$application->resume_uploaded? GeneralController::resumeUrl($application->user->id,'get') : null

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
		$validator = Validator::make($request->all(), [
		    'email' => 'required|email|exists:users,email',
		]);
		if ($validator->fails()) {
			return ['message' => 'error', 'errors' => $validator->errors()->all()];
		}
		$user = User::where('email', $request->email)->first();
		$user->sendPasswordResetEmail();
		return ['message' => 'success'];
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
	public function completePuzzle(Request $request) {
        if(!Auth::user())
            return ['auth plz'];
        $puzzle_id = intval($request->get('puzzle_id'));
        if(!$puzzle_id)
        	return ['puzzle id null'];
        $user_id = Auth::user()->id;

        if($request->get('puzzle_secret')!=env('PUZZLE_SECRET'))
            return ['bad puzzle secret'];

        if(PuzzleProgress::where('user_id',$user_id)->where('puzzle_id',$puzzle_id)->exists())
        	return ['dup'];

        $r = new PuzzleProgress();
        $r->user_id = $user_id;
        $r->puzzle_id = $puzzle_id;
        $r->save();
        return ['ok'];
    }
    public function getCompletedPuzzleIDs(Request $request) {
        $user_id = Auth::user()->id;
        $ids = PuzzleProgress::where('user_id',$user_id)->get()->lists('puzzle_id');
        return ['puzzles'=>$ids];
    }
}
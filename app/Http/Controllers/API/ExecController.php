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
use App\Models\Role;
use App\Models\ApplicationRanking;
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
		return $users;
	}
	public function getNextApplicationID()
	{
		$user = Auth::user();
		foreach(Application::orderBy(DB::raw('RAND()'))->get() as $app)
		{
			if($app->completed)
				if($app->reviews < 3)
					if(!ApplicationRanking::where('application_id',$app->id)->where('user_id',$user->id)->first())
						return($app->id);
		}
		return null;

	}
	public function getApplication($id)
	{
		$user = Auth::user();
		if(!Auth::user()->hasRole('exec'))//TODO middleware perhaps?
			return;
		$app = Application::with('user','school')->find($id);
		$app->myrating = ApplicationRanking::where('application_id',$id)->where('user_id',$user->id)->first();
		return $app;
	}
	public function rateApplication(Request $request, $id)
	{
		$user = Auth::user();
		$rating = $request->all()['rating'];
		$ranking = ApplicationRanking::firstOrNew(['application_id'=>intval($id),'user_id'=>$user->id]);
		$ranking->application_id =intval($id);
		$ranking->user_id =$user->id;
		$ranking->rating =$rating;
		$ranking->save();
		return ['next'=>self::getNextApplicationID()];
	}
}
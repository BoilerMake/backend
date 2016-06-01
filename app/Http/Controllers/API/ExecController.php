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
use App\Models\Event;
use App\Models\ApplicationRating;
use AWS;
use Carbon\Carbon;
use \Eluceo\iCal\Component\Calendar;

class ExecController extends Controller {

	public function __construct() {
       $this->middleware('jwt.auth', ['except' => ['generateCalendar']]);
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
	public function getHackersBulk(Request $request)
	{
		$ids = $request->all();
		if(!Auth::user()->hasRole('exec'))//TODO middleware perhaps?
			return;
		$users = User::whereHas('roles', function($q) use ($ids)
		{
		    $q->where('name', 'hacker')->whereIn('id',$ids);
		})->with('application','application.school')->get();
		foreach ($users as $user) {
			$user['application']['rating_info']=$user->application->ratingInfo();
		}
		return $users;
	}
	public function putHackersBulk(Request $request)
	{
		$ids = $request->all()['hackers'];
		$decision = $request->all()['decision'];
		if(!Auth::user()->hasRole('exec'))//TODO middleware perhaps?
			return;
		$users = User::whereHas('roles', function($q) use ($ids)
		{
		    $q->where('name', 'hacker')->whereIn('id',$ids);
		})->with('application','application.school')->get();
		foreach ($users as $user) {
			$user->application->decision=$decision;
			$user->application->save();
		}
		return $users;
	}
	public function getUsers() {
        $users = User::all();
        foreach ($users as $eachUser) {
            $eachUser->roles = $eachUser->roles()->lists('name');
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

		//todo: move this s3 thing elsewhere
		$s3 = AWS::createClient('s3');
        $cmd = $s3->getCommand('getObject', [
            'Bucket' => getenv('S3_BUCKET'),
            'Key'    => 'r/'.$app->user->id.'.pdf',
            'ResponseContentType' => 'application/pdf'
        ]);
        $request = $s3->createPresignedRequest($cmd, '+1 day');
		$app->resumeURL = (string) $request->getUri();
		$app->myrating = ApplicationRating::where('application_id',$id)->where('user_id',$user->id)->first();
        $app->github_summary = $app->getGithubSummary();
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
			$team['hackers_detail']=$team->getHackersWithRating();
			$hackerRatings=[];
			$ratingSum=0;
			$ratingCount=0;
			foreach ($team['hackers_detail'] as $eachHackerDetail) {
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

    public function createEvent(Request $request) {
    	$validator = Validator::make($request->all(), [
            'title' => 'required|string',
            'description' => 'required|string',
            'begin' => 'required|integer',
            'end' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return $validator->errors()->all();
        }
        if($request->end < $request->begin) {
        	return "invalid time";
        }
		
        $event = new Event;
        $event->title = $request->title;
        $event->description = $request->description;
        // unnecessary, should just be forcing api to datetime
        $event->begin = Carbon::createFromTimestamp($request->begin, 'America/New_York')->toDateTimeString();  
        $event->end = Carbon::createFromTimestamp($request->end, 'America/New_York')->toDateTimeString();  
        $event->save();
        return "success";
    }

    // probably can be merged with create
    public function editEvent(Request $request) {
    	$validator = Validator::make($request->all(), [
            'title' => 'required|string',
            'description' => 'required|string',
            'begin' => 'required|integer',
            'end' => 'required|integer',
            'event_id' => 'required|exists:events,id',
        ]);

        if ($validator->fails()) {
            return $validator->errors()->all();
        }
        if($request->end < $request->begin) {
        	return "invalid time";
        }
        
		$event = Event::where('id', '=', $request->event_id);
        if($event->count()) {
        	$event = $event->first();
        }
        else {
        	return "invalid event";
        }
        
        $event->title = $request->title;
        $event->description = $request->description;
        $event->begin = Carbon::createFromTimestamp($request->begin, 'America/New_York')->toDateTimeString();
        $event->end = Carbon::createFromTimestamp($request->end, 'America/New_York')->toDateTimeString();
        $event->save();
        return "success";
    }

    public function deleteEvent(Request $request) {
		$validator = Validator::make($request->all(), [
            'event_id' => 'required|exists:events,id',
        ]);

        if ($validator->fails()) {
            return $validator->errors()->all();
        }

        $event = Event::where('id', '=', $request->event_id);
        if($event->count()) {
        	$event = $event->first();
        }
        else {
        	return "invalid event";
        }
        
        $event = Event::where('id', '=', $request->event_id)->first()->delete();
        return "success";
    }

    public function generateCalendar(Request $request) {
    	$vCalendar = new Calendar('www.boilermake.org');
    	$events = Event::all();
        // Iterate through all events
        foreach($events as $event) {
            $vEvent = new \Eluceo\iCal\Component\Event();
            $vEvent
                ->setDtStart(new \DateTime($event->begin))
                ->setDtEnd(new \DateTime($event->end))
                ->setNoTime(true)
                ->setSummary($event->title);
            $vCalendar->addComponent($vEvent);
        }
        
        // Headers that might not actually do anything
		header( 'Expires: Sat, 26 Jul 1997 05:00:00 GMT' ); //date in the past
		header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT' ); //tell it we just updated
		header( 'Cache-Control: no-store, no-cache, must-revalidate' ); //force revaidation
		header( 'Cache-Control: post-check=0, pre-check=0', false );
		header( 'Pragma: no-cache' );

        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="cal.ics"');
        echo $vCalendar->render();
    }
}
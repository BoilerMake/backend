<?php namespace App\Http\Controllers\API;

use App\Models\AnalyticsEvent;
use App\Models\ApplicationNote;
use App\Models\GroupMessage;
use App\Models\InterestSignup;
use App\Models\User;
use App\Http\Controllers\Controller;
use App\Services\Notifier;
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
       // $this->middleware('jwt.auth', ['except' => ['generateCalendar']]);
	}
    public function getInterestData() {
        return InterestSignup::all();
    }
	public function getHackers() {
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
	public function getUser($id)
	{
		$user = User::find($id);
		$application = null;
		if($user->hasRole('hacker'))
			$application=$user->getApplication(true);
		return [
			'user'=>$user,
			'application'=>$application,
			'roles'=>$user->roles()->lists('name'),
			'isHacker'=>$user->hasRole('hacker'),
			];
	}
	public function getUserAnalytics($id)
	{
		$events = AnalyticsEvent::where('user_id',$id)->get();
		return $events;
	}
	public function doAction(Request $request, $id)
	{
		$user = User::find($id);
		switch ($request->action) {
			case "password-reset":
				$user->sendPasswordResetEmail();
				return ['status'=>'ok'];
				break;
			case "check-in":
				//todo
				return ['status'=>'error','message'=>'todo'];
				break;
		}
	}
	public function getGroupMessages()
	{
		return GroupMessage::all();
	}
	public function sendGroupMessage(Request $request)
	{
		switch ($request->group) {
			case "all":
				$roles = ['exec','hacker','sponsor'];
				break;
			case "hackers":
				$roles = ['exec','hacker'];
				break;
			case "sponsors":
				$roles = ['exec','sponsor'];
				break;
			case "exec":
				$roles = ['exec'];
				break;
		}
		$users = User::whereHas('roles', function($q) use($roles)
		{
			$q->whereIn('name', $roles);
		})->get();

		foreach($users as $u)
		{
			$n = new Notifier($u);
			$n->sendSMS($request->message,'group-message');
		}
		$log = new GroupMessage();
		$log->group = $request->group;
		$log->message = $request->message;
		$log->num_recipients = $users->count();
		$log->save();
		return ['status'=>'ok','message'=>'message sent to'.$users->count().'users'];
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
		$app = Application::with('user','school','team','notes.user')->find($id);
        
        
		$app->resumeURL = GeneralController::resumeUrl($app->user->id,'get');
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
    public function addApplicationNote(Request $request, $application_id)
    {
        $note  = new ApplicationNote();
        $note->application_id =intval($application_id);
        $note->user_id =Auth::user()->id;
        $note->message =$request->message;
        $note->save();
        return 'ok';
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
		$gender = [];
		$race = [];
		$major = [];
		$grad_year = [];
		$school = [];
        $travel = [];
        $firstHackathon = [];
		$completed = ['yes'=>0,'no'=>0];
		foreach($users as $user) {
			if($user->application->completed) {
			    //travel
				if(!isset($travel[$user->application->needsTravelReimbursement])) {
                    $travel[$user->application->needsTravelReimbursement] = 1;
				}
				else {
                    $travel[$user->application->needsTravelReimbursement]++;
				}

                //first hackathon
                if(!isset($firstHackathon[$user->application->isFirstHackathon])) {
                    $firstHackathon[$user->application->isFirstHackathon] = 1;
                }
                else {
                    $firstHackathon[$user->application->isFirstHackathon]++;
                }

                //gender
                if(!isset($gender[$user->application->gender])) {
                    $gender[$user->application->gender] = 1;
                }
                else {
                    $gender[$user->application->gender]++;
                }

                //race
                if(!isset($race[$user->application->race])) {
                    $race[$user->application->race] = 1;
                }
                else {
                    $race[$user->application->race]++;
                }

                //grad year
				if(!isset($grad_year[$user->application->grad_year])) {
					$grad_year[$user->application->grad_year] = 1;
				}
				else {
					$grad_year[$user->application->grad_year]++;
				}

                //major
                if(!isset($major[$user->application->major])) {
                    $major[$user->application->major] = 1;
                }
                else {
                    $major[$user->application->major]++;
                }


				if(!isset($school[$user->application->school->id]['counts']['complete'])) {
					$school[$user->application->school->id]['counts']['complete'] = 1;
					$school[$user->application->school->id]['school'] = $user->application->school->name;
				}
				else {
					$school[$user->application->school->id]['counts']['complete']+=1;
				}
				$completed["yes"]++;
			}
			else {
			    if(isset($user->application->school))
                {
                    if(!isset($school[$user->application->school->id]['counts']['incomplete'])) {
                        $school[$user->application->school->id]['counts']['incomplete'] = 1;
                        $school[$user->application->school->id]['school'] = $user->application->school->name;
                    }
                    else {
                        $school[$user->application->school->id]['counts']['incomplete']+=1;
                    }
                }
                else
                {
                    if(!isset($school["none"]['counts']['incomplete'])) {
                        $school["none"]['counts']['incomplete'] = 1;
                        $school["none"]['school'] ="none";
                    }
                    else
                        $school["none"]['counts']['incomplete']+=1;
                }
				$completed["no"]++;
			}
		}
		 return([
		     'first_hackathon'=>$firstHackathon,
		     'travel'=>$travel,
		     'by_school'=>$school,
		     'gender'=>$gender,
		     'major'=>$major,
		     'race'=>$race,
		     'grad_year'=>$grad_year,
             'total'=>$completed,
             'num_schools'=>sizeof($school)
         ]
         );
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
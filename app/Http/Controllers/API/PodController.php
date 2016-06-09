<?php

namespace app\Http\Controllers\API;
use App\Models\Pod;
use App\Models\Event;
use App\Models\PodScan;
use App\Models\User;
use Illuminate\Http\Request;
use Log;
use Auth;
use App\Http\Controllers\Controller;

class PodController extends Controller
{
    public function __construct() {
        $this->middleware('jwt.auth',['except' => ['scan', 'heartbeat']]);
    }
    public function scan(Request $request)
    {  
        if($request->pod_key != env('PODPOD_KEY'))
            return ['success'=>false, "message"=>"auth error"];
        $pod = Pod::find($request->pod_id);
        if(!$pod)
            return ['success'=>false, "message"=>'error with pod_id'];

        $scan = new PodScan();
        $scan->ip = $request->ip();
        $scan->pod_id = $pod->id;
        $scan->event_id = $pod->current_event_id;
        if($pod->current_event_id==NULL) {
            //pod is not assigned to an event
            $scan->success = false;
            //todo: check if the event is 'active'
            $scan->message = "this pod is currently not assigned to an event";
            $scan->save();
            return $scan;
        }
        $event = Event::find($scan->event_id);


        $scan->input = $request->code;
        //todo: robustness in case this doesnt exist
        $user = User::where('identifier',$request->code)->first();
        if(!$user)
        {
            //can't find a user behidn the code
            //todo: robustness
            $scan->success = false;
            $scan->message = "user id not valid!";
            $scan->save();
            return $scan;
        }
        //todo: see if the user has already scanned for this event
        $scan->user_id = $user->id;

        Log::info("[POD] processed pod scan from pod ".$pod->id." for event ".$event->name." (#".$event->id.") from user ".$user->slug()." @ ".$request->ip());

        $scan->message = "ok";
        $scan->success = true;
        $scan->save();
        return $scan;
    }
    public function listPods()
    {
        if(!Auth::user()->hasRole('exec'))//TODO middleware perhaps?
            return 'not authorized';
        //todo: filter by successful scans??
        $pods = Pod::with('event','scans','scans.user')->get();
        foreach($pods as $pod) {
            if($pod->updated_at->addSeconds(140) > \Carbon\Carbon::now()) {
                $pod->status = true;
            }
            else {
                $pod->status = false;
            }
        }
        return $pods;
    }
    public function listEvents()
    {
        if(!Auth::user()->hasRole('exec'))//TODO middleware perhaps?
            return 'not authorized';
        //todo: filter by successful scans??
        $events = Event::with('active_pods','scans','scans.user')->get();
        return $events;
    }
    public function listScans()
    {
        if(!Auth::user()->hasRole('exec'))//TODO middleware perhaps?
            return 'not authorized';
        $scans = PodScan::with('user','pod','event')->get();
        return $scans;
    }
    public function heartbeat(Request $request) {
        if($request->pod_key != env('PODPOD_KEY'))
            return "auth error";
        $pod = Pod::find($request->pod_id);
        if(!$pod)
            return 'error with pod_id';
        $pod->touch();
        //TODO: Change this return value on pod status so we can control scanning execution
        return 1;
    }
}
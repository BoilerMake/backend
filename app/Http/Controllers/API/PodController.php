<?php

namespace app\Http\Controllers\API;
use App\Models\Pod;
use App\Models\PodEvent;
use App\Models\PodScan;
use Illuminate\Http\Request;
use Log;
use Auth;
use App\Http\Controllers\Controller;

class PodController extends Controller
{
    public function __construct() {
        $this->middleware('jwt.auth', ['except' => ['scan']]);
    }
    public function scan(Request $request)
    {
        if($request->pod_key != env('PODPOD_KEY'))
            return "auth error";
        $pod = Pod::find($request->pod_id);
        if(!$pod)
            return 'error with pod_id';

        $scan = new PodScan();
        $scan->success = true;
        $message = "ok";


        $scan->pod_id = $pod->id;
        $scan->pod_event_id = $pod->current_pod_event_id;
        if($pod->current_pod_event_id==NULL) {
            $scan->success = false;
            $message="this pod is currently not assigned to an event";
            //todo: check if the event is 'active'
        }
        $scan->input = $request->code;

        //todo: $scan->user_id = User::where('code',$code)->first();
        //todo: see if the user has already scanned for this event

        $scan->message = $message;
        $scan->save();
        return $scan;
    }
    public function listPods()
    {
        if(!Auth::user()->hasRole('exec'))//TODO middleware perhaps?
            return 'not authorized';
        //todo: filter by successful scans??
        $pods = Pod::with('event','scans','scans.user')->get();
        return $pods;
    }
    public function listEvents()
    {
        if(!Auth::user()->hasRole('exec'))//TODO middleware perhaps?
            return 'not authorized';
        //todo: filter by successful scans??
        $events = PodEvent::with('active_pods','scans','scans.user')->get();
        return $events;
    }
    public function listScans()
    {
        if(!Auth::user()->hasRole('exec'))//TODO middleware perhaps?
            return 'not authorized';
        $scans = PodScan::with('user','pod','event')->get();
        return $scans;
    }
}
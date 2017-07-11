<?php

namespace App\Http\Controllers;

use Log;
use Auth;
use JWTAuth;
use App\Models\Pod;
use App\Models\User;
use App\Models\Event;
use App\Models\PodScan;
use Illuminate\Http\Request;

class PodController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth', ['except' => ['scan', 'heartbeat']]);
    }

    public function scan(Request $request)
    {
        $isAdmin = false;
        try {
            $exec = JWTAuth::parseToken()->toUser();
        } catch (\Exception $e) {
            $exec = null;
        }
        if ($exec) {
            $isExecAuthValid = $exec->hasRole('exec');
        }
        $isPodAuthValid = $request->pod_key == env('PODPOD_KEY');
        if (! $isPodAuthValid && ! $isExecAuthValid) {//allow authorized pod clients OR execs
            return ['success'=>false, 'message'=>'auth error'];
        }

        $pod = Pod::find($request->pod_id);
        if (! $pod) {
            return ['success'=>false, 'message'=>'error with pod_id'];
        }

        $scan = new PodScan();
        $scan->ip = $request->ip();
        $scan->pod_id = $pod->id;
        $scan->event_id = $pod->current_event_id;
        $scan->message = 'ok'; //default to good
        $scan->success = true; //default to good
        if ($pod->current_event_id == null) {
            //pod is not assigned to an event
            $scan->success = false;
            //todo: check if the event is 'active'
            $scan->message = 'this pod is currently not assigned to an event';
            //save input regardless
            $scan->input = $request->code;
            $scan->save();

            return $scan;
        }
        $event = Event::find($scan->event_id);

        if ($request->code == null || ! isset($request->code)) {
            //todo: robustness in case this doesnt exist
            $scan->success = false;
            $scan->message = 'something wrong with the code!';
            $scan->save();

            return $scan;
        }
        $scan->input = $request->code;

        $user = User::where('card_code', $request->code)->first();
        if (! $user) {
            //this would really only happen if someone is trying to hack us!
            //can't find a user behind the code
            //todo: robustness
            $scan->success = false;
            $scan->message = 'user id not valid!';
            $scan->save();

            return $scan;
        }
        //todo: see if the user has already scanned for this event
        $scan->user_id = $user->id;

        $scan->message = 'processed pod scan from pod: '.$pod->name.' (#'.$pod->id.') for event: '.$event->title.' (#'.$event->id.') from user: '.$user->slug().' @ '.$request->ip();
        Log::info('PodScan', ['message'=>$scan->message]);
        $scan->save();

        return $scan;
    }

    public function listPods()
    {
        if (! Auth::user()->hasRole('exec')) {//TODO middleware perhaps?
            return 'not authorized';
        }
        //todo: filter by successful scans??
        $pods = Pod::with('event', 'scans', 'scans.user')->get();
        foreach ($pods as $pod) {
            if ($pod->updated_at->addSeconds(140) > \Carbon\Carbon::now()) {
                $pod->status = true;
            } else {
                $pod->status = false;
            }
        }

        return $pods;
    }

    public function listEvents()
    {
        if (! Auth::user()->hasRole('exec')) {//TODO middleware perhaps?
            return 'not authorized';
        }
        //todo: filter by successful scans??
        $events = Event::with('active_pods', 'scans', 'scans.user')->get();

        return $events;
    }

    public function listScans()
    {
        if (! Auth::user()->hasRole('exec')) {//TODO middleware perhaps?
            return 'not authorized';
        }
        $scans = PodScan::with('user', 'pod', 'event')->get();

        return $scans;
    }

    public function heartbeat(Request $request)
    {
        if ($request->pod_key != env('PODPOD_KEY')) {
            return ['success'=> false, 'message'=> 'invalid auth'];
        }
        $pod = Pod::find($request->pod_id);
        if (! $pod) {
            return ['success'=> false, 'message'=> 'could not find pod'];
        }

        $pod->ip = $request->ip_addr;
        $pod->save();

        //TODO: Change this return value on pod status so we can control scanning execution
        return ['success'=> true, 'message'=> 'heartbeat received '.$pod->updated_at];
    }
}

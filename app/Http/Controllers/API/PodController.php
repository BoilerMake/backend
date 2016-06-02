<?php

namespace app\Http\Controllers\API;
use App\Models\Pod;
use App\Models\PodScan;
use Illuminate\Http\Request;
use Log;
use App\Http\Controllers\Controller;

class PodController extends Controller
{
    public function scan(Request $request)
    {
        if($request->pod_token!=env('POD_TOKEN'))
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
}
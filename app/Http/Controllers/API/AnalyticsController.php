<?php

namespace app\Http\Controllers\API;
use App\Models\AnalyticsEvent;
use App\Models\User;
use Illuminate\Http\Request;
use Log;
use Auth;
use JWTAuth;
use App\Http\Controllers\Controller;

class AnalyticsController extends Controller
{
    public function event(Request $request)
    {  
        Log::info($request);

        try {
            $user_id = JWTAuth::parseToken()->toUser()->id;
        }
        catch (\Exception $e)
        {
            $user_id = null;
        }
        $e = new AnalyticsEvent();
        $e->user_id = $user_id;
        $e->name = $request->name;
        if(isset($request->meta['referrer']))
            $e->referrer = $request->meta['referrer'];
        $e->ip = $request->ip();
        if(isset($request->meta['client']))
            $e->client = $request->meta['client'];
        $e->params = json_encode($request->params);
        $e->save();
        return 'pk';
    }
}
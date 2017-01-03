<?php

namespace app\Http\Controllers\API;

use Log;
use JWTAuth;
use Illuminate\Http\Request;
use App\Models\AnalyticsEvent;
use App\Http\Controllers\Controller;

class AnalyticsController extends Controller
{
    public function event(Request $request)
    {
        try {
            $user_id = JWTAuth::parseToken()->toUser()->id;
        } catch (\Exception $e) {
            $user_id = null;
        }
        $meta = $request->meta;
        $url = $request->url;

        $meta['ip'] = $request->ip();
        if (isset($request->meta['ip'])) {//override grabbing ip from request
            $meta['ip'] = $request->meta['ip'];
        }
        self::log($user_id, $request->name, $request->params, $meta, $url);
    }

    public static function log($user_id, $event, $params = null, $meta = null, $url = null)
    {
        $e = new AnalyticsEvent();
        $e->user_id = $user_id;
        $e->name = $event;
        if (isset($meta['referrer'])) {
            $e->referrer = $meta['referrer'];
        }

        if (isset($meta['ip'])) {
            $e->ip = $meta['ip'];
        }

        if (isset($meta['client'])) {
            $e->client = $meta['client'];
        }
        if (isset($meta['url'])) {
            $e->url = $meta['url'];
        }
        if (isset($meta['ua'])) {
            $e->ua = $meta['ua'];
        }
        $e->params = json_encode($params);
        $e->save();

        return 'ok';
    }
}

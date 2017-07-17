<?php

namespace App\Http\Controllers;

use App;
use Log;
use JWTAuth;
use Request;
use App\Models\User;
use App\Models\Event;
use App\Models\School;
use App\Models\PodScan;
use App\Models\UserStat;
use App\Models\GithubEvent;
use App\Models\Announcement;
use App\Models\InboundMessage;
use App\Models\InterestSignup;

class GeneralController extends Controller
{
    /**
     * GET /ping
     *
     * Heartbeat endpoint.
     * @return array
     */
    public function ping()
    {
        return response()->success(['pong']);
    }

    /**
     * GET /
     *
     * @return mixed
     */
    public function info()
    {
        return response()->success([
            'name'=>'BoilerMake API',
            'frontend'=>env('FRONTEND_ADDRESS'),
            'info'=>'http://github.com/BoilerMake',
            'docs'=>env('APP_URL').'/docs',
        ]);
    }

    /**
     * POST /stats
     *
     * Logs a user stat event.
     * @return \Response
     */
    public function createUserStat()
    {
        try {
            $user_id = JWTAuth::parseToken()->toUser()->id;
        } catch (\Exception $e) {
            $user_id = null;
        }

        $eventName = Request::get('event');
        $subtitle = Request::get('subtitle');
        $client = Request::get('client');
        $uuid = Request::header('x-uuid');

        $stat = UserStat::create([
            'user_id'           => $user_id,
            'event'             => $eventName,
            'subtitle'          => $subtitle,
            'context'           => Request::get('context'),
            'uuid'              => $uuid,
            'client_ip'         => Request::ip(),
            'client_useragent'  => Request::header('user-agent'),
            'client_referer'    => Request::header('referer'),
        ]);
        $shouldLog = (App::environment() == 'production') || env('SHOW_EXTRA_LOGS_DEV');
        if ($shouldLog) {
            Log::info('UserStatRecorded', [
                'user_id' => $user_id,
                'event' => $eventName,
                'subtitle' => $subtitle,
                'uuid' => $uuid,
                'client' => $client,
                'id' => $stat->id,
            ]);
        }

        return response()->success($stat);
    }

    /**
     * GET /schools
     * @param Request $request
     * @return mixed
     */
    public function getSchools()
    {
        $filter = Request::get('filter');
        if (! $filter) {
            $filter = '';
        }
        $locs = School::where('name', 'like', '%'.$filter.'%')->orWhere('name', 'Other/School not listed')->get();

        return response()->success($locs);
    }

    /**
     * Handles an incoming SMS from twillio.
     * @deprecated
     * @codeCoverageIgnore
     */
    public function inboundSMS()
    {
        $input = Request::all();

        $phone = $input['From'];
        $user_id = null;
        //try to match it to user we have
        $user = User::where('phone', 'LIKE', '%'.$phone.'%')->get()->first();
        if ($user) {
            $user_id = $user->id;
        }
        $n = new InboundMessage();
        $n->user_id = $user_id;
        $n->raw = json_encode($input);
        $n->number = $phone;
        $n->message = $input['Body'];
        $n->save();
    }

    /**
     * POST interest/signup
     *
     * @return mixed
     */
    public function interestSignup()
    {
        $email = Request::get('email');
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Log::info('InterestSignup Fail');
            return response()->error('email is not valid!');
        }

        $signup = InterestSignup::firstOrCreate(['email' => $email]);
        if ($signup->wasRecentlyCreated) {
            Log::info('InterestSignup Success');
            return response()->success('all signed up!');
        }

        return response()->error('you were already signed up!');
    }

    /*
     * GET /events
     */
    public function getEvents()
    {
        return response()->success(Event::where('hidden', 0)->orderBy('begin')->get(['id', 'title', 'description', 'begin', 'end']));
    }

    /*
     * GET /announcements
     */
    public function getAnnouncements()
    {
        return response()->success(Announcement::orderBy('created_at', 'DESC')->get());
    }

    /*
     * GET /activity
     * @codeCoverageIgnore
     */
    public function getActivity()
    {
        $github = [];
        foreach (GithubEvent::where('type', 'PushEvent')->with('user')->orderBy('timestamp', 'DESC')->get() as $push) {
            $github[] = [
                'id'        => $push->id,
                'message'   => $push->user->name.' pushed to '.$push->repo,
                'timestamp' => $push->timestamp
            ];
        }

        $podScans = [];
        foreach (PodScan::with('user', 'pod')->orderBy('created_at', 'DESC')->get() as $scan) {
            if ($scan->user && $scan->pod) {
                $podScans[] = [
                    'id'        => $scan->id,
                    'message'   => $scan->user->name.' scanned at pod '.$scan->pod->name,
                    'timestamp' => $scan->created_at->toDateTimeString()
                ];
            }
        }

        return [
            'github'=>$github,
            'pods'=>$podScans
        ];
    }
}

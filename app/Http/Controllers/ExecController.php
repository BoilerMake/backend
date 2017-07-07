<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use Validator;
use Carbon\Carbon;
use App\Models\Pod;
use App\Models\User;
use App\Models\Event;
use App\Models\Application;
use App\Models\Announcement;
use Illuminate\Http\Request;
use App\Models\InterestSignup;
use App\Models\ApplicationNote;
use App\Models\ApplicationRating;
use Eluceo\iCal\Component\Calendar;

/**
 * Class ExecController.
 */
class ExecController extends Controller
{
    /**
     * @return array of the interest signup data
     */
    public function getInterestData()
    {
        return InterestSignup::all();
    }

    /**
     * @return array ofhackers
     */
    public function getHackers()
    {
        $users = User::whereHas('roles', function ($q) {
            $q->where('name', 'hacker');
        })->with('application', 'application.school')->get();
        foreach ($users as $user) {
            $user['application']['rating_info'] = $user->application->ratingInfo();
        }

        return $users;
    }

    public function getHackersBulk(Request $request)
    {
        $ids = $request->all();
        $users = User::whereHas('roles', function ($q) use ($ids) {
            $q->where('name', 'hacker')->whereIn('id', $ids);
        })->with('application', 'application.school')->get();
        foreach ($users as $user) {
            $user['application']['rating_info'] = $user->application->ratingInfo();
        }

        return $users;
    }

    public function putHackersBulk(Request $request)
    {
        $ids = $request->all()['hackers'];
        $decision = $request->all()['decision'];
        $users = User::whereHas('roles', function ($q) use ($ids) {
            $q->where('name', 'hacker')->whereIn('id', $ids);
        })->with('application', 'application.school')->get();
        foreach ($users as $user) {
            $user->application->decision = $decision;
            $user->application->save();
        }

        return $users;
    }

    /**
     * Gets all the users, for the exec UI.
     */
    public function getUsers()
    {
        $users = User::all();
        foreach ($users as $eachUser) {
            $eachUser->roles = $eachUser->roles()->pluck('name');
        }

        return $users;
    }

    /**
     * Gets the data of a user with exec info.
     * @param int $id
     * @return array for User with info
     */
    public function getUser($id)
    {
        $user = User::find($id);
        $application = null;
        if ($user->hasRole('hacker')) {
            $application = $user->getApplication(true);
        }

        return [
            'user'=>$user,
            'application'=>$application,
            'roles'=>$user->roles()->pluck('name'),
            'isHacker'=>$user->hasRole('hacker'),
            ];
    }

    public function doAction(Request $request, $id)
    {
        $user = User::find($id);
        switch ($request->action) {
            case 'password-reset':
                $user->sendPasswordResetEmail();

                return ['status'=>'ok'];
                break;
            case 'check-in':
                if (! $user->hasRole('hacker')) {
                    return ['status'=>'error', 'message'=>'not a hacker'];
                }
                $application = Application::where('user_id', $user->id)->first();
                if ($application->checked_in_at == null) {
                    $application->checked_in_at = Carbon::now();
                    $application->save();

                    return ['status' => 'ok', 'message' => 'ok'];
                }

                return ['status' => 'error', 'message' => 'already checked in'];
                break;
        }
    }

    /**
     * Adds an announcement.
     * @param Request $request
     * @return array status
     */
    public function addAnnouncement(Request $request)
    {
        $a = new Announcement();
        $a->body = $request->message;
        $a->sms = $request->sms || false;
        $a->slack = $request->slack || false;
        $a->email = $request->email || false;
        $a->important = $request->important || false;
        $a->save();
        $a->send();

        return ['ok'];
    }

    public function getNextApplicationID()
    {
        $user = Auth::user();
        foreach (Application::orderBy(DB::raw('RAND()'))->get() as $app) {
            //we must find the applications that are completed and have fewer than 3 reviews and that i didn't review
            if ($app->completed) {
                if ($app->reviews < 3) {
                    if (! ApplicationRating::where('application_id', $app->id)->where('user_id', $user->id)->first()) {
                        return $app->id;
                    }
                }
            }
        }
    }

    public function getApplication($id)
    {
        $user = Auth::user();
        $app = Application::with('user', 'school', 'team', 'notes.user')->find($id);

        $app->resumeURL = GeneralController::resumeUrl($app->user->id, 'get');
        $app->myrating = ApplicationRating::where('application_id', $id)->where('user_id', $user->id)->first();
        $app['validation'] = $app->validationDetails();
        $app->github_summary = $app->getGithubSummary();

        return $app;
    }

    public function rateApplication(Request $request, $id)
    {
        $user = Auth::user();
        $rating = $request->all()['rating'];
        $ranking = ApplicationRating::firstOrNew(['application_id'=>intval($id), 'user_id'=>$user->id]);
        $ranking->application_id = intval($id);
        $ranking->user_id = $user->id;
        $ranking->rating = $rating;
        $ranking->save();

        return ['next'=>self::getNextApplicationID()];
    }

    /**
     * @param Request $request
     * @param $application_id
     * @return string success
     */
    public function addApplicationNote(Request $request, $application_id)
    {
        $note = new ApplicationNote();
        $note->application_id = intval($application_id);
        $note->user_id = Auth::user()->id;
        $note->message = $request->message;
        $note->save();

        return 'ok';
    }

    /**
     * POst to create an event.
     * @param Request $request
     * @return array
     */
    public function createEvent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string',
            'description' => 'required|string',
            'begin' => 'required|string',
            'end' => 'required|string',
        ]);

        if ($validator->fails()) {
            return ['message' => 'validation', 'data' => $validator->errors()];
        }

        $begin = new Carbon($request->begin, 'America/New_York');
        $end = new Carbon($request->end, 'America/New_York');

        if ($end < $begin) {
            return ['message' => 'time error - is end before begin?'];
        }

        $event = new Event;
        $event->title = $request->title;
        $event->description = $request->description;
        $event->begin = $begin;
        $event->end = $end;
        $event->save();

        return ['message' => 'success'];
    }

    /**
     * PUT an event to update.
     * @param Request $request
     * @param Event $event
     * @return array success
     */
    public function editEvent(Request $request, Event $event)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'string',
            'description' => 'string',
            'begin' => 'required|string',
            'end' => 'required|string',
        ]);

        if ($validator->fails()) {
            return ['message' => 'validation', 'data' => $validator->errors()];
        }

        $begin = new Carbon($request->begin, 'America/New_York');
        $end = new Carbon($request->end, 'America/New_York');

        if ($end < $begin) {
            return ['message' => 'time error - is end before begin?'];
        }

        $event->description = $request->description;
        $event->begin = $begin;
        $event->end = $end;
        $event->save();

        return ['message' => 'success'];
    }

    /**
     * DELETE an event.
     * @param Request $request
     * @param Event $event
     * @return array success
     */
    public function deleteEvent(Request $request, Event $event)
    {
        if (Pod::where('current_event_id', $event->id)->exists()) {
            return ['message' => 'event_in_use'];
        }
        $event->delete();

        return ['message' => 'success'];
    }

    /**
     * Generates an ical calendar.
     * @param Request $request
     */
    public function generateCalendar(Request $request)
    {
        date_default_timezone_set('America/New_York');
        $vCalendar = new Calendar('www.boilermake.org');
        $events = Event::where('hidden', 0)->get();
        // Iterate through all events
        foreach ($events as $event) {
            $vEvent = new \Eluceo\iCal\Component\Event();
            $vEvent->setUseTimezone(true);
            $vEvent
                ->setDtStart(new \DateTime($event->begin))
                ->setDtEnd(new \DateTime($event->end))
                ->setNoTime(false)
                ->setSummary($event->title);
            $vCalendar->addComponent($vEvent);
        }

        // Headers that might not actually do anything
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); //date in the past
        header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); //tell it we just updated
        header('Cache-Control: no-store, no-cache, must-revalidate'); //force revaidation
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');

        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="cal.ics"');
        echo $vCalendar->render();
    }
}

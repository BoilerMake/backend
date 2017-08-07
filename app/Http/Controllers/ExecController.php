<?php

namespace App\Http\Controllers;

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

    public function dashboardData()
    {
        return response()->success([
            'interest_count' => InterestSignup::count(),
        ]);
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

        return response()->success($users);
    }

    /**
     * Gets all applications
     */
    public function getApplications()
    {
        return response()->success(Application::with('user', 'school')->get());
    }

    /**
     * Gets the data of a user with exec info.
     * @param int $id
     * @return array for User with info
     */
    public function getUser($id)
    {
        $user = User::with('audits')->find($id);
        $user->roles = $user->roles()->pluck('name');
        $app = Application::where('user_id', $id)->first();
        $user->application_id = $app ? $app->id : null;

        return response()->success($user);
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

    public function getApplication($id)
    {
        $application = Application::with('user', 'school', 'notes.user', 'audits')->find($id);
        foreach (Application::USER_FIELDS_TO_INJECT as $x) {
            $application[$x] = $application->user->$x;
        }
        $application->resumeURL = $application->user->resumeURL();
        $application->validationDetails = $application->validationDetails();
//        $app->github_summary = $app->getGithubSummary();
        return response()->success($application);
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

<?php

namespace App\Http\Controllers;

use Auth;
use Request;
use Validator;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Event;
use App\Models\Application;
use App\Models\Announcement;
use App\Models\InterestSignup;
use App\Models\ApplicationNote;

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
        $interestCount = InterestSignup::count() || 1;
        $interestEmails = InterestSignup::all()->pluck('email');
        $signupEmails = User::all()->pluck('email');
        $interestEmailsWhoHaveNotSignedUp = $interestEmails->intersect($signupEmails)->count();
        $percentOfInterestWhoApplied = ($interestCount - $interestEmailsWhoHaveNotSignedUp) / $interestCount * 100;

        $reasonsMap = [];
        foreach (Application::all() as $app) {
            foreach ($app->validationDetails()['reason_label'] as $reason) {
                $reasonsMap[$reason] = ! array_key_exists($reason, $reasonsMap) ? 1 : $reasonsMap[$reason] + 1;
            }
        }
        arsort($reasonsMap);

        return response()->success([
            'interest_count'           => $interestCount,
            'percent_interest_applied' => intval($percentOfInterestWhoApplied),
            'reasons_map'              => $reasonsMap,
        ]);
    }

    /**
     * Gets all the users, for the exec UI.
     */
    public function getUsers()
    {
        $users = User::with('application.school')->get();
        foreach ($users as $eachUser) {
            $eachUser->roles = $eachUser->roles()->pluck('name');
        }

        return response()->success($users);
    }

    /*
     * searches for user by either:
     * first OR last name, returning multiple results
     * hashid, returning an array result of length 1
     */
    public function searchUsers()
    {
        $data = json_decode(Request::getContent(), true);
        if ($data['hashid'] != '') {
            //if provided both, hashid takes precedence
            $user = User::getFromHashID($data['hashid']);
            if (isset($user)) {
                $userWithStuff = User::with('application.school')->find($user->id);

                return response()->success([$userWithStuff]);
            } else {
                return response()->success([]);
            }
        } else {
            $name = $data['name'];
            if ($name == '') {
                //ah, neither was filled out! lets just return errythang and we can cmd-F
                return $this->getUsers();
            }
            $users = User::with('application.school')
                ->where(User::FIELD_FIRSTNAME, 'like', '%'.$name.'%')
                ->orWhere(User::FIELD_LASTNAME, 'like', '%'.$name.'%')->get();

            return response()->success($users);
        }
    }

    /**
     * Gets all applications.
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

    /**
     * Checks in a user.
     * @param $id
     * @return mixed
     */
    public function checkInUser($id)
    {
        $user = User::find($id);
        if (! $user->hasRole('hacker')) {
            return response()->error('not a hacker');
        }
        $name = $user->name;
        $id = $user->id;
        $application = Application::where('user_id', $user->id)->first();

        if ($application->checked_in_at == null) {
            $application->checked_in_at = Carbon::now();
            $application->save();

            return response()->success("Checked in ${name} (#${id}) successfully!");
        } else {
            $diff = Carbon::parse($application->checked_in_at)->diffForHumans();

            return response()->error("User ${name} (#${id} is already checked in! (${diff})");
        }
    }

    /*
     * Sends the user a password reset email
     */
    public function sendPasswordReset($id)
    {
        $user = User::find($id);
        $user->sendPasswordResetEmail();

        return response()->success('ok');
    }

    /**
     * Adds an announcement.
     * @param Request $request
     * @return array status
     */
    public function addAnnouncement()
    {
        $data = json_decode(Request::getContent(), true);
        $message = $data['message'];
        $a = new Announcement();
        $a->title = '';
        $a->body = $message;
        $a->slack = true;
        $a->important = false;
        $a->save();

        $client = new \GuzzleHttp\Client();
        $client->post(
            env('SLACK_URL'),
            ['json' => ['text' => "<!everyone> ${message}"]]
        );

        return response()->success('yay');
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
     * POST to create an event.
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
        $event->delete();

        return ['message' => 'success'];
    }
}

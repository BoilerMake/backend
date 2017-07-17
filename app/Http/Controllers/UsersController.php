<?php

namespace App\Http\Controllers;

use Auth;
use Request;
use App\Models\User;
use App\Models\Application;
use App\Models\PuzzleProgress;

class UsersController extends Controller
{
    /**
     * GET /users/me
     * Gets the currently logged in User.
     * @return User|null
     */
    public function getMe()
    {
        return response()->success(Auth::user());
    }

    /**
     * PUT /users/me.
     * @return mixed
     */
    public function updateMe()
    {
        $user = Auth::user();
        $data = json_decode(Request::getContent(), true);

        foreach ($data as $key => $value) {
            //update the user info
            if (in_array($key, [
                User::FIELD_EMAIL,
                User::FIELD_FIRSTNAME,
                User::FIELD_LASTNAME,
                User::FIELD_PHONE,
            ])) {
                $user->$key = $value;
                $user->save();
            }
        }

        return response()->success('ok');
    }

    /**
     * GET /users/me/application.
     * @return mixed
     */
    public function getApplication()
    {
        $user = Auth::user();
        $application = $user->getApplication();
        $application['skills'] = json_decode($application->skills, true);

        foreach (Application::USER_FIELDS_TO_INJECT as $x) {
            $application[$x] = $user->$x;
        }
        $application['resume_get_url'] = $application[Application::FIELD_RESUME_UPLOADED_FLAG] ? $application->user->resumeURL() : null;
        $application['resume_put_url'] = $application->user->resumeURL('put');

        if (! Application::isPhaseInEffect(Application::PHASE_DECISIONS_REVEALED)) {
            //don't reveal decisions early
            $application->setHidden(['decision', 'emailed_decision']);
        }

        return response()->success([
            'application' => $application,
            'validation'  => $application->validationDetails(),
            'phase'       => Application::getCurrentPhase(),
        ]);
    }

    /**
     * PUT /users/me/application.
     * @return mixed
     */
    public function updateApplication()
    {
        $user = Auth::user();
        $data = json_decode(Request::getContent(), true);
        $application = $user->getApplication();

        foreach ($data as $key => $value) {
            if (in_array($key, Application::INITIAL_FIELDS)) {
                $application->$key = $value;
            } elseif (in_array($key, Application::USER_FIELDS_TO_INJECT)) {
                //we combine some User fields onto Application, since name lives on User and it makes things easier.
                $user->$key = $value;
            } elseif ($key == Application::FIELD_GITHUB) {
                if (! $user->github_user_id) {
                    //let them change username IF they are not linked to a Github account
                    $application->github = User::extractUsernameFromURL($value);
                }
            } elseif ($key == Application::FIELD_LINKEDIN) {
                $application[Application::FIELD_LINKEDIN] = User::extractUsernameFromURL($value);
            } elseif ($key == 'school') {
                if (isset($value['id'])) {
                    $application->school_id = $value['id'];
                } else {
                    $application->school_id = null;
                }
            } elseif ($key == Application::FIELD_RSVP_FLAG) {
                //TODO: check RSVP phase
                //check to make sure they were actually accepted in case we have some sneaky mofos
                if ($application->decision == Application::DECISION_ACCEPT) {
                    $application->rsvp = $value;
                }
            } elseif ($key == 'skills') {
                //                TODO: check RSVP phase
                $application->skills = json_encode($value);
            } elseif (in_array($key, [Application::FIELD_DIET, Application::FIELD_DIET_RESTRICTIONS])) {
                //TODO: check RSVP phase
                $application->$key = $value;
            }
        }
        $user->save();
        $application->save();

        return response()->success('ok');
    }

//    public function completePuzzle(Request $request)
//    {
//        if (! Auth::user()) {
//            return ['auth plz'];
//        }
//        $puzzle_id = intval($request->get('puzzle_id'));
//        if (! isset($puzzle_id)) {
//            return ['puzzle id null'];
//        }
//        $user_id = Auth::user()->id;
//
//        if ($request->get('puzzle_secret') != env('PUZZLE_SECRET')) {
//            return ['bad puzzle secret'];
//        }
//
//        if (PuzzleProgress::where('user_id', $user_id)->where('puzzle_id', $puzzle_id)->exists()) {
//            return ['dup'];
//        }
//
//        $r = new PuzzleProgress();
//        $r->user_id = $user_id;
//        $r->puzzle_id = $puzzle_id;
//        $r->save();
//
//        return ['ok'];
//    }
//
//    public function getCompletedPuzzleIDs(Request $request)
//    {
//        $user_id = Auth::user()->id;
//        $ids = [];
//        foreach (PuzzleProgress::where('user_id', $user_id)->get() as $each) {
//            $ids[] = intval($each->puzzle_id);
//        }
//
//        return ['puzzles'=>$ids];
//    }
}

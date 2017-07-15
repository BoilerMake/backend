<?php

namespace App\Http\Controllers;

use Auth;
use App\Models\User;
use App\Models\Application;
use Log;
use Request;
use App\Models\PuzzleProgress;

class UsersController extends Controller
{
    /**
     * Gets the currently logged in User.
     * @return User|null
     */
    public function getMe()
    {
        return response()->success(Auth::user());
    }

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
                User::FIELD_PHONE
            ])) {
                $user->$key = $value;
                $user->save();
            }
        }

        return response()->success('ok');
    }
    public function updateApplication() {
        $user = Auth::user();
        $data = json_decode(Request::getContent(), true);
        $application = $user->getApplication();

        foreach ($data as $key => $value) {
            if (in_array($key, [
                Application::FIELD_GRAD_YEAR,
                Application::FIELD_GENDER,
                Application::FIELD_MAJOR,
                Application::FIELD_DIET,
                Application::FIELD_DIET_RESTRICTIONS,
                Application::FIELD_RACE,
                Application::FIELD_LINKEDIN,
                Application::FIELD_DIET_RESTRICTIONS,
                Application::FIELD_RESUME_FILENAME,
                Application::FIELD_RESUME_UPLOADED_FLAG,
                Application::FIELD_NEEDS_TRAVEL_REIMBURSEMENT,
                Application::FIELD_IS_FIRST_HACKATHON,
                Application::FIELD_HAS_NO_GITHUB,
                Application::FIELD_HAS_NO_LINKEDIN
            ])) {
                $application->$key = $value;
            } else if(in_array($key, Application::USER_FIELDS_TO_INJECT)) {
                $user->$key = $value;
            } else if ($key == Application::FIELD_GITHUB) {
                //if user is linked to a GH account, don't let them change username
                if(!$user->github_user_id) {
                    //todo: turn github.com/user -> user
                    $application->github = $value;
                }
            } else if ($key == Application::FIELD_RSVP_FLAG) {
                //check to make sure they were actually accepted in case we have some sneaky mofos
                if ($application->decision == Application::DECISION_ACCEPT) {
                    $application->rsvp = $value;
                }
//            } else if ($key == 'skills') {
//                $application->skills = json_encode($value);
//            } else if ($key == 'school') {
//                if (isset($value['id'])) {
//                    $application->school_id = $value['id'];
//                } else {
//                    $application->school_id = null;
//                }
            }
        }
        $user->save();
        $application->save();
        return response()->success([
            'application'=>$application,
            'validation'=>$application->validationDetails(),
            'phase'=>intval(getenv('APP_PHASE')),
        ]);
    }

    public function getApplication()
    {
        $user = Auth::user();
        $application = $user->getApplication();
//        $application['skills'] = json_decode($application->skills, true);

        foreach(Application::USER_FIELDS_TO_INJECT as $x) {
            $application[$x] = $user->$x;
        }

        $phase = intval(getenv('APP_PHASE'));
        if ($phase < Application::PHASE_DECISIONS_REVEALED) {
            //don't reveal decisions early
            $application->setHidden(['decision','emailed_decision']);
        }

        return response()->success([
            'application'     => $application,
            'validation'      => $application->validationDetails(),
            'phase'           => $phase,
            'resume_view_url' => $application[Application::FIELD_RESUME_UPLOADED_FLAG] ? GeneralController::resumeUrl($application->user->id, 'get') : null,
        ]);
    }

    public function getResumePutUrl()
    {
        return GeneralController::resumeUrl(Auth::user()->id, 'put');
    }

    public function completePuzzle(Request $request)
    {
        if (! Auth::user()) {
            return ['auth plz'];
        }
        $puzzle_id = intval($request->get('puzzle_id'));
        if (! isset($puzzle_id)) {
            return ['puzzle id null'];
        }
        $user_id = Auth::user()->id;

        if ($request->get('puzzle_secret') != env('PUZZLE_SECRET')) {
            return ['bad puzzle secret'];
        }

        if (PuzzleProgress::where('user_id', $user_id)->where('puzzle_id', $puzzle_id)->exists()) {
            return ['dup'];
        }

        $r = new PuzzleProgress();
        $r->user_id = $user_id;
        $r->puzzle_id = $puzzle_id;
        $r->save();

        return ['ok'];
    }

    public function getCompletedPuzzleIDs(Request $request)
    {
        $user_id = Auth::user()->id;
        $ids = [];
        foreach (PuzzleProgress::where('user_id', $user_id)->get() as $each) {
            $ids[] = intval($each->puzzle_id);
        }

        return ['puzzles'=>$ids];
    }
}

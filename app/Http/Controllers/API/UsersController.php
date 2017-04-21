<?php

namespace App\Http\Controllers\API;

use Auth;
use Validator;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Application;
use Illuminate\Http\Request;
use App\Models\PasswordReset;
use App\Models\PuzzleProgress;
use App\Http\Controllers\Controller;

class UsersController extends Controller
{

    public function __construct()
    {
        // Apply the jwt.auth middleware to all methods in this controller
       // Except allows for fine grain exclusion if necessary
       $this->middleware('jwt.auth', ['except' => ['sendPasswordReset', 'performPasswordReset']]);
    }

    /**
     * Gets the currently logged in User.
     * @return User|null
     */
    public function getMe()
    {
        return response()->success(Auth::user());
    }

    public function updateMe(Request $request)
    {
        $user = Auth::user();
        $data = $request->all();
        foreach ($data as $key => $value) {
            //update the user info
            if (in_array($key, ['email', 'first_name', 'last_name', 'phone'])) {
                $user->$key = $value;
                $user->save();
            }
        }
        $hasApplication = false;
        if (isset($data['application'])) {
            $hasApplication = true;
            //update the application
            $application = self::getApplication()['application'];
            foreach ($data['application'] as $key => $value) {
                if (in_array($key, ['age', 'grad_year', 'gender', 'major', 'diet', 'diet_restrictions', 'github',
                    'race', 'linkedin', 'diet_restrictions',
                    'resume_filename', 'resume_uploaded', 'needsTravelReimbursement', 'isFirstHackathon', 'has_no_github', 'has_no_linkedin', ])) {
                    $application->$key = $value;
                }
                if ($key == 'rsvp') {
                    //check to make sure they were actually accepted in case we have some sneaky mofos
                    if ($application->decision == Application::DECISION_ACCEPT) {
                        $application->rsvp = $value;
                    }
                }
                if ($key == 'skills') {
                    $application->skills = json_encode($value);
                }
                if ($key == 'school') {
                    if (isset($value['id'])) {
                        $application->school_id = $value['id'];
                    } else {
                        $application->school_id = null;
                    }
                }
            }
            $application->save();
        }
        if ($hasApplication) {
            return [
                'application'=>$application,
                'validation'=>$application->validationDetails(),
                'phase'=>intval(getenv('APP_PHASE')),
                'status'=>'ok',
            ];
        }

        return ['status'=>'ok'];
    }

    public function getApplication()
    {
        $user = Auth::user();
        if (! Auth::user()->hasRole('hacker')) {//TODO middleware perhaps?
            return;
        }
        $application = $user->getApplication();
        $application['skills'] = json_decode($application->skills, true);

        $phase = intval(getenv('APP_PHASE'));
        if ($phase < 3) { //don't reveal decisions early
            $application->setHidden(['decision']);
        }
        return [
            'application'=>$application,
            'validation'=>$application->validationDetails(),
            'phase'=>$phase,
            'teamsEnabled'=> (getenv('TEAMS') === 'true'),
            'resume_view_url'=>$application->resume_uploaded ? GeneralController::resumeUrl($application->user->id, 'get') : null,

        ];
    }

    public function getResumePutUrl()
    {
        $user = Auth::user();

        return GeneralController::resumeUrl($user->id, 'put');
    }

    public function sendPasswordReset(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);
        if ($validator->fails()) {
            return ['message' => 'error', 'errors' => $validator->errors()->all()];
        }
        $user = User::where('email', $request->email)->first();
        $user->sendPasswordResetEmail();

        return ['message' => 'success'];
    }

    public function performPasswordReset(Request $request)
    {
        $token = $request->token;
        $password = $request->password;

        $reset = PasswordReset::where('token', $token)->first();
        if (! $reset) {
            return 'oops';
        }
        if (Carbon::parse($reset->created_at)->addHour(48)->lte(Carbon::now())) {
            return 'expired';
        }
        if ($reset->is_used) {
            return 'already used';
        }
        $user = User::find($reset->user_id);
        $user->password = bcrypt($password);
        $user->save();

        $reset->is_used = true;
        $reset->save();

        return 'ok';
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

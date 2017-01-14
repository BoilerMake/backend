<?php

namespace App\Http\Controllers\API;

use Log;
use Auth;
use Imagick;
use Validator;
use ImagickDraw;
use ImagickPixel;
use Carbon\Carbon;
use App\Models\Team;
use App\Models\User;
use App\Models\Application;
use Illuminate\Http\Request;
use App\Models\PasswordReset;
use App\Models\PuzzleProgress;
use App\Http\Controllers\Controller;

class UsersController extends Controller
{
    const CARD_TYPE_HACKER = 1;
    const CARD_TYPE_EXEC = 2;

    public function __construct()
    {
        // Apply the jwt.auth middleware to all methods in this controller
       // Except allows for fine grain exclusion if necessary
       $this->middleware('jwt.auth', ['except' => ['sendPasswordReset', 'performPasswordReset']]);
    }

    public function getMe()
    {
        return Auth::user();
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
                if ($key == 'team_code') {
                    $team = Team::where('code', $value)->get()->first();
                    if ($team) {//todo return status of this
                        $application->team_id = $team->id;
                    }
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
//        if ($phase < 3) { //don't reveal decisions early
//            $application->setHidden(['decision']);
//        }
//
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

    public function leaveCurrentTeam()
    {
        $app = self::getApplication()['application'];
        $old_team_id = $app->team_id;
        $app->team_id = null;
        $app->save();
        if (Application::where('team_id', $old_team_id)->get()->count() == 0) {//we don't want empty teams
            Team::find($old_team_id)->delete();
        }

        return ['ok'];
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

    public static function generateAccessCardImage($user_id)
    {
        $user = User::with('application', 'application.school')->find($user_id);
        $schoolName = null;
        if ($user->hasRole('exec')) {
            $cardType = self::CARD_TYPE_EXEC;
            $schoolName = 'Purdue University';
        } elseif ($user->hasRole('hacker')) {
            $cardType = self::CARD_TYPE_HACKER;
            $schoolName = $user->application->school
                ? $user->application->school->name
                : '';
            $schoolName = $user->application->school && $user->application->school->display_name
                ? $user->application->school->display_name
                : $schoolName;
        } else {
            return 'error';
        }

        $isExecCard = $cardType == self::CARD_TYPE_EXEC;

        $image = new Imagick();

        //globals
        $whitePixel = new ImagickPixel('#FFFFFF');
        $bluePixel = new ImagickPixel('#1A4A98');
        $greenPixel = new ImagickPixel('#45955E');
        $blackPixel = new ImagickPixel('#000000');
        $mainFont = resource_path('assets/fonts/Exo2-Regular.ttf');
        $headingFont = resource_path('assets/fonts/SanFranciscoDisplay-Regular.otf');

        /* New image */
        $fullWidth = 900; //3in @ 300ppi
        $image->newImage($fullWidth, 1200, $whitePixel);

        /* GENERATE SKILLS ICONS */
        $skills = json_decode($user->application->skills, true);
//        Log::info($skills);
        $skillsYPos = 640;

        if (count($skills) == 3) {
            $item1raw = new Imagick();
            $item1raw->readImageFile(fopen(resource_path('assets/language_icons/'.$skills[0].'.png'), 'rb'));
            $item1raw->cropThumbnailImage(130, 130);
            $image->compositeImage($item1raw, IMAGICK::COMPOSITE_DEFAULT, 250, $skillsYPos);

            $item1raw = new Imagick();
            $item1raw->readImageFile(fopen(resource_path('assets/language_icons/'.$skills[1].'.png'), 'rb'));
            $item1raw->cropThumbnailImage(130, 130);
            $image->compositeImage($item1raw, IMAGICK::COMPOSITE_DEFAULT, 400, $skillsYPos);

            $item1raw = new Imagick();
            $item1raw->readImageFile(fopen(resource_path('assets/language_icons/'.$skills[2].'.png'), 'rb'));
            $item1raw->cropThumbnailImage(130, 130);
            $image->compositeImage($item1raw, IMAGICK::COMPOSITE_DEFAULT, 550, $skillsYPos);
        }
        if (count($skills) == 2) {
            $item1raw = new Imagick();
            $item1raw->readImageFile(fopen(resource_path('assets/language_icons/'.$skills[0].'.png'), 'rb'));
            $item1raw->cropThumbnailImage(130, 130);
            $image->compositeImage($item1raw, IMAGICK::COMPOSITE_DEFAULT, 320, $skillsYPos);

            $item1raw = new Imagick();
            $item1raw->readImageFile(fopen(resource_path('assets/language_icons/'.$skills[1].'.png'), 'rb'));
            $item1raw->cropThumbnailImage(130, 130);
            $image->compositeImage($item1raw, IMAGICK::COMPOSITE_DEFAULT, 475, $skillsYPos);
        }
        if (count($skills) == 1) {
            $item1raw = new Imagick();
            $item1raw->readImageFile(fopen(resource_path('assets/language_icons/'.$skills[0].'.png'), 'rb'));
            $item1raw->cropThumbnailImage(130, 130);
            $image->compositeImage($item1raw, IMAGICK::COMPOSITE_DEFAULT, 400, $skillsYPos);
        }

        $item1raw = new Imagick();
        $item1raw->readImageFile(fopen(resource_path('assets/logo_s17.png'), 'rb'));
        $item1raw->cropThumbnailImage(210, 210);
        $image->compositeImage($item1raw, IMAGICK::COMPOSITE_DEFAULT, 92, 50);

        $BMTextLine = new ImagickDraw();
        $BMTextLine->setFont($headingFont);
//        $BMTextLine->setTextAlignment(\Imagick::ALIGN_LEFT);
//        $BMTextLine->setTextKerning(2);
        $BMTextLine->setFontSize(80);
        $BMTextLine->setFillColor($bluePixel);

        $image->annotateImage($BMTextLine, 313, 180, 0, 'BOILERMAKE');

        $nameTextLine = new ImagickDraw();
        $nameTextLine->setFont($mainFont);
        $nameTextLine->setTextAlignment(\Imagick::ALIGN_CENTER);
        $nameTextLine->setTextKerning(2);
        $nameTextLine->setFontSize(62);
        $nameTextLine->setFillColor($blackPixel);

        $image->annotateImage($nameTextLine, $fullWidth / 2, 440, 0, $user->first_name.' '.$user->last_name);

        $schoolTextLine = new ImagickDraw();
        $schoolTextLine->setFont($mainFont);
        $schoolTextLine->setTextAlignment(\Imagick::ALIGN_CENTER);
        $schoolTextLine->setTextKerning(2);
        $schoolTextLine->setFontSize(45);
        $schoolTextLine->setFillColor($blackPixel);

        $image->annotateImage($schoolTextLine, $fullWidth / 2, 495, 0, $schoolName);

        //add the stripe
        $roleStripe = new ImagickDraw();
        $roleStripe->setFillColor($isExecCard ? $bluePixel : $greenPixel);
        $roleStripe->rectangle(0, 975, $fullWidth, 1200);
        $image->drawImage($roleStripe);

        $roleTextLine = new ImagickDraw();
        $roleTextLine->setFont($mainFont);
        $roleTextLine->setTextAlignment(\Imagick::ALIGN_CENTER);
        $roleTextLine->setTextKerning(2);
        $roleTextLine->setFontSize(68);
        $roleTextLine->setFillColor($whitePixel);
        $roleText = $isExecCard ? 'ORGANIZER' : 'HACKER';
        $image->annotateImage($roleTextLine, $fullWidth / 2, 1115, 0, $roleText);

        $fileName = 'cards/card_'.$cardType.'_'.$user_id.'.jpg';
        $path = public_path().'/'.$fileName;
        $user->card_image = $fileName;
        $user->save();
        $image->writeImage($path);
        Log::info('Saved card for user #'.$user_id.' to: '.$fileName);
    }
}

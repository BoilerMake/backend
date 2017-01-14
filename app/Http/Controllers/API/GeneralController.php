<?php

namespace App\Http\Controllers\API;

use AWS;
use Log;
use App\Models\User;
use App\Models\Event;
use App\Models\School;
use Illuminate\Http\Request;
use App\Models\InboundMessage;
use App\Models\InterestSignup;
use App\Http\Controllers\Controller;

class GeneralController extends Controller
{
    public function ping()
    {
        //        foreach (User::take(10)->get() as $user)
//            UsersController::generateAccessCardImage($user->id);
        UsersController::generateAccessCardImage(1);

        return ['pong'];
    }

    public function getSchools(Request $request)
    {
        $filter = $request->input('filter');
        Log::info($filter);
        if (! $filter) {
            $filter = '';
        }
        $locs = School::where('name', 'like', '%'.$filter.'%')->orWhere('name', 'Other/School not listed')->get();

        return $locs;
    }

    public function inboundSMS()
    {
        $input = Input::all();

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

    public function interestSignup(Request $request)
    {
        $email = $request->input('email');
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['status'=>'fail', 'message'=>'email is not valid!'];
        }
        $signup = InterestSignup::firstOrCreate(['email' => $email]);
        if ($signup->wasRecentlyCreated) {
            return ['status'=>'ok', 'message'=>'all signed up!'];
        }

        return['status'=>'fail', 'message'=>'you were already signed up!'];
    }

    public static function resumeUrl($id, $method)
    {
        $s3 = AWS::createClient('s3');
        switch ($method) {
            case 'get':
                $cmd = $s3->getCommand('getObject', [
                    'Bucket' => getenv('S3_BUCKET'),
                    'Key'    => getenv('S3_PREFIX').'/resumes/'.$id.'.pdf',
                    'ResponseContentType' => 'application/pdf',
                ]);
                break;
            case 'put':
                $cmd = $s3->getCommand('PutObject', [
                'Bucket' => getenv('S3_BUCKET'),
                'Key'    => getenv('S3_PREFIX').'/resumes/'.$id.'.pdf',
                ]);
                break;
        }
        $request = $s3->createPresignedRequest($cmd, '+1 day');

        return (string) $request->getUri();
    }

    public function getEvents()
    {
        return Event::orderBy('begin')->get(['id', 'title', 'description', 'begin', 'end']);
    }
}

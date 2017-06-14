<?php

namespace App\Http\Controllers\API;

use AWS;
use Log;
use App\Models\User;
use App\Models\Event;
use App\Models\School;
use App\Models\PodScan;
use App\Models\GithubEvent;
use App\Models\Announcement;
use Illuminate\Http\Request;
use App\Models\InboundMessage;
use App\Models\InterestSignup;
use App\Http\Controllers\Controller;

class GeneralController extends Controller
{
    /**
     * Heartbeat endpoint.
     * @return array
     */
    public function ping()
    {
        return ['pong'];
    }

    public function info()
    {
        return response()->success([
            'name'=>'BoilerMake API',
            'frontend'=>env('FRONTEND_ADDRESS'),
            'info'=>'http://github.com/BoilerMake',
            'docs'=>env('APP_URL').'/docs',
        ]);
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

    /**
     * Handles an incoming SMS from twillio.
     */
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
            return response()->error('email is not valid!');
        }
        $signup = InterestSignup::firstOrCreate(['email' => $email]);
        if ($signup->wasRecentlyCreated) {
            return response()->success('all signed up!');
        }

        return response()->error('you were already signed up!');
    }

    /**
     * Pre signs an S3 URL pointing to a given user id.
     * @param $id user ID
     * @param string $method GET or PUT
     * @return string the signed
     */
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
        $request = $s3->createPresignedRequest($cmd, '+7 days');

        return (string) $request->getUri();
    }

    public function getEvents()
    {
        return Event::where('hidden', 0)->orderBy('begin')->get(['id', 'title', 'description', 'begin', 'end']);
    }

    public function getAnnouncements()
    {
        return Announcement::orderBy('created_at', 'DESC')->get();
    }

    public function getActivity()
    {
        $pushes = GithubEvent::where('type', 'PushEvent')->with('user')->orderBy('timestamp', 'DESC')->get();
        $github = [];
//        foreach ($pushes as $push) {
//            $github[] = [
//                'id'=>$push->id,
//                'message'=>$push->user->name.' pushed to '.$push->repo,
//                'timestamp'=>$push->timestamp, ];
//        }

        $podScans = [];
//        foreach (PodScan::with('user', 'pod')->orderBy('created_at', 'DESC')->get() as $scan) {
//            if ($scan->user && $scan->pod) {
//                $podScans[] = [
//                    'id'=>$scan->id,
//                    'message'=>$scan->user->name.' scanned at pod '.$scan->pod->name,
//                    'timestamp'=>$scan->created_at->toDateTimeString(), ];
//            }
//        }

        return ['github'=>$github, 'pods'=>$podScans];
    }
}

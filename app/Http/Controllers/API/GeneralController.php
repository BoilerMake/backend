<?php namespace App\Http\Controllers\API;
use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\User;
use App\Models\InboundMessage;
use Log;
use Illuminate\Http\Request;
use AWS;
use App\Models\InterestSignup;
use GuzzleHttp;
use App\Services\Notifier;
class GeneralController extends Controller {
    public function test()
    {
       return ['hi'];
    }
    public static function successWrap($data)
    {
        return ['data'=>$data,
            'meta'=>['status'=>"200"]
        ];
    }
    public function getSchools(Request $request)
    {
        $filter = $request->input('filter');
        Log::info($filter);
        if(!$filter)
            $filter= "";
        $locs = School::where("name","like","%".$filter."%")->get();
        return $locs;
    }
    public function inboundSMS()
    {
        $input = Input::all();

        $phone = $input['From'];
        $user_id = NULL;
        //try to match it to user we have
        $user= User::where('phone', 'LIKE', '%'.$phone.'%')->get()->first();
        if($user)
            $user_id=$user->id;
        $n = new InboundMessage();
        $n->user_id=$user_id;
        $n->raw = json_encode($input);
        $n->number = $phone;
        $n->message = $input['Body'];
        $n->save();
    }
    public function interestSignup(Request $request)
    {
        $email = $request->input("email");
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['status'=>'fail','message'=>'email is not valid!'];
        }
        $signup = InterestSignup::firstOrCreate(['email' => $email]);
        if($signup->wasRecentlyCreated)
            return ['status'=>'ok','message'=>'all signed up!'];
        return['status'=>'ok','message'=>'you were already signed up!'];
    }

}

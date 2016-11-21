<?php namespace App\Http\Controllers\API;
use App\Models\User;
use App\Models\Role;
use JWTAuth;
use Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;
use Hash;
use Mail;
class AuthController extends Controller {
    /**
    * Authenticate a user
    *
    * @param  Request  $request
    * @return Response
    */
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');
        $validator = Validator::make($credentials, [
            'email' => 'required|email',
            'password'   => 'required',
        ]);
        if ($validator->fails()) {
            return $validator->errors()->all();
        }
        else {
	        if (Auth::attempt(['email' => $request['email'], 'password' => $request['password']])) {
	            $roles = Auth::user()->roles()->get()->lists('name');
	            $token = JWTAuth::fromUser(Auth::user(),['exp' => strtotime('+1 year'),'roles'=>$roles, 'slug'=>Auth::user()->slug(), 'user_id'=>Auth::user()->id]);
	            return compact('token');
	        }
    	}
    	// Probably a better way to do this
		return response()->json(['error' => 'invalid_credentials'], 401);
    }

    /**
    * Register a user
    *
    * @param  Request  $request
    * @return Response
    */
    public function signUp(Request $request)
    {
        if(intval(config('app.phase')) < 2)
            return ['error'=>'applications are not open'];

        $validator = Validator::make($request->all(), [
            'email'   => 'required|email|unique:users',
            'password'    => 'required',
        ]);

        if ($validator->fails()) {
            return $validator->errors()->all();
        }
        else {
            $user = new User;
            $user->password = Hash::make($request['password']);
            $user->email = $request['email'];
            $user->save();

            $user->postSignupActions(); // Attach roles

            $roles = $user->roles()->get()->lists('name');
            $token = JWTAuth::fromUser($user,['exp' => strtotime('+1 year'),'roles'=>$roles, 'slug'=>$user->slug(), 'user_id'=>$user->id]);
            
            Mail::send('emails.welcome', ['user' => $user], function ($message) use ($user) {
    			$message->from('hello@boilermake.org', 'Laravel');
    			$message->to($user->email);
			});
			
            return compact('token');
        }
    }

	public function debug()
	{
		$user = JWTAuth::parseToken()->authenticate();
		return $user;
	}
}
<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\GithubUser;
use Auth;
use Hash;
use JWTAuth;
use Log;
use Mail;
use Validator;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\PasswordReset;
use App\Mail\UserRegistration;

/**
 * Class AuthController.
 */
class AuthController extends Controller
{
    /**
     * @param  Request $request: email, password
     * @return string status message
     */
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');
        $validator = Validator::make($credentials, [
            'email' => 'required|email',
            'password'   => 'required',
        ]);
        if ($validator->fails()) {
            return response()->error($validator->errors()->all());
        } else {
            if (Auth::attempt(['email' => $request['email'], 'password' => $request['password']])) {
                $token = Auth::user()->getToken();

                return response()->success(compact('token'));
            }
        }
        // Probably a better way to do this
        return response()->error('Invalid email or password');
    }

    /**
     * Register a user.
     *
     * TODO: error handling like duplicate accounts
     * @param  Request $request: email, password
     * @return string status message
     */
    public function register(Request $request)
    {
        if (intval(config('app.phase')) < 2) {
            return response()->error('applications are not open');
        }

        $validator = Validator::make($request->all(), [
            'email'   => 'required|email|unique:users',
            'password'    => 'required',
        ]);

        $email = $request['email'];
        if ($validator->fails()) {
            return response()->error($validator->errors()->all());
        } else if(User::isEmailUsed($email)) {
            return response()->error('There is already an account with that email!');
        } else {
            $user = User::addNew($email,$request['password']);
            return response()->success(['token'=>$user->getToken()]);
        }
    }

    /**
     * Verify a user's email.
     *
     * @param  Request $request: code
     * @return string status message
     */
    public function confirmEmail(Request $request)
    {
        if (! isset($request->code)) {
            return response()->error('Code is required');
        }
        $user = User::where('confirmation_code', $request->code)->first();
        if ($user) {
            $user->confirmed = 1;
            $user->save();

            return response()->success('Email confirmed!');
        }

        return response()->error('Code is invalid');
    }

    /**
     * Triggers generation + emailing of a password reset link.
     * @param Request $request: email
     * @return string status message
     */
    public function sendPasswordReset(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);
        if ($validator->fails()) {
            return response()->error($validator->errors()->all());
        }
        $user = User::where('email', $request->email)->first();
        $user->sendPasswordResetEmail();

        return response()->success('Success! please check your email for a link');
    }

    /**
     * If the token is valid, it updates the user's password.
     * @param Request $request: password, token
     * @return string status message
     */
    public function performPasswordReset(Request $request)
    {
        $token = $request->token;
        $password = $request->password;

        $reset = PasswordReset::where('token', $token)->first();
        if (! $reset) {
            return response()->error('invalid reset link');
        }
        if (Carbon::parse($reset->created_at)->addHour(48)->lte(Carbon::now())) {
            return response()->error('link expired');
        }
        if ($reset->is_used) {
            return response()->error('link already used');
        }
        $user = User::find($reset->user_id);
        $user->password = bcrypt($password);
        $user->save();

        $reset->is_used = true;
        $reset->save();

        return response()->success('Success! password updated for '.$user->email);
    }
    private function getGithubAuthToken($code) {
        $client = new \GuzzleHttp\Client();
        $response = $client->request('POST', 'https://github.com/login/oauth/access_token', [
            'form_params' => [
                'client_id'     => env('GITHUB_CLIENT_ID'),
                'client_secret' => env('GITHUB_CLIENT_SECRET'),
                'code'          => $code
            ],
            'headers' => [
                'Accept' => 'application/json'
            ]
        ]);
        //TODO: error handling
        $result = json_decode($response->getBody(), true);
        Log::info('githubAuth result from getting acccess token',$result);
        if(isset($result['error'])) {
            //todo: handle error here...
            Log::info('githubAuth access token fetching error');
            return false;
        }
        $access_token = $result['access_token'];
        return $access_token;
    }
    public function githubAuth($code) {
        $gitHub_token = $this->getGithubAuthToken($code);
        if(!$gitHub_token) {
            //todo: handle error here...
            return response()->error('github error');
        }
        $client = new \GuzzleHttp\Client();
        $response = $client->request('GET', "https://api.github.com/user?access_token={$gitHub_token}", [
            'headers' => [
                'Accept' => 'application/json'
            ]
        ]);
        $result = json_decode($response->getBody(), true);
        Log::info("githubAuth: user API response",$result);
        $githubUser = GithubUser::store($result,$gitHub_token);
        $username = $githubUser->username;

        if(!isset($result['email'])) {
            Log::info("githubAuth: user didn't give email scope :(");
            //eek no email permissions scope
            return response()->error('no email scope :(');
        }

        try {
            $loggedInUser = JWTAuth::parseToken()->toUser();
        } catch (\Exception $e) {
            $loggedInUser = null;
        }

        $email = $githubUser->email;
        $doesUserExistAlready = User::isEmailUsed($email) || Application::where('github',$username)->exists();
        $action = null;

        if($loggedInUser) {
            $action = 'link';
            Log::info("githubAuth: user #{$loggedInUser->id} is already logged in, so we should link account");
            //todo: link to existing account if user is logged in
            $user = $loggedInUser;
        } else if($doesUserExistAlready) {
            //User exists, we are doing a login action
            $action = 'login';
            $user = User::where('email',$email)->first();
            if(!$user) {
                //fallback to matching via application form responses
                $user = Application::where('github',$username)->first()->user;
            }
        } else {
            //need to create a new user!
            $action = 'create';
            $user = User::addNew($email,null,false);
        }
        $user->github_user_id = $githubUser->id;
        $user->save();
        if(!$user->first_name && !$user->last_name) {
            //we don't want to overwrite names, just autofill them if they are null
            $nameParts = explode(" ",$result['name']);
            $user->first_name = $nameParts[0];
            $user->last_name = isset($nameParts[1]) ? $nameParts[1] : null; //edge case: name on GH is only one word.


        }
        if($user->hasRole(User::ROLE_HACKER)) {
            $application = $user->getApplication();
            $application->github = $username;
            $application->has_no_github = false;
            $application->save();
            Log::info("Saved GH username {$username} int aapplication",['user_id'=>$user->id, 'application_id'=>$application->id]);
        }

        return response()->success([
            'token'=>$user->getToken(),
            'username' => $username,
            'action' => $action,
        ]);
    }
}

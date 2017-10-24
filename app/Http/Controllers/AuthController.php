<?php

namespace App\Http\Controllers;

use Log;
use Auth;
use JWTAuth;
use Validator;
use Carbon\Carbon;
use App\Models\User;
use App\Models\GithubUser;
use App\Models\Application;
use Illuminate\Http\Request;
use App\Models\PasswordReset;

/**
 * Class AuthController.
 * Used to login and register users, via both email and password
 * Also handles password reset and email confirmations.
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
        if (! Application::isPhaseInEffect(Application::PHASE_APPLICATIONS_OPEN)) {
            return response()->error('applications are not open');
        }

        $validator = Validator::make($request->all(), [
            'email'   => 'required|email|unique:users',
            'password'    => 'required',
        ]);

        $email = $request['email'];
        if ($validator->fails()) {
            return response()->error($validator->errors()->all());
        } elseif (User::isEmailUsed($email)) {
            return response()->error('There is already an account with that email!');
        } else {
            $user = User::addNew($email, $request['password']);

            return response()->success(['token'=>$user->getToken()]);
        }
    }

    /**
     * Verify a user's email.
     *
     * @param  Request $request: code
     * @return string status message
     */
    public function confirmEmail($code = null)
    {
        if (! $code) {
            return response()->error('Code is required');
        }
        $user = User::where(User::FIELD_CONFIRMATION_CODE, $code)->first();
        if ($user) {
            $user->confirmed = 1;
            $user->save();
            Log::info("confirmEmail {$user->email}");

            return response()->success(['message'=>'Email confirmed!', 'token'=>$user->getToken()]);
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

    /**
     * @codeCoverageIgnore
     * @param $code
     * @return mixed
     */
    public function githubAuth($code)
    {
        if (! Application::isPhaseInEffect(Application::PHASE_APPLICATIONS_OPEN)) {
            return response()->error('applications are not open');
        }

        $gitHub_token = GithubUser::getGithubAuthToken($code);
        if (! $gitHub_token) {
            //todo: handle error here...
            return response()->error('github error');
        }
        $githubUser = GithubUser::fetchFromOauthToken($gitHub_token);

        if (! $githubUser->email) {
            Log::error("githubAuth: user didn't give email", ['githubUsername' => $githubUser->userName]);
            //eek no email
            return response()->error('You do not have an email attached to your GitHub profile.');
        }

        //do we have a user logged in already (e.g. if they are linking their GH)
        try {
            $loggedInUser = JWTAuth::parseToken()->toUser();
        } catch (\Exception $e) {
            $loggedInUser = null;
        }

        $email = $githubUser->email;
        $username = $githubUser->username;
        $doesUserExistAlready = User::isEmailUsed($email) || Application::where('github', $username)->exists();

        //decide if we want to link, login, or create a User
        $action = null;
        if ($loggedInUser) {
            $action = 'link';
            Log::info("githubAuth: user #{$loggedInUser->id} is already logged in, so we should link account");
            $user = $loggedInUser;
        } elseif ($doesUserExistAlready) {
            //User exists, we are doing a login action
            $action = 'login';
            $user = User::where('email', $email)->first();
            if (! $user) {
                //fallback to matching via application form responses
                $user = Application::where('github', $username)->first()->user;
            }
        } else {
            //need to create a new user!
            $action = 'create';
            $user = User::addNew($email, null, false);
        }
        $user->github_user_id = $githubUser->id;
        Log::info("githubAuth success, action={$action}", ['user_id'=>$user->id]);

        //auto-fill names
        if (! $user->first_name && ! $user->last_name) {
            //we don't want to overwrite names, just autofill them if they are null
            $nameParts = explode(' ', $githubUser->name);
            $user->first_name = $nameParts[0];
            $user->last_name = isset($nameParts[1]) ? $nameParts[1] : null; //edge case: name on GH is only one word.
        }

        //if user is a hacker, fill out some of their application
        if ($user->hasRole(User::ROLE_HACKER)) {
            $application = $user->getApplication();
            $application->github = $username;
            $application->has_no_github = false;
            $application->save();
            Log::info("Saved GH username {$username} int application", ['user_id'=>$user->id, 'application_id'=>$application->id]);
        }
        $user->save();

        return response()->success([
            'token'=>$user->getToken(),
            'username' => $username,
            'action' => $action,
        ]);
    }
}

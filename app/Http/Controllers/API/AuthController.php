<?php

namespace App\Http\Controllers\API;

use Auth;
use Hash;
use Mail;
use JWTAuth;
use Validator;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\PasswordReset;
use App\Mail\UserRegistration;
use App\Http\Controllers\Controller;

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

        if ($validator->fails()) {
            return response()->error($validator->errors()->all());
        } else {
            $code = str_random(24);
            $user = new User;
            $user->password = Hash::make($request['password']);
            $user->email = $request['email'];
            $user->confirmation_code = $code;
            $user->save();

            $user->postSignupActions(); // Attach roles

            $token = $user->getToken();

            //todo: clean up this email building
            $link = env('FRONTEND_ADDRESS').'/confirm?tok='.$code;
            Mail::to($user->email)->send(new UserRegistration($user, $link));

            return response()->success(compact('token'));
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
}

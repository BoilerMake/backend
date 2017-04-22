<?php

namespace App\Http\Controllers\API;

use App\Models\PasswordReset;
use Auth;
use Carbon\Carbon;
use Hash;
use Mail;
use JWTAuth;
use Validator;
use App\Models\User;
use Illuminate\Http\Request;
use App\Mail\UserRegistration;
use App\Http\Controllers\Controller;

/**
 * Class AuthController.
 */
class AuthController extends Controller
{
    /**
     * Authenticate a user, given username and password, returning a JWT token.
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
     * @param  Request  $request
     * @return Response
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

            $roles = $user->roles()->get()->pluck('name');
            $token = JWTAuth::fromUser($user, ['exp' => strtotime('+1 year'), 'roles'=>$roles, 'slug'=>$user->slug(), 'user_id'=>$user->id]);

            //todo: clean up this email building
            $link = env('FRONTEND_ADDRESS').'/confirm?tok='.$code;
            Mail::to($user->email)->send(new UserRegistration($user, $link));

            return response()->success(compact('token'));
        }
    }

    /**
     * Verify a user's email.
     *
     * @param  Request  $request
     * @return Response
     */
    public function confirmEmail(Request $request)
    {
        if (! isset($request->code)) {
            return response()->json(['error' => 'Code Required'], 200);
        }
        $user = User::where('confirmation_code', $request->code)->first();
        if ($user) {
            $user->confirmed = 1;
            $user->save();

            return response()->json(['success' => 'Email Confirmed'], 200);
        }

        return response()->json(['error' => 'Invalid Code'], 200);
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
}

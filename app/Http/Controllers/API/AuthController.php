<?php namespace App\Http\Controllers\API;
use App\Models\User;
use App\Models\Role;
use JWTAuth;
use Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;
use Hash;
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
        if (Auth::attempt(['email' => $request['email'], 'password' => $request['password']])) {
            $roles = Auth::user()->roles()->get()->lists('name');
            $token = JWTAuth::fromUser(Auth::user(),['exp' => strtotime('+1 year'),'roles'=>$roles, 'slug'=>Auth::user()->slug()]);
            return GeneralController::successWrap(compact('token'));
        }
        else {
            // invalid
        }
    }

    /**
    * Register a user
    *
    * @param  Request  $request
    * @return Response
    */
    public function signUp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required',
            'last_name' => 'required',
            'email'   => 'required|email|unique:users',
            'password'    => 'required',
        ]);

        if ($validator->fails()) {
            return $validator->errors()->all();
        }
        else {
            $user = new User;
            $user->first_name = $request['first_name'];
            $user->last_name = $request['last_name'];
            $user->password = Hash::make($request['password']);
            $user->email = $request['email'];
            $user->save();

            return array('success', 'true');

            /**
            * Needs comments, don't really understand what this does
            **/

            //  //signup actions
            // $user->postSignupActions();

            // $data  = $user->attributesToArray();
            // $roles = $user->roles()->get()->lists('name');
            // $token = JWTAuth::fromUser($user,['exp' => strtotime('+1 year'),'roles'=>$roles, 'slug'=>$user->slug()]);
            // $data ['token']=$token;
            // return GeneralController::successWrap($data);
        }
    }

    public function debug()
    {
        $user = JWTAuth::parseToken()->authenticate();
        return $user;
    }
}

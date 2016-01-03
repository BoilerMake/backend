<?php namespace App\Http\Controllers\API;
use App\Models\User;
use App\Models\Role;
use JWTAuth;
use Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
class AuthController extends Controller {

    public function login(Request $request)
    {
        try {
            $credentials = [
                'email' => $request->input('email'),
                'password' => $request->input('password')
            ];
        } catch(Exception $e)
        {
            $error = $e->getMessage();
            return  compact('error');
        }
        $valid = Auth::validate($credentials);
        if (!$valid)
            return ['oops'];
        $user = User::where('email','=',$credentials['email'])->first();

        $roles = $user->roles()->get()->lists('name');
        $token = JWTAuth::fromUser($user,['exp' => strtotime('+1 year'),'roles'=>$roles, 'slug'=>$user->slug()]);
        return GeneralController::successWrap(compact('token'));
    }
    public function signUp(Request $request)
    {
        try {
            $email = $request->input('email');
            $password = $request->input('password');
            $first_name = $request->input('first_name');
            $last_name = $request->input('last_name');
        }
        catch(Exception $e) {
            $error = $e->getMessage();
            return  response()->json(compact('error'));
        }
        try {
            $user = User::create([
                'email' => $email,
                'password' => bcrypt($password),
                'first_name'=> $first_name,
                'last_name'=> $last_name
            ]);

            //signup actions
            $user->postSignupActions();

            $data  = $user->attributesToArray();
            $roles = $user->roles()->get()->lists('name');
            $token = JWTAuth::fromUser($user,['exp' => strtotime('+1 year'),'roles'=>$roles, 'slug'=>$user->slug()]);
            $data ['token']=$token;
            return GeneralController::successWrap($data);
        }
        catch(Exception $e) {
            return ['user_exists', 'A user already exists with this email!'];
        }

    }
    public function debug()
    {
        $user = JWTAuth::parseToken()->authenticate();
        return $user;
    }
}

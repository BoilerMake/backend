<?php namespace App\Http\Controllers\API;

use App\Models\User;
use App\Http\Controllers\Controller;
use JWTAuth;
class UsersController extends Controller {

    public function getMe()
    {
        //todo: AccessControl::checkRule('only-me');
        $user = JWTAuth::parseToken()->authenticate();
        $data  = $user->attributesToArray();
        return GeneralController::successWrap($data);
    }

}

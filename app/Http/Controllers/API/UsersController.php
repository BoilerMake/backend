<?php namespace App\Http\Controllers\API;

use App\Models\User;
use App\Http\Controllers\Controller;
use Auth;

class UsersController extends Controller {

	public function __construct() {
       // Apply the jwt.auth middleware to all methods in this controller
       // Except allows for fine grain exclusion if necessary
       $this->middleware('jwt.auth', ['except' => []]);
	}

	// Example method that is automatically authenticated by middleware
	public function getAttributes() {
		return Auth::user()->getAttributes();
	}
}
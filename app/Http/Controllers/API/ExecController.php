<?php namespace App\Http\Controllers\API;

use App\Models\User;
use App\Http\Controllers\Controller;
use Auth;
use Validator;
use Illuminate\Http\Request;
use App\Models\Application;
use Input;
use Log;
use App\Models\Role;
class ExecController extends Controller {

	public function __construct() {
       $this->middleware('jwt.auth', ['except' => []]);
	}
	public function getHackers() {
		if(!Auth::user()->hasRole('exec'))//TODO middleware perhaps?
			return;
		$users = User::whereHas('roles', function($q)
		{
		    $q->where('name', 'hacker');
		})->with('application','application.school')->get();
		return $users;

	}
}
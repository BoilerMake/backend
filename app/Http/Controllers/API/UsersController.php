<?php namespace App\Http\Controllers\API;

use App\Models\User;
use App\Http\Controllers\Controller;
use Auth;
use Validator;
use Illuminate\Http\Request;
use App\Models\Application;
 
class UsersController extends Controller {

	public function __construct() {
       // Apply the jwt.auth middleware to all methods in this controller
       // Except allows for fine grain exclusion if necessary
       $this->middleware('jwt.auth', ['except' => []]);
	}

	// Example method that is automatically authenticated by middleware
	public function getAttributes() {
		 return Auth::user()->getAttributes();
		//return Auth::user()->application->toArray();
	}

	public function application(Request $request) {
		$validator = Validator::make($request->all(), [
            'age' => 'integer|min:0|max:255',
            'gender' => 'string',
            'major' => 'string',
            'grad_year' => 'integer', // should be string
            'diet' => 'string|max:255',
            'diet_restrictions' => 'string',
            'tshirt' => 'integer|min:0|max:255',
            'phone' => 'string|max:255',

        ]);

        if ($validator->fails()) {
        	// uh oh
        }
        else {
			$application = Application::firstOrCreate(['user_id' => Auth::user()->id]);
			$application->user_id = Auth::user()->id;
			$application->age = $request->input('age');
			$application->gender = $request->input('gender');
			$application->major = $request->input('major');
			$application->grad_year = $request->input('grad_year');
			$application->diet = $request->input('diet');
			$application->diet_restrictions = $request->input('diet_restrictions');
			$application->tshirt = $request->input('tshirt');
			$application->phone = $request->input('phone');
			$application->save();
		}
	}
}
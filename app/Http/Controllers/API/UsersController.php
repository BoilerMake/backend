<?php namespace App\Http\Controllers\API;

use App\Models\User;
use App\Http\Controllers\Controller;
use Auth;
use Validator;
use Illuminate\Http\Request;
use App\Models\Application;
use Input;
use Log;
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
	public function updateMe(Request $request)
	{
		Log::info($request);
		$user = Auth::user();
		$data = $request->all();
			foreach($data as $key => $value)
			{
				if(in_array($key,['email','first_name','first_name','phone']))
				{
					$user->$key=$value;
					$user->save();
					Log::info($value);
				}
			}
			if(isset($data['application']))
			{
				//update the application
				$application = Application::firstOrCreate(['user_id' => Auth::user()->id]);
				foreach ($data['application'] as $key => $value) {
					if(in_array($key,['age','gender','major','diet','diet_restrictions','tshirt']))
					{
						$application->$key=$value;
						$application->save();
					}
				}
			}

	}

	public function getApplication()
	{
		$application = Application::firstOrCreate(['user_id' => Auth::user()->id]);
		$application->save();
		return $application;
	}
	// public function updateApplication(Request $request) {
	// 	$validator = Validator::make($request->all(), [
 //            'age' => 'integer|min:0|max:255',
 //            'gender' => 'string',
 //            'major' => 'string',
 //            'grad_year' => 'integer', // should be string
 //            'diet' => 'string|max:255',
 //            'diet_restrictions' => 'string',
 //            'tshirt' => 'integer|min:0|max:255',
 //            'phone' => 'string|max:255',

 //        ]);

 //        if ($validator->fails()) {
 //        	// uh oh
 //        }
 //        else {
	// 		$application = Application::firstOrCreate(['user_id' => Auth::user()->id]);
	// 		$application->user_id = Auth::user()->id;
	// 		$application->age = $request->input('age');
	// 		$application->gender = $request->input('gender');
	// 		$application->major = $request->input('major');
	// 		$application->grad_year = $request->input('grad_year');
	// 		$application->diet = $request->input('diet');
	// 		$application->diet_restrictions = $request->input('diet_restrictions');
	// 		$application->tshirt = $request->input('tshirt');
	// 		$application->phone = $request->input('phone');
	// 		$application->save();
	// 	}
	// }
}
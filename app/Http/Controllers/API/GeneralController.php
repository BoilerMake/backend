<?php namespace App\Http\Controllers\API;
use App\Http\Controllers\Controller;
use App\Models\School;
use Log;
use Illuminate\Http\Request;
class GeneralController extends Controller {
    public function test()
    {
       return ['hi'];
    }
    public static function successWrap($data)
    {
        return ['data'=>$data,
            'meta'=>['status'=>"200"]
        ];
    }
    public function getSchools(Request $request)
    {
        $filter = $request->input('filter');
        Log::info($filter);
        if(!$filter)
            $filter= "";
        $locs = School::where("name","like","%".$filter."%")->get();
        return $locs;
    }
}

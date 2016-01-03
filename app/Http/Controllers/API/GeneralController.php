<?php namespace App\Http\Controllers\API;
use App\Http\Controllers\Controller;
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
}

<?php

namespace App\Providers;

use Request;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Response;;

class ResponseServiceProvider extends ServiceProvider
{
    /**
     * Register the application's response macros.
     * success: for API success
     * error: for API error
     * @return void
     */
    public function boot()
    {

        Response::macro('success', function ($data) {
            $debugInfo = [
                'url'=>Request::fullUrl(),
//                'all'=>Request::all(),
                'ip'=>Request::ip(),
//                'headers'=>Request::header(),
                'user'=>\Auth::user()];

            return Response::json([
                'success' => true,
                'data' => $data,
                'debug' => $debugInfo
            ],200,['hi'=>'ih']);
        });
        Response::macro('error', function ($data, $message=null) {
            return Response::json([
                'success' => false,
                'message' => $message,
                'data' => $data
            ],500);
        });
    }
}

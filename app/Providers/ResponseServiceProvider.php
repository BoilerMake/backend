<?php

namespace App\Providers;

use Request;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Response;
use Log;
class ResponseServiceProvider extends ServiceProvider
{
    /**
     * Register the application's response macros.
     * success: for API success
     * error: for API error.
     * @return void
     */
    public function boot()
    {
        $debugInfo = [
            'request_url'=>Request::fullUrl(),
            'request_path'=>Request::path(),
            'request_all_params'=>Request::all(),
            'request_client_ip'=>Request::ip(),
            'request_headers'=>Request::header()];

        Response::macro('success', function ($data) use ($debugInfo) {
            $debugInfo['request_success']=true;
            $debugInfo['request_user']=\Auth::user();
            $debugInfo['request_response_code']=200;
            Log::info('api_request',$debugInfo);

            return Response::json([
                'success' => true,
                'data' => $data,
                'debug' => $debugInfo,
            ], 200, ['headerkey'=>'headerval']);
        });
        Response::macro('error', function ($message, $data = null, $response_code=400)  use ($debugInfo) {
            $debugInfo['request_success']=false;
            $debugInfo['request_user']=\Auth::user();
            $debugInfo['request_response_code']=$response_code;
            Log::info('api_request',$debugInfo);

            return Response::json([
                'success' => false,
                'message' => $message,//todo: refactor to error_message?
                'data' => $data,
                'debug' => $debugInfo,
            ], $response_code);
        });
    }
}

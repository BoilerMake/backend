<?php

namespace App\Providers;

use Log;
use Request;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Response;

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
        try {
            $user = \JWTAuth::parseToken()->toUser();
        } catch (\Exception $e) {
            $user = null;
        }

        $requestInfo = [
            'url'=>Request::fullUrl(),
            'path'=>Request::path(),
            'params'=>Request::all(),
            'ip'=>Request::ip(),
            'headers'=>Request::header(),
            'user' => $user,
            'success' => true,
            'code' => 200
        ];

        Response::macro('success', function ($data) use ($requestInfo) {
            Log::info('api_request', ['request' => $requestInfo]);
            return Response::json([
                'success' => true,
                'data' => $data,
                'request' => $requestInfo,
            ], 200);
        });

        Response::macro('error', function ($message, $data = null, $response_code = 400) use ($requestInfo) {
            $requestInfo['success'] = false;
            $requestInfo['code'] = $response_code;
            Log::info('api_request', ['request' => $requestInfo, 'error_message' => $message]);
            return Response::json([
                'success' => false,
                'message' => $message, //todo: refactor to error_message?
                'data' => $data,
                'request' => $requestInfo,
            ], $response_code);
        });
    }
}

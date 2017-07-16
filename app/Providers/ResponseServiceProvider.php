<?php

namespace App\Providers;

use App;
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
            'url'       => Request::fullUrl(),
            'path'      => Request::path(),
            'params'    => Request::all(),
            'ip'        => Request::ip(),
            'headers'   => Request::header(),
            'user'      => $user,
            'success'   => true,
            'code'      => 200,
            'timing_ms' => round(1000*(microtime(true)-LARAVEL_START)),
        ];

        //determine if we want to return debug info, based on x-debug-token, which gets set via React cookie.
        //we will also return debug info in a dev env
        $providedDebugToken = Request::header('x-debug-token');
        $shouldDebugRequest = ($providedDebugToken && $providedDebugToken === env('DEBUG_TOKEN') || (env('APP_ENV') !== 'production'));

        //if on !production, log requests only if env says to, because they are inherently verbose
        $shouldLog = (App::environment() == 'production') || env('SHOW_EXTRA_LOGS_DEV');
        Response::macro('success', function ($data) use ($requestInfo, $shouldDebugRequest, $shouldLog) {
            if($shouldLog)
                Log::info('api_request', ['request' => $requestInfo]);

            return Response::json([
                'success'       => true,
                'data'          => $data,
                'request_debug' => $shouldDebugRequest ? $requestInfo : null,
            ], 200);
        });

        Response::macro('error', function ($message, $data = null, $response_code = 400) use ($requestInfo, $shouldDebugRequest, $shouldLog) {
            $requestInfo['success'] = false;
            $requestInfo['code'] = $response_code;
            if($shouldLog)
                Log::info('api_request', ['request' => $requestInfo, 'error_message' => $message]);

            return Response::json([
                'success'       => false,
                'message'       => $message,
                'data'          => $data,
                'request_debug' => $shouldDebugRequest ? $requestInfo : null,
            ], $response_code);
        });
    }
}

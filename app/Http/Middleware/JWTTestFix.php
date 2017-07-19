<?php

namespace App\Http\Middleware;

use App;
use Closure;
use JWTAuth;

class JWTTestFix
{
    /**
     * Laravel is handling JWTs in tests weirdly...
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ((App::environment() == 'testing') && $request->headers->get('Authorization')) {
            JWTAuth::setRequest($request);
        }

        return $next($request);
    }
}

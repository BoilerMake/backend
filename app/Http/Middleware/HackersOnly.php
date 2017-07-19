<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;

class HackersOnly
{
    /**
     * Handle an incoming request.
     * Only allows users who are hackers.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (! \Auth::user()->hasRole(User::ROLE_HACKER)) {
            return response()->error('Not allowed. You must be a hacker to access this.', null, 403);
        }

        return $next($request);
    }
}

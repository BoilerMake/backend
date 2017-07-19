<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

/*
 * @codeCoverageIgnore
 */
class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'App\Events\Event' => [
            'App\Listeners\EventListener',
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
//        Event::listen('tymon.jwt.valid', function($user) {
//            Auth::login($user);
//        });
        Event::listen('tymon.jwt.absent', function () {
            return response()->error('token_missing', 'token missing', 401);
        });

        Event::listen('tymon.jwt.expired', function ($user) {
            return response()->error('token_expired', 'token expired', 401);
        });

        Event::listen('tymon.jwt.invalid', function ($user) {
            return response()->error('token_invalid', 'token invalid', 401);
        });

        Event::listen('tymon.jwt.user_not_found', function ($user) {
            throw new JWTException('User not found', 404);
        });

        //
    }
}

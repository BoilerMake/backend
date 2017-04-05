<?php

namespace App\Providers;
use Event;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'App\Events\SomeEvent' => [
            'App\Listeners\EventListener',
        ],
    ];

    /**
     * Register any other events for your application.
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @return void
     */
    public function boot()
    {
        parent::boot();
//        Event::listen('tymon.jwt.valid', function($user) {
//            Auth::login($user);
//        });
        Event::listen('tymon.jwt.absent', function() {
            return response()->error("token_missing","token missing");
        });

        Event::listen('tymon.jwt.expired', function($user) {
            return response()->error("token_expired","token expired");
        });

        Event::listen('tymon.jwt.invalid', function($user) {
            return response()->error("token_invalid","token invalid");
        });

        Event::listen('tymon.jwt.user_not_found', function($user) {
            throw new JWTException("User not found", 404);
        });

        //
    }
}

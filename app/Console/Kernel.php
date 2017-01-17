<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\Inspire::class,
        Commands\CreateUser::class,
        Commands\PodPodKey::class,
        Commands\JWTKey::class,
        Commands\CalculateApplications::class,
        Commands\ProcessExpiredRSVP::class,
        Commands\AcceptDrivingHackers::class,
        Commands\GenerateEmailTodo::class,
        Commands\BusRoster::class,
        Commands\GenerateAccessCards::class,
        Commands\GenerateMagicLink::class,
        Commands\SponsorDump::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('inspire')->hourly();
        $schedule->command('applications:calculate')->everyFiveMinutes();
        $schedule->command('applications:expiredrsvp')->hourly();
    }
}

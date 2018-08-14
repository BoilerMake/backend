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
        Commands\CreateUser::class,
        Commands\AcceptDrivingHackers::class,
        Commands\GenerateEmailTodo::class,
        Commands\BusRoster::class,
        Commands\GenerateAccessCards::class,
        Commands\SponsorDump::class,
        Commands\GetGithubActivity::class,
        Commands\GenerateTableNumbers::class,
        Commands\CreateCheckedInHackers::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        if(config('app.backup_enabled')) {
            $schedule->command('backup:clean')->weeklyOn(1, '8:00');
            $schedule->command('backup:run --only-db')->hourly();
        }
        
        $schedule->command('applications:calculate')->everyFiveMinutes();
        //        $schedule->command('users:github')->everyFiveMinutes();
        $schedule->command('applications:expiredrsvp')->hourly();
    }

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        require base_path('routes/console.php');
    }
}

<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Application;
use Illuminate\Console\Command;

class BusRoster extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exec:busroster';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('MODES:');
        $this->info('1: bus apps with priority');
        $this->info('2: all apps with priority');
        $mode = $this->ask('mode?');
        $this->info('mode: '.$mode);

        $users = User::with('application', 'application.school')->get();

        if ($mode == 1) {
            foreach ($users as $user) {
                if ($user->application && $user->application->school && $user->application->school->transit_method == 'bus') {
                    $this->info(($user->application->school ? $user->application->school->getDisplayNameIfPossible() : 'n/a')."\t"
                        .$user['email']."\t"
                        .$user['phone']."\t"
                        .$user['first_name']."\t"
                        .$user['last_name']."\t"
                        .$user->getHashIDAttribute()."\t"
                        .$user->application->getPriorityLevelForAdmittance());
                }
            }
        }
        if ($mode == 2) {
            foreach ($users as $user) {
                if ($user->application) {
                    $this->info(($user->application->school ? $user->application->school->getDisplayNameIfPossible() : 'n/a')."\t"
                        .$user['email']."\t"
                        .$user['phone']."\t"
                        .$user['first_name']."\t"
                        .$user['last_name']."\t"
                        .$user->getHashIDAttribute()."\t"
                        .$user->application->getPriorityLevelForAdmittance());
                }
            }
        }
    }
}

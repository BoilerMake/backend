<?php

namespace App\Console\Commands;

use App\Models\User;
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
        $users = User::whereHas('roles', function ($q) {
            $q->where('name', 'hacker');
        })->with('application', 'application.school')->get();
        foreach ($users as $user) {
            if($user->application->rsvp==true && $user->application->school && $user->application->school->transit_method=="bus")
            {
                $this->info($user->application->school->name."\t".$user['email']."\t".$user['first_name']."\t".$user['last_name']);
            }
        }
    }
}

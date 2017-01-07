<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\School;
use App\Models\Application;
use Carbon\Carbon;

class AcceptDrivingHackers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:acceptdriving';

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
        $emails = [];
        foreach(Application::whereNull('decision')->get() as $app)
        {
            if($app->completed && $app->school->transit_method=="car") {
                $emails[] = [$app->user->first_name, $app->user->email];
                $app->decision = Application::DECISION_ACCEPT;
                $app->rsvp_deadline = Carbon::now()->addDays(5);
                $app->save();
            }
        }
        $this->info(json_encode($emails));
    }
}

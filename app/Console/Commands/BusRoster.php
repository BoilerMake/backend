<?php

namespace App\Console\Commands;

use App\Models\Application;
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
        $this->info("MODES:");
        $this->info("1: complete applications");
        $this->info("2: accepted hackers (subset of 1) ");
        $this->info("3: where RSVP = yes (subset of 2)");
        $mode = $this->ask('mode?');
        $this->info("mode: ".$mode);

        if($mode==1)
            $appIDs = Application::where('completed_calculated', true)->get()->lists('id')->toArray();
        else if($mode==2)
            $appIDs = Application::where('decision', Application::DECISION_ACCEPT)->get()->lists('id')->toArray();
        else if($mode==3)
            $appIDs = Application::where('rsvp',true)->get()->lists('id')->toArray();
        else
            return;

        $school_id = $this->ask('school_id filter? (or "none")');
        $school_id = $school_id=="none" ? false : $school_id;
        $users = User::whereHas('roles', function ($q) {
            $q->where('name', 'hacker');
        })->with('application', 'application.school')->get();
        foreach ($users as $user) {
            if($user->application->school && $user->application->school->transit_method=="bus")
            {
                if(in_array($user->application->id,$appIDs))
                {
                    if ($school_id !== false)//filter by school
                    {
                        if ($user->application->school->id == $school_id)
                            $this->info($user->application->school->name . "\t" . $user['email'] . "\t" . $user['first_name'] . "\t" . $user['last_name']);
                    }
                    else {
                        $this->info($user->application->school->name . "\t" . $user['email'] . "\t" . $user['first_name'] . "\t" . $user['last_name']);
                    }
                }
            }
        }
    }
}

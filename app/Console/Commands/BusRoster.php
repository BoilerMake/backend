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
        $this->info('1: complete applications');
        $this->info('2: accepted hackers (subset of 1) ');
        $this->info('3: where RSVP = yes (subset of 2)');
        $this->info('4: magic jess thing, basically compelted applications)');
        $mode = $this->ask('mode?');
        $this->info('mode: '.$mode);

        if ($mode == 1|| $mode==4) {
            $appIDs = Application::where('completed_calculated', true)->get()->lists('id')->toArray();
        } elseif ($mode == 2) {
            $appIDs = Application::where('decision', Application::DECISION_ACCEPT)->get()->lists('id')->toArray();
        } elseif ($mode == 3) {
            $appIDs = Application::where('rsvp', true)->get()->lists('id')->toArray();
        } else {
            return;
        }

        $school_id = $this->ask('school_id filter? (or "none")');
        $school_id = $school_id == 'none' ? false : $school_id;
        $users = User::whereHas('roles', function ($q) {
            $q->where('name', 'hacker');
        })->with('application', 'application.school')->get();
        foreach ($users as $user) {
            if ($user->application->school && $user->application->school->transit_method == 'bus') {
                if (in_array($user->application->id, $appIDs)) {
                    if ($school_id !== false) {//filter by school
                        if ($user->application->school->id == $school_id) {
                            $this->info($user->application->school->name."\t".$user['email']."\t".$user['first_name']."\t".$user['last_name']);
                        }
                    } else {
                        $rsvptext = "";
                        if($user->application->rsvp==true)
                            $rsvptext = "said yes";
                        if ($user->application->rsvp==false)
                            $rsvptext = "said no";
                        if($user->application->rsvp==null)
                            $rsvptext = "did not respond";


                        if($mode==4) {
                            $this->info($user->application->school->name . "\t"
                                . $user['email'] . "\t"
                                . $user['first_name'] . "\t"
                                . $user['last_name'] . "\t"
                                . "expired: " . ($user->application->decision == 4 ? "yes" : "no")
                                . "\twaitlisted: " . ($user->application->decision == 2 ? "yes" : "no")
                                . "\taccepted: " . ($user->application->decision == 3 ? "yes" : "no")
                                . "\trsvp: " . $rsvptext);
                        }
                        else
                            $this->info($user->application->school->name."\t".$user['email']."\t".$user['first_name']."\t".$user['last_name']);

                    }
                }
            }
        }
    }
}

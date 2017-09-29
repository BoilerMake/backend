<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Application;
use Illuminate\Console\Command;

class GenerateEmailTodo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:generateemails';

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
        $this->info('1: no decision -> accept');
        $this->info('2: no decision -> waitlist');
        $this->info('3: waitlist -> accept');
        $this->info('4: accept -> expired');
        $this->info('5: incomplete apps');
        $this->info('6: rsvp=yes, non bus, non purdue, i.e. driving');
        $this->info('7: rsvp=yes, with schools');
        $mode = $this->ask('mode?');
        $this->info('mode: '.$mode);

        $toAcceptFromNull = Application::whereNull('emailed_decision')
                    ->where('decision', Application::DECISION_ACCEPT)
                    ->get();
        $toWaitlistFromNull = Application::whereNull('emailed_decision')
                    ->where('decision', Application::DECISION_WAITLIST)
                    ->get();
        $toAcceptFromWaitlist = Application::where('emailed_decision', Application::DECISION_WAITLIST)
                    ->where('decision', Application::DECISION_ACCEPT)
                    ->get();
        $expiredFromAccepted = Application::where('emailed_decision', Application::DECISION_ACCEPT)
                    ->where('decision', Application::DECISION_EXPIRED)
                    ->get();

        $incomplete = Application::where('completed_calculated', false)->get()->pluck('user_id');

        if ($mode == 1) {
            $this->info(json_encode($toAcceptFromNull->pluck('id')));
            foreach (User::whereIn('id', $toAcceptFromNull->pluck('user_id'))->get() as $u) {
                $this->info($u->email.','.$u->first_name);
            }
        }
        if ($mode == 2) {
            $this->info(json_encode($toWaitlistFromNull->pluck('id')));
            foreach (User::whereIn('id', $toWaitlistFromNull->pluck('user_id'))->get() as $u) {
                $this->info($u->email.','.$u->first_name);
            }
        }
        if ($mode == 3) {
            $this->info(json_encode($toAcceptFromWaitlist->pluck('id')));
            foreach (User::whereIn('id', $toAcceptFromWaitlist->pluck('user_id'))->get() as $u) {
                $this->info($u->email.','.$u->first_name,$u->getHashIDAttribute());
            }
        }
        if ($mode == 4) {
            $this->info(json_encode($expiredFromAccepted->pluck('id')));
            foreach (User::whereIn('id', $expiredFromAccepted->pluck('user_id'))->get() as $u) {
                $this->info($u->email.','.$u->first_name);
            }
        }
        if ($mode == 5) {
            $this->info(json_encode($incomplete));
            foreach (User::whereIn('id', $incomplete)->get() as $u) {
                $this->info($u->email);
            }
        }
        if ($mode == 6) {
            $rsvpDriving = Application::where('rsvp', true)->where('completed_calculated', true)->get()->pluck('user_id');
            $this->info(json_encode($rsvpDriving));
            foreach (User::whereIn('id', $rsvpDriving)->with('application', 'application.school')->get() as $user) {
                if ($user->application->school && $user->application->school->transit_method == 'car') {
                    $this->info($user->email.','.$user->first_name);
                }
            }
        }
        if ($mode == 7) {
            $rsvp = Application::where('rsvp', true)->where('completed_calculated', true)->get()->pluck('user_id');
            $this->info(json_encode($rsvp));
            foreach (User::whereIn('id', $rsvp)->with('application', 'application.school')->get() as $user) {
                $this->info($user->id
                    ."\t".$user->application->id
                    ."\t".$user->email
                    ."\t".$user->first_name
                    ."\t".$user->last_name
                    ."\t".$user->phone
                    ."\t".($user->application->school ? $user->application->school->name : 'no school???')
                    ."\t".$user->getHashIDAttribute()
                );
            }
        }
    }
}

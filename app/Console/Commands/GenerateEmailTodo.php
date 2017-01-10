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
        $mode = $this->ask('mode?');
        $this->info('mode: '.$mode);

        $toAcceptFromNull = Application::whereNull('emailed_decision')
                    ->where('decision', Application::DECISION_ACCEPT)
                    ->get()->lists('user_id');
        $toWaitlistFromNull = Application::whereNull('emailed_decision')
                    ->where('decision', Application::DECISION_WAITLIST)
                    ->get()->lists('user_id');
        $toAcceptFromWaitlist = Application::where('emailed_decision', Application::DECISION_WAITLIST)
                    ->where('decision', Application::DECISION_ACCEPT)
                    ->get()->lists('user_id');
        $expiredFromAccepted = Application::where('emailed_decision', Application::DECISION_ACCEPT)
                    ->where('decision', Application::DECISION_EXPIRED)
                    ->get()->lists('user_id');

        $incomplete = Application::where('completed_calculated', false)->get()->lists('user_id');

        if ($mode == 1) {
            foreach (User::whereIn('id', $toAcceptFromNull)->get() as $u) {
                $this->info($u->email.' '.$u->first_name);
            }
        }
        if ($mode == 2) {
            foreach (User::whereIn('id', $toWaitlistFromNull)->get() as $u) {
                $this->info($u->email.' '.$u->first_name);
            }
        }
        if ($mode == 3) {
            foreach (User::whereIn('id', $toAcceptFromWaitlist)->get() as $u) {
                $this->info($u->email.' '.$u->first_name);
            }
        }
        if ($mode == 4) {
            foreach (User::whereIn('id', $expiredFromAccepted)->get() as $u) {
                $this->info($u->email.' '.$u->first_name);
            }
        }
        if ($mode == 5) {
            foreach (User::whereIn('id', $incomplete)->get() as $u) {
                $this->info($u->email);
            }
        }
    }
}

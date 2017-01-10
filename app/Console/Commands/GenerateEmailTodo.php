<?php

namespace App\Console\Commands;

use App\Models\Application;
use App\Models\User;
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
        $this->info("MODES:");
        $this->info("1: no decision -> accept");
        $this->info("2: no decision -> waitlist");
        $this->info("3: waitlist -> accept");
        $this->info("4: accept -> expired");
        $this->info("5: incomplete apps");
        $mode = $this->ask('mode?');
        $this->info("mode: ".$mode);

        $toAcceptFromNull = Application::whereNull('emailed_decision')
                    ->where('decision',Application::DECISION_ACCEPT)
                    ->get();
        $toWaitlistFromNull = Application::whereNull('emailed_decision')
                    ->where('decision',Application::DECISION_WAITLIST)
                    ->get();
        $toAcceptFromWaitlist = Application::where('emailed_decision',Application::DECISION_WAITLIST)
                    ->where('decision',Application::DECISION_ACCEPT)
                    ->get();
        $expiredFromAccepted = Application::where('emailed_decision',Application::DECISION_ACCEPT)
                    ->where('decision',Application::DECISION_EXPIRED)
                    ->get();

        $incomplete = Application::where('completed_calculated',false)->get()->lists('user_id');


        if($mode==1) {
            $this->info(json_encode($toAcceptFromNull->lists('id')));
            foreach (User::whereIn('id', $toAcceptFromNull->lists('user_id'))->get() as $u)
                $this->info($u->email . " " . $u->first_name);
        }
        if($mode==2) {
            $this->info(json_encode($toWaitlistFromNull->lists('id')));
            foreach (User::whereIn('id', $toWaitlistFromNull->lists('user_id'))->get() as $u)
                $this->info($u->email . " " . $u->first_name);
        }
        if($mode==3) {
            $this->info(json_encode($toAcceptFromWaitlist->lists('id')));
            foreach (User::whereIn('id', $toAcceptFromWaitlist->lists('user_id'))->get() as $u)
                $this->info($u->email . " " . $u->first_name);
        }
        if($mode==4) {
            $this->info(json_encode($expiredFromAccepted->lists('id')));
            foreach (User::whereIn('id', $expiredFromAccepted->lists('user_id'))->get() as $u)
                $this->info($u->email . " " . $u->first_name);
        }
        if($mode==5) {
            $this->info(json_encode($incomplete));
            foreach (User::whereIn('id', $incomplete)->get() as $u)
                $this->info($u->email);
        }

            }
}

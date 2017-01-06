<?php

namespace App\Console\Commands;

use App\Models\Application;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ProcessExpiredRSVP extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'applications:expiredrsvp';

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
        foreach (Application::where('decision',Application::DECISION_ACCEPT)->get() as $app)
        {
            $this->info($app->id);
            if(Carbon::parse($app->rsvp_deadline)->lt(Carbon::now())) {
                $app->decision = Application::DECISION_EXPIRED;
                $app->save();
            }
        }
    }
}

<?php

namespace App\Console\Commands;

use App\Http\Controllers\API\GeneralController;
use App\Models\Application;
use App\Models\User;
use Illuminate\Console\Command;
use Mockery\CountValidator\Exception;
use Psy\Exception\ErrorException;

class SponsorDump extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:sponsordump';

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
        if(!is_dir(sys_get_temp_dir()."/resumes/"))
            mkdir(sys_get_temp_dir()."/resumes/");

        $this->info("saving to: ".sys_get_temp_dir()."/resumes/");
        $apps = Application::with('user')->get();
        foreach ($apps as $app) {
            if($app->rsvp && $app->resume_uploaded) {
                $userId = $app->user->id;
//                $this->info($userId);
                $resumeURL = GeneralController::resumeUrl($userId,'get');
//                $this->info($resumeURL);

                $tmpPDF = sys_get_temp_dir()."/resumes/".$userId.".pdf";
                try {
                    copy($resumeURL, $tmpPDF);
                    $this->info($app->user->first_name.",".$app->user->last_name.",".$app->user->email.",".$app->github.",".$app->linkedin.",".$app->gender.",".$app->major.",".$app->grad_year.",".$app->school->name.",".$app->id.".pdf");
                }
                catch (\ErrorException $e)
                {
                    //$this->info('oops');
                }
            }
        }
    }
}

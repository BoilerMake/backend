<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Application;
use Illuminate\Console\Command;
use App\Http\Controllers\GeneralController;

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
        if (! is_dir(sys_get_temp_dir().'/resumes/')) {
            mkdir(sys_get_temp_dir().'/resumes/');
        }

        $this->info('saving to: '.sys_get_temp_dir().'/resumes/');
        $apps = Application::with('user')->get();
        foreach ($apps as $app) {
            if ($app->checked_in_at && $app->resume_uploaded) {
                $userId = $app->user->id;
//                $this->info($userId);
                $resumeURL = GeneralController::resumeUrl($userId, 'get');
//                $this->info($resumeURL);

                $resumeFilename = $app->user->first_name.'_'.$app->user->last_name.'_'.$app->user->id;
                $tmpPDF = sys_get_temp_dir().'/resumes/'.$resumeFilename.'.pdf';
                try {
                    copy($resumeURL, $tmpPDF);
                    $this->info($app->user->first_name."\t".$app->user->last_name."\t".$app->user->email."\t".$app->github."\t".$app->linkedin."\t".$app->gender."\t".$app->major."\t".$app->grad_year."\t".$app->school->name."\t".$resumeFilename.'.pdf'."\t".$resumeURL);
                } catch (\ErrorException $e) {
                    //$this->info('oops');
                }
            }
        }
    }
}

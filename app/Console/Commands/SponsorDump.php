<?php

namespace App\Console\Commands;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Application;
use Illuminate\Console\Command;
use Zipper;
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
        $resumeDir = public_path().'/r/';
        $resumeSecret = substr(md5(env('APP_KEY')),0,8);
        if (! is_dir($resumeDir)) {
            mkdir($resumeDir);
        }
        $resumeDir.= $resumeSecret.'/';
        if (! is_dir($resumeDir)) {
            mkdir($resumeDir);
        }

        $this->info('saving to: '.$resumeDir);
        $apps = Application::with('user')->get();
        foreach ($apps as $app) {
            if ($app->rsvp && $app->resume_uploaded) {
                $resumeURL = $app->user->resumeURL();
                $resumeFilename = $app->user->first_name.'_'.$app->user->last_name.'_'.$app->user->getHashIDAttribute().$app->user->id;
                $tmpPDF = $resumeDir.$resumeFilename.'.pdf';
                $publicResumeURL = env('APP_URL').'/r/'.$resumeSecret.$resumeFilename.'.pdf';
                try {
                    copy($resumeURL, $tmpPDF);
                    $this->info('S3 -> local '.$tmpPDF);
                    $this->info($app->user->first_name
                        ."\t".$app->user->last_name
                        ."\t".$app->user->email
                        ."\t".$app->github
                        ."\t".$app->linkedin
                        ."\t".$app->gender
                        ."\t".$app->major
                        ."\t".$app->grad_year
                        ."\t".$app->school->name
                        ."\t".$resumeFilename.'.pdf'
                        ."\t".$publicResumeURL);
                } catch (\ErrorException $e) {
                    //$this->info('oops');
                }
            }
        }
        $files = glob($resumeDir.'*');
        Zipper::make('public/r/'.$resumeSecret.'_resume_archive.zip')->folder('boilermake5_resumes_pre_event')->add($files)->close();
        $this->info('public/r/'.$resumeSecret.'_resume_archive.zip');
    }
}

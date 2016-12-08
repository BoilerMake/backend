<?php

namespace App\Console\Commands;

use App\Models\Application;
use Illuminate\Console\Command;

class CalculateApplications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'applications:calculate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculates application completed attr';

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
        foreach (Application::all() as $app) {
            $app->completed_calculated=$app->completed;
            $app->save();
        }
    }
}

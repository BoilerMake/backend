<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use App\Http\Controllers\API\UsersController;

class GenerateAccessCards extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:cards';

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
        foreach (User::with('application')->get() as $user) {
            $user->card_image = null;
            $user->save();
            if ($user->hasRole('exec')) {
                UsersController::generateAccessCardImage($user->id);
            } elseif ($user->hasRole('hacker') && $user->application->rsvp) {
                UsersController::generateAccessCardImage($user->id);
            }
        }
        $this->info('stitching...');
        UsersController::stitchAccessCards();

        $this->info('done');
//        UsersController::generateAccessCardImage(1);
//        UsersController::generateAccessCardImage(16);
    }
}

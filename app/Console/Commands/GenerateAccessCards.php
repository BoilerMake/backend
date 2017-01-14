<?php

namespace App\Console\Commands;

use App\Http\Controllers\API\UsersController;
use App\Models\User;
use Illuminate\Console\Command;

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
        foreach (User::all() as $user)
            UsersController::generateAccessCardImage($user->id);
//        UsersController::generateAccessCardImage(1);
//        UsersController::generateAccessCardImage(16);
    }
}

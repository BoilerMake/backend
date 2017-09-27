<?php

namespace App\Console\Commands;

use App\Models\Card;
use App\Models\User;
use Illuminate\Console\Command;
use App\Http\Controllers\ImageController;

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

//        ImageController::generateTableNumberImage(3);
//        ImageController::generateTableNumberImage(5);
//        return;


        foreach (User::with('application', 'application.school')->get() as $user) {
            if ($user->hasRole('hacker') && $user->application && $user->application->rsvp) {
                $schoolName = '';
                if ($user->application && $user->application->school) {
                    $schoolName = $user->application->school->display_name
                            ? $user->application->school->display_name
                            : $user->application->school->name;
                }
                Card::create([
                    'name'      => $user->getNameAttribute(),
                    'subtitle'  => $schoolName,
                    'skills'    => $user->application->skills,
                    'role'      => User::ROLE_HACKER,
                ]);
            }
        }

        foreach (Card::all() as $card) {
            ImageController::generateAccessCardImage($card);
        }

        $this->info('stitching...');
        ImageController::stitchAccessCards(User::ROLE_HACKER);
        $this->info('stitching...');
        ImageController::stitchAccessCards(User::ROLE_ORGANIZER);
        $this->info('stitching...');
        ImageController::stitchAccessCards(User::ROLE_SPONSOR);
        $this->info('stitching...');
        ImageController::stitchAccessCards(User::ROLE_GUEST);
        $this->info('done');
    }
}

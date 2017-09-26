<?php

namespace App\Console\Commands;

use App\Models\Card;
use Log;
use App\Models\User;
use Illuminate\Console\Command;
use App\Http\Controllers\CardController;

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

//        CardController::generateTableNumberImage(3);
//        CardController::generateTableNumberImage(5);
//        return;

        Card::where('role',User::ROLE_HACKER)->delete();

        foreach (User::with('application', 'application.school')->get() as $user) {
            if($user->hasRole('hacker') && $user->application && $user->application->rsvp) {

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
                    'role'      => User::ROLE_HACKER
                ]);
            }
        }

        foreach (Card::all() as $card ) {
            CardController::generateAccessCardImage($card);
        }

        $this->info('stitching...');
        CardController::stitchAccessCards(User::ROLE_HACKER);
        CardController::stitchAccessCards(User::ROLE_ORGANIZER);
        CardController::stitchAccessCards(User::ROLE_SPONSOR);
        $this->info('done');
    }
}

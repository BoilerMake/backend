<?php

namespace App\Console\Commands;

use App\Models\Card;
use App\Models\User;
use Illuminate\Console\Command;
use App\Http\Controllers\ImageController;

class GenerateAccessCards extends Command
{
    protected $signature = 'users:cards';
    protected $description = 'Command description';
    public function __construct()
    {
        parent::__construct();
    }
    public function handle()
    {
        /**
         * Populate `card` table with data from all rsvp'd hackers.
         */
        foreach (User::with('application', 'application.school')->get() as $user) {
            if ($user->hasRole('hacker') && $user->application && $user->application->rsvp) {
                $schoolName = '';
                if ($user->application && $user->application->school) {
                    //use display_name of school if available bc it's shorter, or fall back to full name
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
        /*
         * generate access cards for all roles based on `cards` table
         */
        foreach (Card::all() as $card) {
            ImageController::generateAccessCardImage($card);
        }
        /*
         * stitch cards into 6up sheets
         */
        foreach (User::ROLES as $role) {
            $this->info("stiching access cards for ${role}");
            ImageController::stitchAccessCards($role);
        }
    }
}

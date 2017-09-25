<?php

namespace App\Console\Commands;

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

        CardController::generateTableNumberImage(3);
        CardController::generateTableNumberImage(5);
        return;

        //todo again:
        //puzzle
        //exec
        $execs = User::whereHas('roles', function ($q) {
            $q->where('name', 'exec');
        })->pluck('id')->toArray();
        $toGenerate = $execs;
        //newly added
        $newlyAdded = [72381];
        $toGenerate = array_merge($toGenerate, $newlyAdded);

        Log::info($toGenerate);

        foreach (User::with('application')->get() as $user) {
            $user->card_image = null;
            $user->save();
            if ($user->hasRole('exec')) {
                CardController::generateAccessCardImage($user->id);
            } elseif ($user->hasRole('hacker') && $user->application->rsvp) {
                CardController::generateAccessCardImage($user->id);
            }
        }
        $this->info('stitching...');
        //        CardController::stitchAccessCards($toGenerate);
        CardController::stitchAccessCards();

        $this->info('done');
        //        CardController::generateAccessCardImage(1);
//        CardController::generateAccessCardImage(16);
    }
}

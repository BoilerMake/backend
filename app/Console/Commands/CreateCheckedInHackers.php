<?php

namespace App\Console\Commands;

use Hash;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Application;
use Illuminate\Console\Command;
use App\Http\Controllers\ExecController;

class CreateCheckedInHackers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hacker:createcheckedin
                            {first_name : user first name}
                            {last_name : user last name}
                            {school_id : school id }
                            {email : user email}
                            {--password= : user password, otherwise reset email will be auto sent}';

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
        $this->info('first name: '.$this->argument('first_name'));
        $this->info('last name: '.$this->argument('last_name'));
        $this->info('email: '.$this->argument('email'));
        $this->info('school_id: '.$this->argument('school_id'));

        $password = Hash::make(Carbon::now().env('APP_KEY'));

        $user = User::addNew($this->argument('email'), $password, false);
        $user->first_name = $this->argument('first_name');
        $user->last_name = $this->argument('last_name');
        $user->save();

        $application = Application::where('user_id', $user->id)->first();
        $application->school_id = $this->argument('school_id');
        $application->save();

        $test = new ExecController();
        $test->checkInUser($user->id);
    }
}

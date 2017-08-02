<?php

namespace App\Console\Commands;

use Hash;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Console\Command;

class CreateUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:create
                            {first_name : user first name}
                            {last_name : user last name}
                            {email : user email}
                            {roles=hacker : user roles, comma seperated}
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

        $roles = explode(',', $this->argument('roles'));
        $this->info('roles:'.print_r($roles, true));

        $password = $this->option('password');
        $shouldSendPasswordReset = false;
        if (! $password) {
            $password = Hash::make(Carbon::now().env('APP_KEY'));
            $shouldSendPasswordReset = true;
        }
        $password = Hash::make($password);

        $user = User::addNew($this->argument('email'), $password, true, $roles);
        $user->first_name = $this->argument('first_name');
        $user->last_name = $this->argument('last_name');
        $user->save();

        if ($shouldSendPasswordReset) {
            $user->sendPasswordResetEmail();
        }
    }
}

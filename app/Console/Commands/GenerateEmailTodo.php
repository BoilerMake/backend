<?php

namespace App\Console\Commands;

use App\Models\Application;
use App\Models\User;
use Illuminate\Console\Command;

class GenerateEmailTodo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:generateemails';

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
        $toAccept = Application::whereNull('emailed_decision')->where('decision',3)->get()->lists('user_id');
        foreach(User::whereIn('id',$toAccept)->get() as $u) {
            $this->info($u->first_name." ".$u->email);
        }

    }
}

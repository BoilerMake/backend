<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\ImageController;

class GenerateTableNumbers extends Command
{
    protected $signature = 'misc:tablenumbers';
    protected $description = 'Make table numbers';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        for($x=1; $x<150; $x++) {
            ImageController::generateTableNumberImage($x);
        }
    }
}

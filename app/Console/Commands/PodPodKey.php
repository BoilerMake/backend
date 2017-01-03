<?php

namespace App\Console\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;

class PodPodKey extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'podpod:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set the PodPod secret key';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $key = $this->generateRandomKey();
        $this->setKeyInEnvironmentFile($key);
        $this->laravel['config']['app.podpod'] = $key;
        $this->info("PodPod key [$key] set successfully.");
    }

    /**
     * Set the application key in the environment file.
     *
     * @param  string  $key
     * @return void
     */
    protected function setKeyInEnvironmentFile($key)
    {
        file_put_contents($this->laravel->environmentFilePath(), str_replace(
            'PODPOD_KEY='.$this->laravel['config']['app.podpod'],
            'PODPOD_KEY='.$key,
            file_get_contents($this->laravel->environmentFilePath())
        ));
    }

    /**
     * Generate a random key for the application.
     *
     * @return string
     */
    protected function generateRandomKey()
    {
        return Str::quickRandom(32);
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;

class JWTKey extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jwtkey:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set the JWT secret key in .env';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $key = $this->generateRandomKey();
        $this->setKeyInEnvironmentFile($key);
        $this->laravel['config']['jwt.secret'] = $key;
        $this->info("JWT key [$key] set successfully.");
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
            'JWT_SECRET='.$this->laravel['config']['jwt.secret'],
            'JWT_SECRET='.$key,
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
        return Str::random(32);
    }
}

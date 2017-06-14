<?php

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| The first thing we will do is create a new Laravel application instance
| which serves as the "glue" for all the components of Laravel, and is
| the IoC container for the system binding all of the various parts.
|
*/

$app = new Illuminate\Foundation\Application(
    realpath(__DIR__.'/../')
);

/*
|--------------------------------------------------------------------------
| Bind Important Interfaces
|--------------------------------------------------------------------------
|
| Next, we need to bind some important interfaces into the container so
| we will be able to resolve them when needed. The kernels serve the
| incoming requests to this application from both the web and CLI.
|
*/

$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

use Monolog\Handler\StreamHandler;

$app->configureMonologUsing(function ($monolog) {
    if (env('GREYLOG_ADDRESS')) {
        //ship off logs to greylog
        $transport = new \Gelf\Transport\UdpTransport(env('GREYLOG_ADDRESS'), env('GREYLOG_PORT'));
        $publisher = new \Gelf\Publisher($transport, null, 'bm');
        $monolog->pushHandler(new \Monolog\Handler\GelfHandler($publisher));
    }

    //re-setup default laravel log style since we're overriding Monolog
    $infoStreamHandler = new StreamHandler(storage_path('/logs/laravel.log'));
    $infoStreamHandler->setFormatter(new \Monolog\Formatter\LineFormatter(null, null, true, true));
    $monolog->pushHandler($infoStreamHandler);
});
/*
|--------------------------------------------------------------------------
| Return The Application
|--------------------------------------------------------------------------
|
| This script returns the application instance. The instance is given to
| the calling script so we can separate the building of the instances
| from the actual running of the application and sending responses.
|
*/

return $app;

<?php

use App\Application;
use Illuminate\Events\EventServiceProvider;
use Illuminate\Log\LogServiceProvider;
use Illuminate\Filesystem\FilesystemServiceProvider;
use Illuminate\Contracts\Console\Kernel as KernelContract;
use Illuminate\Support\Facades\Facade;
use Illuminate\Foundation\AliasLoader;

require __DIR__ . '/../vendor/autoload.php';

$app = new Application();

// Bind important interfaces
$app->singleton(
    Illuminate\Contracts\Container\Container::class,
    function () use ($app) {
        return $app;
    }
);

// Register service providers
(new EventServiceProvider($app))->register();
(new LogServiceProvider($app))->register();
(new FilesystemServiceProvider($app))->register();

// Bind the Kernel
$app->singleton(KernelContract::class, function ($app) {
    return new App\Console\Kernel($app);
});

// Set the facade application
Facade::setFacadeApplication($app);

// Register aliases using AliasLoader
$loader = AliasLoader::getInstance();
$loader->alias('File', Illuminate\Support\Facades\File::class);
// Add other aliases as needed
$loader->register();

// Return the application instance
return $app;

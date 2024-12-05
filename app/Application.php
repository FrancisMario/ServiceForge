<?php

namespace App;

use Illuminate\Container\Container;

class Application extends Container
{
    /**
     * Determine if the application is running in the console.
     */
    public function runningInConsole()
    {
        return true;
    }

    /**
     * Determine if the application is running unit tests.
     */
    public function runningUnitTests()
    {
        return false;
    }

    /**
     * Get the current application environment.
     */
    public function environment()
    {
        return 'production';
    }

      /**
     * Get the base path of the application.
     */
    public function basePath($path = '')
    {
        return __DIR__ . '/..' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

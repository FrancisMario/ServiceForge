<?php

namespace App\Console;

use Illuminate\Contracts\Console\Kernel as KernelContract;
use Illuminate\Console\Application as Artisan;
use Illuminate\Console\Scheduling\Schedule;
use App\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Kernel implements KernelContract
{
    protected $app;
    protected $artisan;
    protected $commands = [
        \App\Console\Commands\GenerateService::class,
        \App\Console\Commands\ProtoGenerate::class,
        // Add other commands here
    ];

    protected $lastOutput;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->artisan = new Artisan($app, $app['events'], '1.0');

        $this->bootstrap();
    }

    /**
     * Handle an incoming console command.
     */
    public function handle($input, $output = null)
    {
        return $this->artisan->run($input, $output);
    }

    /**
     * Terminate the application.
     */
    public function terminate($input, $status)
    {
        // Termination logic if needed
    }

    /**
     * Bootstrap the application for artisan commands.
     */
    public function bootstrap()
    {
        // Register commands
        foreach ($this->commands as $command) {
            $this->artisan->add($this->app->make($command));
        }
    }

    /**
     * Define the application's command schedule.
     */
    public function schedule(Schedule $schedule)
    {
        // Define scheduled tasks if needed
    }

    /**
     * Run an Artisan console command by name.
     */
    public function call($command, array $parameters = [], $outputBuffer = null)
    {
        $parameters['command'] = $command;

        $input = new \Symfony\Component\Console\Input\ArrayInput($parameters);

        if ($outputBuffer === null) {
            $output = new \Symfony\Component\Console\Output\NullOutput();
        } else {
            $output = new \Symfony\Component\Console\Output\BufferedOutput();
        }

        $this->handle($input, $output);

        if ($outputBuffer !== null) {
            $this->lastOutput = $output->fetch();
        }

        return 0; // Return exit code
    }

    /**
     * Queue an Artisan console command by name.
     */
    public function queue($command, array $parameters = [])
    {
        // Implement logic to queue a command (if necessary)
        // For this minimal setup, you can leave this method as is
        throw new \Exception('Queueing commands is not implemented.');
    }

    /**
     * Get all of the commands registered with the console.
     */
    public function all()
    {
        return $this->artisan->all();
    }

    /**
     * Get the output for the last run command.
     */
    public function output()
    {
        return $this->lastOutput ?? '';
    }
}

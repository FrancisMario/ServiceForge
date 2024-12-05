<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class DeployService extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deploy:service {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deploy a service by building its Docker image and running the container';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            $serviceName = $this->argument('name');
            $this->info("Deploying service: {$serviceName}");

            $servicePath = base_path("services/{$serviceName}");
            $dockerImage = "your-docker-repo/{$serviceName}:latest";
            $containerName = "{$serviceName}_grpc";

            // Check if the service exists
            if (!File::exists($servicePath)) {
                throw new \Exception("Service '{$serviceName}' does not exist at path {$servicePath}.");
            }

            // Step 1: Build the Docker image
            $this->info("Building Docker image: {$dockerImage}");
            $process = Process::fromShellCommandline("docker build -t {$dockerImage} .", $servicePath);
            $process->setTimeout(600);
            $process->run(function ($type, $buffer) {
                $this->output->write($buffer);
            });

            if (!$process->isSuccessful()) {
                throw new \Exception("Docker build failed for service '{$serviceName}'.");
            }

            $this->info("Docker image built successfully.");

            // Step 2: Push the Docker image to the repository
            $this->info("Pushing Docker image to repository: {$dockerImage}");
            $process = Process::fromShellCommandline("docker push {$dockerImage}");
            $process->run(function ($type, $buffer) {
                $this->output->write($buffer);
            });

            if (!$process->isSuccessful()) {
                throw new \Exception("Docker push failed for image '{$dockerImage}'.");
            }

            $this->info("Docker image pushed successfully.");

            // Step 3: Stop and remove existing container if it exists
            $this->info("Stopping and removing existing Docker container (if any): {$containerName}");
            $process = Process::fromShellCommandline("docker stop {$containerName} && docker rm {$containerName}");
            $process->run(function ($type, $buffer) {
                $this->output->write($buffer);
            });
            // It's okay if the container doesn't exist; ignore errors

            // Step 4: Run the Docker container
            $this->info("Running Docker container: {$containerName}");
            $process = Process::fromShellCommandline("docker run -d -p 50051:50051 --name {$containerName} {$dockerImage}");
            $process->run(function ($type, $buffer) {
                $this->output->write($buffer);
            });

            if (!$process->isSuccessful()) {
                throw new \Exception("Docker run failed for container '{$containerName}'.");
            }

            $this->info("Docker container '{$containerName}' is running successfully.");

            return 0;
        } catch (\Exception $e) {
            $this->error("Deployment failed: " . $e->getMessage());
            return 1;
        }
    }
}

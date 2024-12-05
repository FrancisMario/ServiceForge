<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class ProtoGenerate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'proto:generate {service?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate PHP classes from proto files';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $serviceName = $this->argument('service');

        if ($serviceName) {
            $this->info("Generating proto classes for service: $serviceName");
            $services = [$serviceName];
        } else {
            $this->info("Generating proto classes for all services");
            $services = $this->getAllServices();
        }

        foreach ($services as $service) {
            $this->generateProtoClasses($service);
        }

        $this->info('Proto classes generation complete.');
        return 0;
    }

    protected function getAllServices()
    {
        $servicesPath = base_path('services');
        $directories = array_filter(glob($servicesPath . '/*'), 'is_dir');
        return array_map('basename', $directories);
    }

    protected function generateProtoClasses($serviceName)
    {
        $this->info("Processing service: $serviceName");

        $servicePath = base_path("services/{$serviceName}");
        $protoPath = "{$servicePath}/proto";
        $outputPath = "{$servicePath}/app/Grpc";

        if (!File::exists($protoPath)) {
            $this->error("Proto directory not found for service '{$serviceName}'.");
            return;
        }

        // Ensure output directory exists
        if (!File::exists($outputPath)) {
            File::makeDirectory($outputPath, 0755, true);
            $this->info("Created gRPC output directory at {$outputPath}.");
        }

        // Compile proto files
        // Ensure 'protoc' and 'grpc_php_plugin' are installed and accessible
        $process = new Process([
            'protoc',
            "--proto_path={$protoPath}",
            "--php_out={$outputPath}",
            "--grpc_out={$outputPath}",
            "--plugin=protoc-gen-grpc=/usr/local/bin/grpc_php_plugin", // Adjust path as needed
            "{$protoPath}/*.proto"
        ]);

        $process->setTimeout(300);
        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        if (!$process->isSuccessful()) {
            $this->error("Failed to generate proto classes for service '{$serviceName}'.");
            return;
        }

        $this->info("Proto classes generated for service '{$serviceName}'.");
    }
}

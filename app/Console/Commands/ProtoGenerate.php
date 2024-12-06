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
    $protoPath = realpath(base_path('shared/proto')); // Resolve shared proto path
    $outputPath = "{$servicePath}/app/Grpc";

    if (!$protoPath) {
        $this->error("Proto directory not found for shared files.");
        return;
    }

    // Ensure output directory exists
    if (!File::exists($outputPath)) {
        File::makeDirectory($outputPath, 0755, true);
        $this->info("Created gRPC output directory at {$outputPath}.");
    }

    // Compile proto files using grpc-tools
    $protoFiles = glob("{$protoPath}/*.proto");
    if (empty($protoFiles)) {
        $this->error("No .proto files found in shared proto directory.");
        return;
    }

    foreach ($protoFiles as $protoFile) {
        $this->info("Processing proto file: {$protoFile}");

        $process = new Process([
            'node',
            base_path('scripts/protoc-compile.js'),
            "--proto_path={$protoPath}",
            "--php_out={$outputPath}",
            "--grpc_out={$outputPath}",
            $protoFile
        ]);

        $process->setTimeout(300);
        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        if (!$process->isSuccessful()) {
            $this->error("Failed to generate proto classes for file '{$protoFile}': " . $process->getErrorOutput());
            return;
        }

        $this->info("Generated proto classes for file '{$protoFile}'.");
    }

    $this->info("Proto classes generation complete for service '{$serviceName}'.");
}

}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class GenerateService extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'service:generate {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a new service with gRPC support, Dockerfile, and Kubernetes manifests';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $serviceName = $this->argument('name');

            // Create various naming conventions
            $serviceNamePascal = ucfirst($serviceName); // e.g. "ExampleService"
            $serviceNameCamel = lcfirst($serviceNamePascal); // "exampleService"
            $serviceNameSnake = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $serviceNamePascal)); // "example_service"
            $serviceNameUpper = strtoupper($serviceNameSnake); // "EXAMPLE_SERVICE"

            $variables = [
                'SERVICE_NAME' => $serviceNamePascal,
                'service_name' => $serviceNameCamel,
                'service_snake' => $serviceNameSnake,
                'SERVICE_SNAKE' => $serviceNameUpper,
                'PACKAGE_NAME' => $serviceNameSnake, // Proto package name derived from the snake_case service name
            ];

            $this->info("Generating service: {$serviceName}");

            $servicePath = base_path("services/{$serviceName}");

            // Ensure shared/proto exists
            $sharedProtoPath = $this->ensureSharedProto();

            // Check required templates
            $this->ensureTemplatesExist();

            // Check if service already exists
            if (File::exists($servicePath)) {
                $this->error("Service '{$serviceName}' already exists.");
                return 1;
            }

            // Step 1: Create Laravel application
            $this->createLaravelApp($serviceName, $servicePath);

            // Step 2: Setup gRPC
            $this->setupGrpc($serviceName, $servicePath);

            // Step 3: Generate Kubernetes manifests
            $this->generateKubernetesManifests($serviceName, $servicePath, $variables);

            // Step 4: Create symlink to shared proto directory
            $this->createProtoSymlink($servicePath);

            // Step 5: Create gRPC server script
            $this->createFromTemplate('grpc_server.php.tpl', "{$servicePath}/grpc_server.php", $variables);

            // Step 6: Create Service Implementation
            // Uses {{SERVICE_NAME}} in class and proto references
            $this->createFromTemplate('ServiceImplementation.php.tpl', "{$servicePath}/app/Grpc/{$variables['SERVICE_NAME']}ServiceImplementation.php", $variables);

            // Step 7: Create Dockerfile
            $this->createFromTemplate('Dockerfile.tpl', "{$servicePath}/Dockerfile", $variables);

            // Step 8: Create .proto file from template
            $this->createFromTemplate('Service.proto.tpl', "{$sharedProtoPath}/{$variables['SERVICE_NAME']}Service.proto", $variables);

            $this->info("Service '{$serviceName}' has been generated successfully!");
            return 0;

        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return 1;
        }
    }

    protected function ensureSharedProto()
    {
        $sharedProtoPath = base_path('shared/proto');

        if (!File::exists($sharedProtoPath)) {
            File::makeDirectory($sharedProtoPath, 0755, true);
            $this->info("Created shared proto directory at {$sharedProtoPath}.");
        }

        // No default proto needed now since we rely on templates
        return $sharedProtoPath;
    }

    protected function ensureTemplatesExist()
    {
        $templatePath = base_path('templates');

        $requiredTemplates = [
            'Service.proto.tpl',
            'grpc_server.php.tpl',
            'ServiceImplementation.php.tpl',
            'Dockerfile.tpl',
            'deployment.yaml.tpl',
            'service.yaml.tpl',
        ];

        foreach ($requiredTemplates as $templateFile) {
            $fullPath = "{$templatePath}/{$templateFile}";

            if (!File::exists($fullPath)) {
                $this->error("Required template not found: {$fullPath}");
                throw new \Exception("Missing required template: {$templateFile}");
            }
        }

        $this->info('All required templates are present.');
    }

    protected function createLaravelApp($serviceName, $servicePath)
    {
        $this->info("Creating Laravel application...");

        $process = new Process(['composer', 'create-project', '--prefer-dist', 'laravel/laravel', $servicePath]);
        $process->setTimeout(600);
        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        if (!$process->isSuccessful()) {
            throw new \Exception('Failed to create Laravel application.');
        }

        $this->info('Laravel application created successfully.');
    }

    protected function setupGrpc($serviceName, $servicePath)
    {
        $this->info("Setting up gRPC support...");

        chdir($servicePath);

        $process = new Process(['composer', 'require', 'grpc/grpc', 'google/protobuf']);
        $process->setTimeout(300);
        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        if (!$process->isSuccessful()) {
            throw new \Exception('Failed to install gRPC dependencies.');
        }

        $this->info('gRPC support has been set up.');
    }

    protected function generateKubernetesManifests($serviceName, $servicePath, $variables)
    {
        $this->info("Generating Kubernetes manifests...");

        $k8sPath = "{$servicePath}/k8s";

        if (!File::exists($k8sPath)) {
            File::makeDirectory($k8sPath);
            $this->info("Created Kubernetes directory at {$k8sPath}.");
        }

        // deployment.yaml
        $deploymentContent = $this->renderTemplate(base_path("templates/deployment.yaml.tpl"), $variables);
        File::put("{$k8sPath}/deployment.yaml", $deploymentContent);
        $this->info("Kubernetes deployment manifest created at {$k8sPath}/deployment.yaml.");

        // service.yaml
        $serviceContent = $this->renderTemplate(base_path("templates/service.yaml.tpl"), $variables);
        File::put("{$k8sPath}/service.yaml", $serviceContent);
        $this->info("Kubernetes service manifest created at {$k8sPath}/service.yaml.");
    }

    protected function createProtoSymlink($servicePath)
    {
        $this->info("Creating symlink to shared proto directory...");

        $target = base_path('shared/proto');
        $link = "{$servicePath}/proto";

        if (File::exists($link)) {
            File::delete($link);
            $this->info("Existing proto symlink deleted.");
        }

        try {
            symlink($target, $link);
            $this->info('Symlink to shared proto directory created.');
        } catch (\Exception $e) {
            $this->error("Failed to create symlink: " . $e->getMessage());
            // Alternative: copy files instead of symlink if needed
            /*
            File::copyDirectory($target, $link);
            $this->info('Copied proto files instead of symlink.');
            */
        }
    }

    protected function createFromTemplate($templateFile, $destinationPath, $variables = [])
    {
        $templatePath = base_path("templates/{$templateFile}");
        $content = $this->renderTemplate($templatePath, $variables);
        $directory = dirname($destinationPath);

        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
            $this->info("Created directory at {$directory}.");
        }

        File::put($destinationPath, $content);
        File::chmod($destinationPath, 0755);

        $this->info("Created file from template: {$destinationPath}.");
    }

    protected function renderTemplate($templatePath, $variables = [])
    {
        if (!File::exists($templatePath)) {
            throw new \Exception("Template file not found: {$templatePath}");
        }

        $template = File::get($templatePath);

        foreach ($variables as $key => $value) {
            $template = str_replace("{{{$key}}}", $value, $template);
        }

        return $template;
    }
}

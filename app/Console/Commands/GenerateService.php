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
    protected $description = 'Generate a new service with its gRPC configuration and Kubernetes manifests';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            $serviceName = $this->argument('name');
            $this->info("Generating service: {$serviceName}");

            $servicePath = base_path("services/{$serviceName}");

            // Step 0: Ensure shared/proto exists
            $this->ensureSharedProto();

            // Ensure required templates are present
            $this->ensureTemplatesExist();

            // Check if the service already exists
            if (File::exists($servicePath)) {
                $this->error("Service '{$serviceName}' already exists.");
                return 1;
            }

            // Step 1: Create a new Laravel application
            $this->createLaravelApp($serviceName, $servicePath);

            // Step 2: Set up gRPC support
            $this->setupGrpc($serviceName, $servicePath);

            // Step 3: Generate Kubernetes manifests
            $this->generateKubernetesManifests($serviceName, $servicePath);

            // Step 4: Create symlink to shared proto directory
            $this->createProtoSymlink($servicePath);

            // Step 5: Create gRPC server script from template
            $this->createFromTemplate(
                'grpc_server.php.tpl',
                "{$servicePath}/grpc_server.php",
                ['SERVICE_NAME' => $serviceName]
            );

            // Step 6: Create gRPC Service Implementation from template
            $this->createFromTemplate(
                'HelloServiceImplementation.php.tpl',
                "{$servicePath}/app/Grpc/HelloServiceImplementation.php",
                ['SERVICE_NAME' => $serviceName]
            );

            // Step 7: Create Dockerfile from template
            $this->createFromTemplate(
                'Dockerfile.tpl',
                "{$servicePath}/Dockerfile",
                ['SERVICE_NAME' => $serviceName]
            );

            $this->info("Service '{$serviceName}' has been generated successfully!");
            return 0;
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Ensures that all required templates are present.
     *
     * @throws \Exception
     */
    protected function ensureTemplatesExist()
    {
        $templatePath = base_path('templates');

        $requiredTemplates = [
            'grpc_server.php.tpl',
            'HelloServiceImplementation.php.tpl',
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

    /**
     * Ensures the shared proto directory and sample proto file exist.
     */
    protected function ensureSharedProto()
    {
        $sharedProtoPath = base_path('shared/proto');

        if (!File::exists($sharedProtoPath)) {
            File::makeDirectory($sharedProtoPath, 0755, true);
            $this->info("Created shared proto directory at {$sharedProtoPath}.");
        }

        // Check if HelloService.proto exists, if not, create it
        $helloProto = "{$sharedProtoPath}/HelloService.proto";

        if (!File::exists($helloProto)) {
            $this->info("Creating sample HelloService.proto in shared/proto.");
            $helloProtoContent = <<<EOT
syntax = "proto3";

package hello;

// The greeting service definition.
service HelloService {
  // Sends a greeting
  rpc SayHello (HelloRequest) returns (HelloReply) {}
}

// The request message containing the user's name.
message HelloRequest {
  string name = 1;
}

// The response message containing the greetings.
message HelloReply {
  string message = 1;
}
EOT;
            File::put($helloProto, $helloProtoContent);
            $this->info("HelloService.proto created.");
        }
    }

    /**
     * Creates a new Laravel application using Composer.
     *
     * @param string $serviceName
     * @param string $servicePath
     */
    protected function createLaravelApp($serviceName, $servicePath)
    {
        $this->info("Creating Laravel application...");

        // Use Composer to create a new Laravel project
        $process = new Process(['composer', 'create-project', '--prefer-dist', 'laravel/laravel', $servicePath]);
        $process->setTimeout(600); // Increase timeout as Laravel installation can take time
        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        if (!$process->isSuccessful()) {
            throw new \Exception('Failed to create Laravel application.');
        }

        $this->info('Laravel application created successfully.');
    }

    /**
     * Sets up gRPC support by installing necessary Composer packages.
     *
     * @param string $serviceName
     * @param string $servicePath
     */
    protected function setupGrpc($serviceName, $servicePath)
    {
        $this->info("Setting up gRPC support...");

        // Navigate to the service directory
        chdir($servicePath);

        // Require necessary Composer packages
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

    /**
     * Generates Kubernetes manifests from templates.
     *
     * @param string $serviceName
     * @param string $servicePath
     */
    protected function generateKubernetesManifests($serviceName, $servicePath)
    {
        $this->info("Generating Kubernetes manifests...");

        $k8sPath = "{$servicePath}/k8s";

        // Create k8s directory
        if (!File::exists($k8sPath)) {
            File::makeDirectory($k8sPath);
            $this->info("Created Kubernetes directory at {$k8sPath}.");
        }

        // Define variables for templates
        $variables = ['SERVICE_NAME' => $serviceName];

        // Generate deployment.yaml
        $deploymentContent = $this->renderTemplate(base_path("templates/deployment.yaml.tpl"), $variables);
        File::put("{$k8sPath}/deployment.yaml", $deploymentContent);
        $this->info("Kubernetes deployment manifest created at {$k8sPath}/deployment.yaml.");

        // Generate service.yaml
        $serviceContent = $this->renderTemplate(base_path("templates/service.yaml.tpl"), $variables);
        File::put("{$k8sPath}/service.yaml", $serviceContent);
        $this->info("Kubernetes service manifest created at {$k8sPath}/service.yaml.");
    }

    /**
     * Creates a symlink to the shared proto directory.
     *
     * @param string $servicePath
     */
    protected function createProtoSymlink($servicePath)
    {
        $this->info("Creating symlink to shared proto directory...");

        $target = base_path('shared/proto');
        $link = "{$servicePath}/proto";

        if (File::exists($link)) {
            File::delete($link);
            $this->info("Existing proto symlink deleted.");
        }

        // Create symlink (works on Unix systems)
        try {
            symlink($target, $link);
            $this->info('Symlink to shared proto directory created.');
        } catch (\Exception $e) {
            $this->error("Failed to create symlink: " . $e->getMessage());
            // Alternative for Windows or failure: copy files instead of symlink
            // Uncomment the lines below if you prefer copying over symlinking
            /*
            File::copyDirectory($target, $link);
            $this->info('Copied proto files instead of symlink.');
            */
        }
    }

    /**
     * Creates a file from a template with variable replacements.
     *
     * @param string $templateFile
     * @param string $destinationPath
     * @param array $variables
     */
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

    /**
     * Renders a template file by replacing placeholders with actual values.
     *
     * @param string $templatePath
     * @param array $variables
     * @return string
     * @throws \Exception
     */
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

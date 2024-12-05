


![ServiceForge](https://github.com/user-attachments/assets/d39b668b-e666-40e7-8053-51939f7d4c76)


**Service Monorepo**! is designed to manage multiple Laravel-based microservices, providing a foundation for scalable, modular application development. It leverages Docker for deployment, with the potential for Kubernetes orchestration in the future. Services in this monorepo communicate via **gRPC** for efficient inter-service communication, while retaining the ability to expose traditional REST APIs.

---

## **Monorepo Overview**

This monorepo serves as the central hub for managing all microservices and shared resources. It provides tools to generate, manage, and deploy services efficiently.

### **Folder Structure**

Here is the primary structure of the monorepo:

```
monorepo/
├── app/
│   └── Console/
│       └── Commands/
│           ├── GenerateService.php     # Command to generate new services
│           ├── DeployService.php       # Command to deploy services via Docker
├── shared/
│   └── proto/
│       ├── HelloService.proto          # Shared gRPC definitions
│       └── (Other .proto files)
├── services/
│   └── (Generated Laravel services live here)
├── templates/
│   ├── Dockerfile.tpl                  # Dockerfile template for services
│   ├── grpc_server.php.tpl             # gRPC server template
│   ├── deployment.yaml.tpl             # Kubernetes deployment template (future use)
│   ├── service.yaml.tpl                # Kubernetes service template (future use)
├── artisan                             # Root Artisan command for managing the monorepo
├── composer.json                       # Dependencies for the monorepo
└── README.md                           # This documentation

```

### **Key Components**

1. **app/**: Contains custom Artisan commands to manage the monorepo.
2. **shared/proto/**: Central repository for `.proto` files defining gRPC services and shared types.
3. **services/**: Directory where all generated microservices (Laravel applications) are stored.
4. **templates/**: Templates used during service generation for Docker, gRPC, and Kubernetes.

---

## **Root Artisan Command**

At the root of the monorepo, a custom **Artisan** CLI tool is provided to manage the repository. This tool abstracts common tasks like generating and deploying services.

### **Available Commands**

1. **Generate a Service**:
    
    ```bash
    php artisan service:generate {ServiceName}
    
    ```
    
    - Creates a new Laravel-based service.
    - Configures gRPC support, Dockerfile, and symlinks to shared `.proto` files.
2. **Deploy a Service**:
    
    ```bash
    php artisan deploy:service {ServiceName}
    
    ```
    
    - Builds the Docker image for the service.
    - Pushes the image to your Docker repository.
    - Starts the service as a Docker container.

---

## **Service Generation**

### **Generating a New Service**

To create a new service, run the following command:

```bash
php artisan service:generate ExampleService

```

**What Happens:**

- A new Laravel application is created in `services/ExampleService`.
- gRPC dependencies are installed, and a gRPC server script is added.
- A Dockerfile is generated to containerize the service.
- A symlink to `shared/proto/` is created for accessing shared `.proto` files.

---

## **Service Structure**

Each generated service is a fully functional Laravel application with additional gRPC support.

```
services/ExampleService/
├── app/
│   ├── Grpc/
│   │   └── HelloServiceImplementation.php # gRPC service implementation
│   └── Http/
├── bootstrap/
├── config/
├── grpc_server.php                        # gRPC server script
├── proto/ -> ../../shared/proto/          # Symlink to shared proto files
├── Dockerfile                             # Docker configuration
├── vendor/
└── composer.json

```

### **Key Files**

1. **grpc_server.php**: Starts the gRPC server for the service.
2. **proto/**: Symlink to shared `.proto` definitions for consistency.
3. **Dockerfile**: Configures the service for deployment as a Docker container.

---

## **gRPC and Inter-Service Communication**

### **What is gRPC?**

gRPC is a high-performance RPC (Remote Procedure Call) framework that enables efficient communication between services. It uses Protocol Buffers (**protobuf**) to define service interfaces and data structures, ensuring type safety and performance.

### **How it Works in the Monorepo**

1. **Defining Services**:
    - Shared `.proto` files in `shared/proto/` define the contract for gRPC services.
    - Example: `HelloService.proto` defines a `SayHello` method.
2. **Implementing Services**:
    - Each service implements the gRPC methods in `app/Grpc/`.
    - Example:
        
        ```php
        namespace App\Grpc;
        
        use Hello\HelloReply;
        use Hello\HelloRequest;
        use Hello\HelloServiceInterface;
        use Grpc\ServerContext;
        
        class HelloServiceImplementation implements HelloServiceInterface
        {
            public function SayHello(ServerContext $context, HelloRequest $request): HelloReply
            {
                $message = "Hello, " . $request->getName() . "!";
                $reply = new HelloReply();
                $reply->setMessage($message);
                return $reply;
            }
        }
        
        ```
        
3. **Exposing gRPC Servers**:
    - Each service runs its own gRPC server on a specified port (e.g., `50051`).
    - Example: `grpc_server.php` starts the server and binds it to the service implementation.
4. **Calling Other Services**:
    - gRPC clients can invoke methods on other services using generated client classes.
    - Example:
        
        ```php
        use Hello\HelloServiceClient;
        use Hello\HelloRequest;
        use Grpc\ChannelCredentials;
        
        $client = new HelloServiceClient('TestService:50051', [
            'credentials' => ChannelCredentials::createInsecure(),
        ]);
        
        $request = new HelloRequest();
        $request->setName('Test User');
        list($response, $status) = $client->SayHello($request)->wait();
        
        echo $response->getMessage(); // Outputs: "Hello, Test User!"
        
        ```
        

---

## **Protocol Buffers (`.proto` Files)**

### **What are `.proto` Files?**

Protocol Buffers (protobuf) are used to define gRPC services and the types they use. These files are shared across all services to ensure consistency.

### **Structure of a `.proto` File**

Example: `shared/proto/HelloService.proto`

```
syntax = "proto3";

package hello;

service HelloService {
  rpc SayHello (HelloRequest) returns (HelloReply);
}

message HelloRequest {
  string name = 1;
}

message HelloReply {
  string message = 1;
}

```

- **Service**: Defines the available RPC methods (e.g., `SayHello`).
- **Messages**: Define the structure of request and response data.

### **How `.proto` Files Work in the Monorepo**

1. **Centralized Storage**:
    - All `.proto` files are stored in `shared/proto/`.
2. **Shared Across Services**:
    - Each service accesses the shared `.proto` files via a symlink in `proto/`.
3. **Generating PHP Classes**:
    - Run the following command to regenerate PHP classes after updating `.proto` files:
        
        ```bash
        php artisan proto:generate {ServiceName}
        
        ```
        
    - This ensures that all services are using the latest definitions.

---

## **Using gRPC Alongside Laravel APIs**

While gRPC is used for inter-service communication, each service can also expose traditional REST APIs using Laravel's routing system. This allows you to:

- Use gRPC for internal communication between microservices.
- Use REST APIs to interact with external clients.

### **Example Use Case**

1. **Exposing a REST API**:
    - Define routes and controllers as usual in Laravel.
    - Example: `routes/api.php`
        
        ```php
        use Illuminate\Support\Facades\Route;
        
        Route::get('/greet', function () {
            return ['message' => 'Hello from REST API!'];
        });
        
        ```
        
2. **gRPC for Internal Communication**:
    - Use gRPC to communicate between `ServiceA` and `ServiceB`.

This hybrid approach allows flexibility, catering to different use cases while maintaining efficient communication internally.

---

## **Deploying Services**

For now, deployment is handled using **Docker**, with Kubernetes support planned for future versions.

### **Deploying a Service**

To deploy a service, run:

```bash
php artisan deploy:service {ServiceName}

```

**What Happens:**

1. The Docker image is built from the service's `Dockerfile`.
2. The image is pushed to the configured Docker repository.
3. A Docker container is started, running the gRPC server.

**Notes:**

- Ensure Docker is installed and running.
- Update the `your-docker-repo` placeholder in `Dockerfile.tpl` with your actual Docker repository URL.

---

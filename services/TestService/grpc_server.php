<?php

require __DIR__ . '/vendor/autoload.php';

use Grpc\RpcServer;
use App\Grpc\TestServiceServiceImplementation;

// Initialize Laravel Application
$app = require __DIR__ . '/bootstrap/app.php';

// Create gRPC Server
$server = new RpcServer();

// Register gRPC Services
$server->handle(new TestServiceServiceImplementation());

// Listen on port 50051
$server->addHttp2Port('0.0.0.0:50051');

// Start the server
echo "TestService gRPC server running on 0.0.0.0:50051\n";
$server->run();

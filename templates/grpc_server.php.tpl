<?php

require __DIR__ . '/vendor/autoload.php';

use Grpc\Server;
use App\Grpc\{{SERVICE_NAME}}ServiceImplementation;
use Grpc\ServerCredentials;

// Initialize Laravel Application
$app = require __DIR__ . '/bootstrap/app.php';

// Create gRPC Server
$server = new Server();

// Register gRPC Services
$server->addService(new {{SERVICE_NAME}}ServiceImplementation());

// Load insecure credentials for dev (use secure in prod)
$creds = ServerCredentials::createInsecure();

// Listen on port 50051
$server->addListeningPort('0.0.0.0:50051', $creds);

// Start the server
echo "{{SERVICE_NAME}} gRPC server running on 0.0.0.0:50051\n";
$server->start();

// Keep the server running
while (true) {
    $server->handle();
}

<?php

require __DIR__ . '/vendor/autoload.php';

use Grpc\Server;
use App\Grpc\{{SERVICE_NAME}}ServiceImplementation;

// Initialize Laravel Application
\$app = require __DIR__ . '/bootstrap/app.php';

// Create gRPC Server
\$server = new Server();

// Register gRPC Services
\$server->addService(new {{SERVICE_NAME}}ServiceImplementation());

// Listen on port 50051 with SSL (if needed)
// For insecure connections, use Grpc\CHANNEL_INSECURE
\$server->addListeningPort('0.0.0.0:50051', Grpc\CHANNEL_INSECURE);

// Start the server
echo "gRPC server running on 0.0.0.0:50051\n";
\$server->start();

// Keep the server running
while (true) {
    \$server->handle();
}

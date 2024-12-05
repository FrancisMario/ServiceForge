#!/bin/bash

# Check for service name
if [ -z "$1" ]; then
  echo "Usage: $0 <service-name>"
  exit 1
fi

SERVICE_NAME=$1
ROOT_DIR="./"
SERVICE_DIR="$ROOT_DIR/services/$SERVICE_NAME"
PROTO_DIR="$ROOT_DIR/shared/ipc/proto"
LARAVEL_VERSION="10.x"

# Step 1: Create service directory
echo "Creating service directory for $SERVICE_NAME..."
mkdir -p $SERVICE_DIR

# Step 2: Bootstrap a new Laravel application
echo "Setting up Laravel application for $SERVICE_NAME..."
composer create-project --prefer-dist laravel/laravel:$LARAVEL_VERSION $SERVICE_DIR

# Step 3: Install gRPC package and dependencies
echo "Installing gRPC dependencies..."
cd $SERVICE_DIR
composer require grpc/grpc php-protobuf

# Step 4: Generate the gRPC proto file
PROTO_FILE="$PROTO_DIR/$SERVICE_NAME.proto"
echo "Generating proto file for $SERVICE_NAME..."
mkdir -p $PROTO_DIR
cat <<EOL > $PROTO_FILE
syntax = "proto3";

package $SERVICE_NAME;

service ${SERVICE_NAME^}Service {
  rpc ExampleRpc (ExampleRequest) returns (ExampleResponse);
}

message ExampleRequest {
  string request_id = 1;
}

message ExampleResponse {
  string response_message = 1;
}
EOL

# Step 5: Set up gRPC server in Laravel
echo "Setting up gRPC server..."
mkdir -p $SERVICE_DIR/app/Grpc
cat <<EOL > $SERVICE_DIR/app/Grpc/ExampleService.php
<?php

namespace App\Grpc;

use $SERVICE_NAME\ExampleRequest;
use $SERVICE_NAME\ExampleResponse;

class ExampleService
{
    public function ExampleRpc(ExampleRequest \$request): ExampleResponse
    {
        \$response = new ExampleResponse();
        \$response->setResponseMessage("Response to: " . \$request->getRequestId());
        return \$response;
    }
}
EOL

# Step 6: Register gRPC routes
echo "Registering gRPC routes..."
cat <<EOL > $SERVICE_DIR/routes/grpc.php
<?php

use App\Grpc\ExampleService;
use Spiral\RoadRunner\GRPC\Server;

\$server = new Server();
\$server->registerService(new ExampleService());

return \$server;
EOL

# Step 7: Update the Laravel `.env` file
echo "Updating .env file..."
cat <<EOL >> $SERVICE_DIR/.env

# gRPC Configuration
GRPC_PORT=9000
EOL

# Step 8: Set up Dockerfile for the service
echo "Setting up Dockerfile..."
cat <<EOL > $SERVICE_DIR/Dockerfile
FROM php:8.2-cli

# Install dependencies
RUN apt-get update && apt-get install -y \
    unzip \
    libprotobuf-dev \
    protobuf-compiler

# Install composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install gRPC PHP extension
RUN pecl install grpc && docker-php-ext-enable grpc

# Set up working directory
WORKDIR /var/www
COPY . .

# Install PHP dependencies
RUN composer install --no-dev

# Expose gRPC port
EXPOSE 9000

# Start the gRPC server
CMD ["php", "artisan", "grpc:serve"]
EOL

# Step 9: Output success message
echo "Service $SERVICE_NAME successfully generated!"
echo "Proto file location: $PROTO_FILE"
echo "To start the service, navigate to $SERVICE_DIR and use Docker or your local PHP environment."

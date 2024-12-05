<?php

namespace App\Grpc;

use Grpc\ServerContext;
use Hello\HelloRequest;
use Hello\HelloReply;
use Hello\HelloServiceInterface;

class HelloServiceImplementation implements HelloServiceInterface
{
    public function SayHello(ServerContext \$context, HelloRequest \$request): HelloReply
    {
        \$name = \$request->getName();
        \$message = "Hello, {\$name}! Welcome to gRPC with Laravel.";

        \$reply = new HelloReply();
        \$reply->setMessage(\$message);

        return \$reply;
    }
}

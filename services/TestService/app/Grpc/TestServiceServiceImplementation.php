<?php

namespace App\Grpc;

use test_service\TestServiceRequest;
use test_service\TestServiceReply;
use test_service\TestServiceServiceInterface;
use Grpc\ServerContext;

class TestServiceServiceImplementation implements TestServiceServiceInterface
{
    public function SayHello(ServerContext $context, TestServiceRequest $request): TestServiceReply
    {
        $message = "Hello, " . $request->getName() . " from TestService!";
        $reply = new TestServiceReply();
        $reply->setMessage($message);
        return $reply;
    }
}

// docker run -d --network service_network --name test-service -p 50051:50051 test-service:latest

// docker rm -f test-service
// docker build -t test-service:latest .
// docker run -d --network service_network --name test-service -p 50051:50051 test-service:latest

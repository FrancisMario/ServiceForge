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

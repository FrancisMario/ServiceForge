<?php

namespace App\Grpc;

use {{PACKAGE_NAME}}\{{SERVICE_NAME}}Request;
use {{PACKAGE_NAME}}\{{SERVICE_NAME}}Reply;
use {{PACKAGE_NAME}}\{{SERVICE_NAME}}ServiceInterface;
use Grpc\ServerContext;

class {{SERVICE_NAME}}ServiceImplementation implements {{SERVICE_NAME}}ServiceInterface
{
    public function SayHello(ServerContext $context, {{SERVICE_NAME}}Request $request): {{SERVICE_NAME}}Reply
    {
        $message = "Hello, " . $request->getName() . " from {{SERVICE_NAME}}!";
        $reply = new {{SERVICE_NAME}}Reply();
        $reply->setMessage($message);
        return $reply;
    }
}

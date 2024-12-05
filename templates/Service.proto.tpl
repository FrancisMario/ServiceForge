syntax = "proto3";

package {{service_snake}};

// The {{SERVICE_NAME}} service definition.
service {{SERVICE_NAME}} {
  // Example RPC method: Sends a greeting
  rpc SayHello ({{SERVICE_NAME}}Request) returns ({{SERVICE_NAME}}Reply) {}
}

// The request message containing the user's name.
message {{SERVICE_NAME}}Request {
  string name = 1;
}

// The response message containing the greetings.
message {{SERVICE_NAME}}Reply {
  string message = 1;
}

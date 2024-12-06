// GENERATED CODE -- DO NOT EDIT!

'use strict';
var grpc = require('grpc');
var TestServiceService_pb = require('./TestServiceService_pb.js');

function serialize_test_service_TestServiceReply(arg) {
  if (!(arg instanceof TestServiceService_pb.TestServiceReply)) {
    throw new Error('Expected argument of type test_service.TestServiceReply');
  }
  return Buffer.from(arg.serializeBinary());
}

function deserialize_test_service_TestServiceReply(buffer_arg) {
  return TestServiceService_pb.TestServiceReply.deserializeBinary(new Uint8Array(buffer_arg));
}

function serialize_test_service_TestServiceRequest(arg) {
  if (!(arg instanceof TestServiceService_pb.TestServiceRequest)) {
    throw new Error('Expected argument of type test_service.TestServiceRequest');
  }
  return Buffer.from(arg.serializeBinary());
}

function deserialize_test_service_TestServiceRequest(buffer_arg) {
  return TestServiceService_pb.TestServiceRequest.deserializeBinary(new Uint8Array(buffer_arg));
}


// The TestService service definition.
var TestServiceService = exports.TestServiceService = {
  // Example RPC method: Sends a greeting
sayHello: {
    path: '/test_service.TestService/SayHello',
    requestStream: false,
    responseStream: false,
    requestType: TestServiceService_pb.TestServiceRequest,
    responseType: TestServiceService_pb.TestServiceReply,
    requestSerialize: serialize_test_service_TestServiceRequest,
    requestDeserialize: deserialize_test_service_TestServiceRequest,
    responseSerialize: serialize_test_service_TestServiceReply,
    responseDeserialize: deserialize_test_service_TestServiceReply,
  },
};

exports.TestServiceClient = grpc.makeGenericClientConstructor(TestServiceService);

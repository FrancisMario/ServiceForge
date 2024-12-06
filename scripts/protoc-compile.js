#!/usr/bin/env node

const { spawn } = require('child_process');
const path = require('path');

// Get arguments
const args = process.argv.slice(2);
const protoFile = args.pop(); // Last argument is the proto file
const options = args.reduce((acc, arg) => {
  const [key, value] = arg.split('=');
  acc[key.replace('--', '')] = value;
  return acc;
}, {});

// Resolve paths
const grpcPluginPath = require.resolve('grpc-tools/bin/grpc_node_plugin');
const protoPath = options.proto_path || '.';
const phpOut = options.php_out || './php';
const grpcOut = options.grpc_out || './grpc';

// Run grpc-tools protoc
const protoc = spawn('npx', [
  'grpc_tools_node_protoc',
  `--proto_path=${protoPath}`,
  `--plugin=protoc-gen-grpc=${grpcPluginPath}`,
  `--php_out=${phpOut}`,
  `--grpc_out=${grpcOut}`,
  protoFile,
]);

protoc.stdout.on('data', (data) => console.log(data.toString()));
protoc.stderr.on('data', (data) => console.error(data.toString()));

protoc.on('close', (code) => {
  if (code !== 0) {
    console.error(`protoc exited with code ${code}`);
    process.exit(code);
  } else {
    console.log(`Successfully compiled: ${protoFile}`);
  }
});

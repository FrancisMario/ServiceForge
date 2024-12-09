#!/usr/bin/env node

const { spawn } = require('child_process');
const path = require('path');

// Parse arguments
const args = process.argv.slice(2);
if (args.length === 0) {
  console.error('Error: No proto file specified.');
  process.exit(1);
}

const protoFile = args.pop(); // Last argument is the proto file
const options = args.reduce((acc, arg) => {
  const [key, value] = arg.split('=');
  acc[key.replace('--', '')] = value;
  return acc;
}, {});

// Resolve paths
try {
  const grpcPluginPath = require.resolve('grpc-tools/bin/grpc_node_plugin');
  const protoPath = options.proto_path || '.';
  const phpOut = options.php_out || './php';
  const grpcOut = options.grpc_out || './grpc';

  console.log('Using the following paths:');
  console.log(`  proto_path: ${protoPath}`);
  console.log(`  php_out: ${phpOut}`);
  console.log(`  grpc_out: ${grpcOut}`);
  console.log(`  grpc_node_plugin: ${grpcPluginPath}`);

  // Build the command
  const commandArgs = [
    'grpc_tools_node_protoc',
    `--proto_path=${protoPath}`,
    `--plugin=protoc-gen-grpc=${grpcPluginPath}`,
    `--php_out=${phpOut}`,
    `--grpc_out=${grpcOut}`,
    protoFile,
  ];

  console.log('Running command:');
  console.log(`  npx ${commandArgs.join(' ')}`);

  // Run protoc
  const protoc = spawn('npx', commandArgs);

  protoc.stdout.on('data', (data) => console.log(`stdout: ${data.toString()}`));
  protoc.stderr.on('data', (data) => console.error(`stderr: ${data.toString()}`));

  protoc.on('close', (code) => {
    if (code !== 0) {
      console.error(`Error: protoc exited with code ${code}`);
      process.exit(code);
    } else {
      console.log(`Successfully compiled: ${protoFile}`);
    }
  });
} catch (err) {
  console.error('Error during setup:');
  console.error(err.message);
  process.exit(1);
}

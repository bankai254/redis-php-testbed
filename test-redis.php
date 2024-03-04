<?php
require_once 'globals.php';

use Predis\Client;

// Connect to Redis
$redis = new Client([
    'scheme' => 'tcp',
    'host' => $_ENV['REDIS_HOST'],
    'port' => $_ENV['REDIS_PORT'],
    'password' => $_ENV['REDIS_PASSWORD'],
    'timeout' => 10,
]);

// Test the connection
try {
    $redis->connect();
    echo "Connected to Redis successfully!";
} catch (Exception $e) {
    echo "Failed to connect to Redis: " . $e->getMessage();
}

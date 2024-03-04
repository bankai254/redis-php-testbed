<?php
require_once __DIR__ . '/globals.php';

use Predis\Client;

// Connect to Redis server
$redis = new Client([
    'scheme' => 'tcp',
    'host' => $_ENV['REDIS_HOST'],
    'port' => $_ENV['REDIS_PORT'],
    'password' => $_ENV['REDIS_PASSWORD'],
    'timeout' => 10,
]);

$messageKeys = $redis->keys('msg_*');
$messages = [];

foreach ($messageKeys as $key) {
    $messages[] = $redis->hgetall($key);
}

// Return messages as JSON
header('Content-Type: application/json');
echo json_encode($messages, true, JSON_UNESCAPED_SLASHES);

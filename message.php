<?php
require __DIR__ . '/globals.php'; // Ensure this path matches your project structure

header('Content-Type: application/json'); // Set content type to application/json

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate input data
    $to = filter_input(INPUT_POST, 'to', FILTER_SANITIZE_EMAIL);
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400); // Set HTTP response code to 400 (Bad Request)
        echo json_encode(['error' => 'Invalid email address.']);
        exit;
    }

    $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_STRING);
    $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);

    try {
        // Initialize Predis client
        $redis = new Predis\Client([
            'scheme' => 'tcp',
            'host' => $_ENV['REDIS_HOST'],
            'port' => $_ENV['REDIS_PORT'],
            'password' => $_ENV['REDIS_PASSWORD'],
            'timeout' => 10,
        ]);

        $messageId = uniqid('msg_');
        $redis->hmset($messageId, [
            'to' => $to,
            'subject' => $subject,
            'message' => $message,
            'status' => 'queued', // Initial status
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Use LPUSH (or RPUSH) to add the message to the queue
        $redis->lpush('messageQueue', $messageId);

        http_response_code(200); // Set HTTP response code to 200 (OK)
        echo json_encode(['success' => 'Message successfully queued!', 'id' => $messageId]);
    } catch (Exception $e) {
        http_response_code(500); // Set HTTP response code to 500 (Internal Server Error)
        echo json_encode(['error' => 'Error connecting to Redis: ' . $e->getMessage()]);
    }
} else {
    // Not a POST request
    http_response_code(405); // Set HTTP response code to 405 (Method Not Allowed)
    echo json_encode(['error' => 'Method not allowed. Please submit the form.']);
}

<?php
require __DIR__ . '/globals.php'; // Ensure this path matches your project structure

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

$redis = new Predis\Client([
    'scheme' => 'tcp',
    'host' => $_ENV['REDIS_HOST'],
    'port' => $_ENV['REDIS_PORT'],
    'password' => $_ENV['REDIS_PASSWORD'],
    'timeout' => 10,
]);

echo "Worker started. Listening for messages...\n";

while (true) {

    $messageId = $redis->brpop(['messageQueue'], 0)[1];
    if ($messageId) {
        $messageData = $redis->hgetall($messageId);

        // Initialize PHPMailer
        $mail = new PHPMailer(true);

        try {

            // Server settings
            $mail->isSMTP();
            $mail->Host = $_ENV['EMAIL_HOST']; // Set the SMTP server to send through
            $mail->SMTPAuth = true;
            $mail->Username = $_ENV['EMAIL_USERNAME']; // SMTP username
            $mail->Password = $_ENV['EMAIL_PASSWORD']; // SMTP password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
            $mail->Port = $_ENV['EMAIL_PORT']; // TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS` above

            // Recipients
            $mail->setFrom($_ENV['EMAIL_FROM'], 'Demo Test');
            $mail->addAddress($messageData['to']); // Add a recipient

            // Content
            $mail->isHTML(true); // Set email format to HTML
            $mail->Subject = $messageData['subject'];
            $mail->Body = $messageData['message'];

            $mail->send();
            echo "Message sent: $messageId\n";

            // Update the message status to 'sent'
            $redis->hmset($messageId, ['status' => 'sent', 'updated_at' => date('Y-m-d H:i:s')]);

        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}\n";
            // Optionally, update the message status to 'failed'

            //$redis->hmset($messageId, $messageData);
            $redis->hmset($messageId, ['status' => 'failed', 'error' => $mail->ErrorInfo, 'updated_at' => date('Y-m-d H:i:s')]);

        }
    }
}

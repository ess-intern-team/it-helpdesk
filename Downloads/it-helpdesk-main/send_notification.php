<?php
// Debug autoloader
$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    die('Autoload file not found at: ' . $autoloadPath);
}
if (!is_readable($autoloadPath)) {
    die('Autoload file not readable at: ' . $autoloadPath);
}
require_once $autoloadPath;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendNotification($to, $subject, $message)
{
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Replace with your SMTP host
        $mail->SMTPAuth = true;
        $mail->Username = 'hussenseid670@gmail.com'; // Replace with your email
        $mail->Password = 'Seid465396@'; // Use app-specific password if 2FA is enabled
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('hussenseid670@gmail.com', 'IT Help Desk');
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;
        $mail->AltBody = strip_tags($message);

        $mail->send();
        return true;
    } catch (Exception $e) {
        $logFile = __DIR__ . '/logs/errors.log';
        $logDir = dirname($logFile);
        if (!file_exists($logDir)) {
            mkdir($logDir, 0777, true);
        }
        if (is_writable($logDir)) {
            error_log("Notification failed for $subject: {$mail->ErrorInfo}", 3, $logFile);
        } else {
            error_log("Notification failed for $subject: {$mail->ErrorInfo} (Log file not writable)");
        }
        return false;
    }
}

if (isset($_POST['send'])) {
    $to = 'hussenseid670@gmail.com';
    $subject = 'Ticket #1 Update';
    $message = 'Dear Seid Hussein, your ticket has been updated.';
    sendNotification($to, $subject, $message);
}

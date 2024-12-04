<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../../../vendor/autoload.php'; // Adjust the path if necessary

require __DIR__ . '/../../../dbconnections/config.php';

function sendEmail($recipientEmail, $body)
{
    $subject = "Dean";

    $mail = new PHPMailer(true);
    try {

        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'quicerbencezar@gmail.com';
        $mail->Password = 'nqac xomw sugs elig'; // NEVER hardcode credentials in real applications
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        // $mail->CharSet = 'UTF-8';

        // Recipients
        $mail->setFrom('quicerbencezar@gmail.com', 'Mailer');
        $mail->addAddress($recipientEmail);
        // $mail->WordWrap = 50;
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;

        $mail->send();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to send email. Error: {$mail->ErrorInfo}']);
    }
}

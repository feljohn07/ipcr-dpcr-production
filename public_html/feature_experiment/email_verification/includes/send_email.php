<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../../../vendor/autoload.php'; // Adjust the path if necessary

require __DIR__ . '/../../../dbconnections/config.php';

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve the submitted email value
    if (isset($_POST['email']) && isset($_POST['email'])) {

        // Get Id number and Email of the user
        $email = $_POST['email'];
        // Retrieve the value of the hidden field
        $idnumber = $_POST['idnumber'];

        // Send Email
        sendEmail($email, $idnumber);
    } else {
        echo "Email not provided!";
    }
} else {
    echo "Invalid request method!";
}

function sendEmail($recipientEmail, $userId)
{
    $subject = "Email Verification!";

    $mail = new PHPMailer(true);

    $email = urlencode($recipientEmail); // URL-encode the email to ensure it's safe
    $token = bin2hex(random_bytes(16)); // Generate a random token for security

    $verification_link = "https://aliceblue-mosquito-125906.hostingersite.com/public_html/feature_experiment/email_verification/public/verification_link.php?user_id=$userId&email=$email&token=$token";
    // $verification_link = "http://localhost/asscatipcr.com/public_html/feature_experiment/email_verification/public/verification_link.php?user_id=$userId&email=$email&token=$token";

    $body = "Thank you for signing up! Please confirm your email address by clicking this Link : <a href=\"$verification_link\"> Verify Email</a>";

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
        echo "<div class='message'>Email sent successfully to $recipientEmail.</div>";
    } catch (Exception $e) {
        echo "<div class='error-message'>Failed to send email. Error: {$mail->ErrorInfo}</div>";
    }
}

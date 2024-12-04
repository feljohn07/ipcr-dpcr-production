<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php'; // Adjust the path if necessary

function sendEmail($recipientEmail) {
    $subject = "Hello!";
    $body = "hello";

    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();                                            // Send using SMTP
        $mail->Host       = 'smtp.gmail.com';                        // Set the SMTP server to send through
        $mail->SMTPAuth   = true;                                    // Enable SMTP authentication
        $mail->Username   = 'quicerbencezar@gmail.com';              // SMTP username
        $mail->Password   = 'nqac xomw sugs elig';                   // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         // Enable TLS encryption
        $mail->Port       = 587;                                     // TCP port to connect to

        // Recipients
        $mail->setFrom('quicerbencezar@gmail.com', 'Mailer');
        $mail->addAddress($recipientEmail);                          // Add recipient

        // Content
        $mail->isHTML(true);                                         // Set email format to HTML
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        echo "Email sent successfully to $recipientEmail.";
    } catch (Exception $e) {
        echo "Failed to send email. Error: {$mail->ErrorInfo}";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Email</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background-color: #f9f9f9;
        }
        .email-container {
            width: 100%;
            max-width: 400px;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 8px;
            background-color: #fff;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        h2 {
            margin-bottom: 20px;
            font-size: 22px;
            color: #333;
        }
        .input-group {
            margin-bottom: 15px;
            text-align: left;
        }
        .input-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        .input-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
        }
        .send-button {
            width: 100%;
            padding: 10px;
            font-size: 16px;
            color: #fff;
            background-color: #4CAF50;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .send-button:hover {
            background-color: #45a049;
        }
        .message {
            margin-top: 20px;
            font-size: 14px;
            color: #155724;
            background-color: #d4edda;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #c3e6cb;
        }
        .error-message {
            margin-top: 20px;
            font-size: 14px;
            color: #721c24;
            background-color: #f8d7da;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <h2>Send Email</h2>
        <form method="POST" action="">
            <div class="input-group">
                <label for="email">Recipient Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <button type="submit" class="send-button">Send Email</button>
        </form>
        <?php
        if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['email'])) {
            // require 'path/to/your/sendEmailFunction.php'; // Adjust the path
            $recipientEmail = $_POST['email'];
            sendEmail($recipientEmail);
        }
        ?>
    </div>
</body>
</html>

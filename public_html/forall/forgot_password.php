<?php
session_start();
include '../dbconnections/config.php'; // Include your database connection

// Add the use statements for PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include the Composer autoload file to load PHPMailer and other dependencies
require '../vendor/autoload.php'; // Adjust the path if necessary

$message = '';
$error = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = isset($_POST['email']) ? $_POST['email'] : '';

    // Check if the email exists in the database
    $sql = "SELECT idnumber FROM usersinfo WHERE gmail=?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // Generate a 6-digit verification code
            $verificationCode = rand(100000, 999999);
            
            // Store the code in the database
            $sqlInsertCode = "UPDATE usersinfo SET verification_code=? WHERE gmail=?";
            if ($stmtInsert = $conn->prepare($sqlInsertCode)) {
                $stmtInsert->bind_param("is", $verificationCode, $email);
                $stmtInsert->execute();
                $stmtInsert->close();
            }

            // Send the verification code to the user's email using PHPMailer
            $subject = "Password Reset Verification Code";
            $body = "Your verification code to reset your password is: " . $verificationCode;

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
                $mail->addAddress($email);                                    // Add a recipient

                // Content
                $mail->isHTML(true);                                          // Set email format to HTML
                $mail->Subject = $subject;
                $mail->Body    = $body;

                $mail->send();
                $message = "A verification code has been sent to your email.";

                // Redirect to the verification page
                header("Location: verify_code.php?email=" . urlencode($email));
                exit(); // Make sure to exit to stop further script execution
            } catch (Exception $e) {
                $error = "Failed to send email. Mailer Error: {$mail->ErrorInfo}";
            }
        } else {
            $error = "Email not found.";
        }
        $stmt->close();
    } else {
        $error = "Error preparing statement: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background-color: #f2f2f2;
            font-family: Arial, sans-serif;
        }
        .forgot-password-container {
            width: 100%;
            max-width: 400px;
            padding: 25px;
            margin: 0 15px;
            border: 1px solid #ccc;
            border-radius: 8px;
            background-color: #fff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        h2 {
            margin-bottom: 20px;
            font-size: 24px;
            color: #333;
        }
        .input-group {
            margin-bottom: 20px;
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
        .submit-button {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            color: #fff;
            background-color: #4CAF50;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .submit-button:hover {
            background-color: #45a049;
        }
        .message, .error-message {
            font-size: 14px;
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 5px;
        }
        .message {
            color: #155724;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
        }
        .error-message {
            color: #721c24;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
        }
        .back-to-login {
            margin-top: 15px;
            text-align: center;
        }
        .back-to-login a {
            color: #007bff;
            text-decoration: none;
            font-size: 14px;
        }
        .back-to-login a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="forgot-password-container">
        <h2>Forgot Password</h2>
        <?php if ($message): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="input-group">
                <label for="email">Enter your email address</label>
                <input type="email" name="email" id="email" required>
            </div>
            <input type="submit" value="Send Verification Code" class="submit-button">
        </form>
        <div class="back-to-login">
            <a href="../index.php">Back to Login</a>
        </div>
    </div>
</body>
</html>

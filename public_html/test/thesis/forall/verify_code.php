<?php
session_start();
include '../dbconnections/config.php'; // Include your database connection

$email = isset($_GET['email']) ? $_GET['email'] : ''; // Get the email from the URL
$verification_code = isset($_POST['verification_code']) ? $_POST['verification_code'] : '';
$new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
$confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

// Initialize message and error variables
$message = "";
$error = "";

// If the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty($verification_code) || empty($new_password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $error = "The new password and confirm password do not match.";
    } else {
        // Query the database to find the stored verification code for the given email
        $sql = "SELECT verification_code FROM usersinfo WHERE gmail = ?";
        if ($stmt = $conn->prepare($sql)) {
            // Bind the email parameter to the prepared statement
            $stmt->bind_param("s", $email);
            
            // Execute the query
            $stmt->execute();
            
            // Store the result
            $stmt->store_result();
            
            // Check if the email exists
            if ($stmt->num_rows > 0) {
                // Bind the result to the variable
                $stmt->bind_result($stored_verification_code);
                $stmt->fetch();
                
                // Check if the provided verification code matches the stored code
                if ($verification_code == $stored_verification_code) {
                    // The verification code is correct, so update the password
                    // Update the password directly (without hashing)
                    $update_sql = "UPDATE usersinfo SET password = ? WHERE gmail = ?";
                    if ($update_stmt = $conn->prepare($update_sql)) {
                        $update_stmt->bind_param("ss", $new_password, $email);
                        $update_stmt->execute();
                        
                        if ($update_stmt->affected_rows > 0) {
                            $message = "Password updated successfully!";
                        } else {
                            $error = "Error updating password.";
                        }
                        $update_stmt->close();
                    }
                } else {
                    // The verification code is incorrect
                    $error = "The verification code is incorrect.";
                }
            } else {
                // Email not found
                $error = "No user found with that email.";
            }
            
            $stmt->close();
        } else {
            $error = "Error preparing statement: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Code</title>
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
        .verify-code-container {
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
    <div class="verify-code-container">
        <h2>Verify Code</h2>
        <?php if ($message): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="input-group">
                <label for="verification_code">Enter the verification code</label>
                <input type="text" name="verification_code" id="verification_code" value="<?php echo htmlspecialchars($verification_code); ?>" required>
            </div>
            <div class="input-group">
                <label for="new_password">Enter your new password</label>
                <input type="password" name="new_password" id="new_password" required>
            </div>
            <div class="input-group">
                <label for="confirm_password">Confirm your new password</label>
                <input type="password" name="confirm_password" id="confirm_password" required>
            </div>
            <input type="submit" value="Reset Password" class="submit-button">
        </form>
        <div class="back-to-login">
            <a href="../index.php">Back to Login</a>
        </div>
    </div>
</body>
</html>

<?php
session_start();

// Ensure the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Database connection
include '../dbconnections/config.php';

$message = '';
$error = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $currentPassword = isset($_POST['current_password']) ? $_POST['current_password'] : '';
    $newPassword = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    $confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    // Validate new password and confirm password
    if ($newPassword !== $confirmPassword) {
        $error = "New password and confirm password do not match.";
    } else {
        // Fetch the current password from the database
        $idnumber = $_SESSION['idnumber'];
        $sqlFetchPassword = "SELECT password FROM usersinfo WHERE idnumber=?";
        if ($stmt = $conn->prepare($sqlFetchPassword)) {
            $stmt->bind_param("s", $idnumber);
            $stmt->execute();
            $stmt->bind_result($storedPassword);
            $stmt->fetch();
            $stmt->close();
        }

        // Verify the current password
        if ($currentPassword === $storedPassword) {
            // Update the password in the database without hashing
            $sqlUpdatePassword = "UPDATE usersinfo SET password=? WHERE idnumber=?";
            if ($stmt = $conn->prepare($sqlUpdatePassword)) {
                $stmt->bind_param("ss", $newPassword, $idnumber);
                if ($stmt->execute()) {
                    $message = "Password changed successfully.";
                } else {
                    $error = "Error updating password: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $error = "Error preparing statement: " . $conn->error;
            }
        } else {
            $error = "Current password is incorrect.";
        }
    }
}

// Determine the redirect URL based on the user's role
$redirectUrl = "profile.php"; // Default URL
$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';

switch (strtolower($role)) {
    case 'admin':
        $redirectUrl = "../admin/admindash.php";
        break;
    case 'ipcr':
        $redirectUrl = "../ipcr/ipcrdash.php";
        break;
    case 'office head':
        $redirectUrl = "../dpcr/dpcrdash.php";
        break;
    case 'college president':
        $redirectUrl = "../president/pressdash.php";
        break;
    case 'vpaaqa':
        $redirectUrl = "../vp/vpdash.php";
        break;
    case 'immediate supervisor':
        $redirectUrl = "../supervisor/supervisor_dash.php";
        break;
    default:
        $redirectUrl = "../index.php"; // Redirect to a generic dashboard
        break;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <link rel="stylesheet" href="styles.css"> <!-- Include your CSS file -->
</head>
<body>
    <div class="change-password-container">
        <h2>Change Password</h2>
        <?php if (isset($message) && $message): ?>
            <div class="success-message"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if (isset($error) && $error): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="input-group">
                <label for="current_password">Current Password</label>
                <input type="password" name="current_password" id="current_password" required>
            </div>
            <div class="input-group">
                <label for="new_password">New Password</label>
                <input type="password" name="new_password" id="new_password" required>
            </div>
            <div class="input-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" name="confirm_password" id="confirm_password" required>
            </div>
            <input type="submit" value="Change Password" class="submit-button">
        </form>
        <a href="<?php echo $redirectUrl; ?>" class="back-link">Back to Profile</a>
    </div>
</body>
</html>

<style>
    /* Basic styles for the change password form */
    body {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        margin: 0;
        background-color: #f2f2f2;
        font-family: Arial, sans-serif;
    }

    .change-password-container {
        width: 100%;
        max-width: 400px;
        padding: 25px;
        margin: 0 15px;
        border: 1px solid #ccc;
        border-radius: 8px;
        background-color: # fff;
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

    .back-link {
        display: block;
        margin-top: 20px;
        color: #4CAF50;
        text-decoration: none;
        font-weight: bold;
    }

    .back-link:hover {
        color: #45a049;
    }

    .success-message, .error-message {
        font-size: 14px;
        margin-bottom: 15px;
        padding: 10px;
        border-radius: 5px;
    }

    .success-message {
        color: #155724;
        background-color: #d4edda;
        border: 1px solid #c3e6cb;
    }

    .error-message {
        color: #721c24;
        background-color: #f8d7da;
        border: 1px solid #f5c6cb;
    }
</style>
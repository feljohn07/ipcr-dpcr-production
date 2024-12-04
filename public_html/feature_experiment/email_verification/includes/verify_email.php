<?php
// Database connection
include __DIR__ . '/../../../dbconnections/config.php';


function verifyEmail($recipientEmail, $userId)
{
    // Update email in the database where idnumber matches
    global $conn; // Make sure $conn is available inside the function
    $sqlUpdateEmail = "UPDATE usersinfo SET gmail=? WHERE idnumber=?";

    if ($stmt = $conn->prepare($sqlUpdateEmail)) {
        $stmt->bind_param("ss", $recipientEmail, $userId);
        if ($stmt->execute()) {
            echo "Email updated successfully";

            // Optionally, update session variables
            $_SESSION['gmail'] = $recipientEmail;
        } else {
            echo "Error updating email: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Error preparing statement: " . $conn->error;
    }

    // Redirect to login page with a message in the query string
    $message = urlencode("Email verification successful!");

    session_unset(); // Unset all session variables
    session_destroy(); // Destroy the session

    // Logout user
    header("Location: ../../../forall/login.php?message=$message");

    exit; // Ensure no further code is executed.
}

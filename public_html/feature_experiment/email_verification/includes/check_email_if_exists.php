<?php
// Database connection
include __DIR__ . '/../../../dbconnections/config.php';

// Check first if email already exists
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['email'])) {

    // Retrieve the email and idnumber from the POST data
    $email = $_POST['email'];
    $idnumber = $_POST['idnumber']; // Assuming the idnumber is passed via the form

    // ================== Verify Email ======================================

    // Update email in the database where idnumber matches
    global $conn; // Make sure $conn is available inside the function

    // Check if the email already exists in the database (excluding the current user's email)
    $countQuery = "SELECT COUNT(*) FROM usersinfo WHERE gmail=?";

    if ($stmt = $conn->prepare($countQuery)) {
        $stmt->bind_param("s", $email); // Bind the email and idnumber (as an integer)
        $stmt->execute();
        $stmt->bind_result($emailCount);
        $stmt->fetch();
        $stmt->close();

        // If email already exists for another account, show the message
        if ($emailCount >= 1) {
            echo json_encode(['status' => 'error', 'message' => 'Email Used.']);
            return; // Stop further execution if the email exists for another account
        } else {
            echo json_encode(['status' => 'success', 'message' => 'Email not used.']);
        }

    } else {
        echo "Error preparing statement: " . $conn->error;
        return;
    }

    // ======================================================================
}

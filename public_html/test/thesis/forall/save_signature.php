<?php
session_start();

include '../dbconnections/config.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Retrieve idnumber and image data from POST request
    if (!isset($_SESSION['idnumber'])) {
        die("Session idnumber not set");
    }
    $idnumber = $_SESSION['idnumber'];

    // Fetch user role from session
    if (!isset($_SESSION['role'])) {
        die("Session role not set");
    }
    $role = $_SESSION['role'];

    // Fetch college from session
    if (!isset($_SESSION['college'])) {
        die("Session college not set");
    }
    $college = $_SESSION['college'];

    $dataURL = $_POST['image'];

    // Remove the "data:image/png;base64," substring from the base64 data
    $data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $dataURL));

    // Check if the user already has a signature
    $checkStmt = $conn->prepare("SELECT COUNT(*) FROM signature WHERE idnumber = ?");
    if (!$checkStmt) {
        die("Preparation failed: " . $conn->error);
    }
    $checkStmt->bind_param("s", $idnumber);
    $checkStmt->execute();
    $checkStmt->bind_result($count);
    $checkStmt->fetch();
    $checkStmt->close();

    if ($count > 0) {
        echo "You already have signature."; // If signature already exists
    } else {
        // Prepare SQL statement to insert data into the signature table
        $stmt = $conn->prepare("INSERT INTO signature (idnumber, role, college, data) VALUES (?, ?, ?, ?)");
        if (!$stmt) {
            die("Preparation failed: " . $conn->error);
        }

        // Bind parameters and execute SQL statement
        $stmt->bind_param("sssb", $idnumber, $role, $college, $null);
        $null = NULL;
        $stmt->send_long_data(3, $data);

        if ($stmt->execute()) {
            echo "Signature saved successfully";
        } else {
            echo "Error: " . $stmt->error;
        }

        // Close prepared statement
        $stmt->close();
    }

    // Close database connection
    $conn->close();
}
?>

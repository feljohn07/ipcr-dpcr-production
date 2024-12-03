<?php
session_start();

include '../dbconnections/config.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Retrieve idnumber from session
    if (!isset($_SESSION['idnumber'])) {
        die("Session idnumber not set");
    }
    $idnumber = $_SESSION['idnumber'];

    // Prepare SQL statement to delete the signature
    $stmt = $conn->prepare("DELETE FROM signature WHERE idnumber = ?");
    if (!$stmt) {
        die("Preparation failed: " . $conn->error);
    }

    // Bind parameters and execute SQL statement
    $stmt->bind_param("s", $idnumber);

    if ($stmt->execute()) {
        echo "Signature deleted successfully";
    } else {
        echo "Error: " . $stmt->error;
    }

    // Close prepared statement
    $stmt->close();
}

// Close database connection
$conn->close();
?>

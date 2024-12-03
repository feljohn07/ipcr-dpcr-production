<?php
// Database connection parameters
include '../../../dbconnections/config.php';

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve the input values
    $idnumber = isset($_POST['idnumber']) ? trim($_POST['idnumber']) : '';
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';
    $semester_id = isset($_POST['semester_id']) ? (int)$_POST['semester_id'] : 0; // Cast to integer

    // Input validation
    if (empty($idnumber) || empty($message) || $semester_id <= 0) {
        echo "Error: All fields are required.";
        exit;
    }

    // Prepare the SQL statement with INSERT ... ON DUPLICATE KEY UPDATE
    $query = "INSERT INTO performance_ipcr_message (idnumber, message, semester_id) 
              VALUES (?, ?, ?) 
              ON DUPLICATE KEY UPDATE message = ?, semester_id = ?"; // Update the message and semester_id if the record exists
    $stmt = $conn->prepare($query);

    // Check if the statement was prepared successfully
    if ($stmt === false) {
        echo "Error preparing statement: " . $conn->error;
        exit;
    }

    // Bind the parameters
    $stmt->bind_param("ssisi", $idnumber, $message, $semester_id, $message, $semester_id); // Bind parameters for both insert and update

    // Execute the statement and check for errors
    if ($stmt->execute()) {
        echo "Message submitted successfully.";
    } else {
        echo "Error: " . $stmt->error;
    }

    // Close the statement
    $stmt->close();
} else {
    echo "Error: Invalid request method.";
}

// Close the database connection
$conn->close();
?>
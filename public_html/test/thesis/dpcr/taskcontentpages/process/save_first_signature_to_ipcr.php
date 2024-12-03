<?php
session_start();
include '../../../dbconnections/config.php'; // Database connection

// Set the timezone to Philippines
date_default_timezone_set('Asia/Manila');

// Get the data from the POST request
$idnumber = isset($_POST['idnumber']) ? $_POST['idnumber'] : '';
$semester_id = isset($_POST['semester_id']) ? (int)$_POST['semester_id'] : 0;

// Check if the signature already exists
$sql = "SELECT * FROM to_ipcr_first_signature WHERE idnumber = ? AND semester_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $idnumber, $semester_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // If it exists, delete the signature
    $delete_sql = "DELETE FROM to_ipcr_first_signature WHERE idnumber = ? AND semester_id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("si", $idnumber, $semester_id);
    if ($delete_stmt->execute()) {
        echo "Signature deleted.";
    } else {
        echo "Error deleting signature: " . $delete_stmt->error;
    }
    $delete_stmt->close();
} else {
    // If it doesn't exist, insert the signature with the current date and time
    $current_time = date('Y-m-d H:i:s'); // Get the current date and time in the specified format
    $insert_sql = "INSERT INTO to_ipcr_first_signature (idnumber, semester_id, created_at) VALUES (?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("sis", $idnumber, $semester_id, $current_time);
    if ($insert_stmt->execute()) {
        echo "Signature saved.";
    } else {
        echo "Error saving signature: " . $insert_stmt->error; // Provide error feedback
    }
    $insert_stmt->close();
}

// Close the main statement and connection
$stmt->close();
$conn->close();
?>
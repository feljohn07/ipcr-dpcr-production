<?php
session_start();
include '../../dbconnections/config.php'; // Include your database connection

// Set the timezone to the Philippines
date_default_timezone_set('Asia/Manila');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $semester_id = $_POST['semester_id'];
    $idnumber = $_POST['idnumber'];

    // Check if the record already exists
    $stmt = $conn->prepare("SELECT * FROM user_semesters WHERE semester_id = ? AND idnumber = ?");
    $stmt->bind_param("ss", $semester_id, $idnumber);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Record exists, delete it
        $stmt = $conn->prepare("DELETE FROM user_semesters WHERE semester_id = ? AND idnumber = ?");
        $stmt->bind_param("ss", $semester_id, $idnumber);
        $stmt->execute();
        $response = ['action' => 'deleted'];
    } else {
        // Record does not exist, insert it
        $stmt = $conn->prepare("INSERT INTO user_semesters (semester_id, idnumber, created_at) VALUES (?, ?, ?)");
        $createdAt = date('Y-m-d H:i:s'); // Get the current date and time
        $stmt->bind_param("sss", $semester_id, $idnumber, $createdAt);
        $stmt->execute();
        $response = ['action' => 'saved'];
    }

    // Close the statement
    $stmt->close();
    // Return response as JSON
    echo json_encode($response);
}

// Close the database connection
$conn->close();
?>
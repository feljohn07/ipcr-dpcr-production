<?php
session_start();
include '../../dbconnections/config.php'; // Update path as needed

// Set the timezone to Philippine Time
date_default_timezone_set('Asia/Manila');
$createdAt = date("Y-m-d H:i:s"); // Format for MySQL DATETIME

// Retrieve task_id, owner_id, semester_id, and task_type from POST request
$task_id = $_POST['task_id'];
$owner_id = $_POST['owner_id'];
$semester_id = $_POST['semester_id']; // Get semester_id
$task_type = $_POST['task_type']; // Get task_type
$new_deansmessage = $_POST['deansmessage']; // New Dean's message value

$response = ['success' => false];

// Update the deansmessage, deansnote_is_read, deansmessage_created_at, and increment times_of_return in the task_assignments table
$query = "
    UPDATE task_assignments 
    SET 
        deansmessage = ?, 
        deansnote_is_read = 0, 
        deansmessage_created_at = ?, 
        times_of_return = times_of_return + 1
    WHERE 
        assignuser = ? 
        AND idoftask = ? 
        AND semester_id = ? 
        AND task_type = ?
";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

// Bind parameters
$stmt->bind_param("ssssss", $new_deansmessage, $createdAt, $owner_id, $task_id, $semester_id, $task_type);

if ($stmt->execute()) {
    $response['success'] = true;
} else {
    $response['error'] = $stmt->error;
}

$stmt->close();
$conn->close();

echo json_encode($response);
?>

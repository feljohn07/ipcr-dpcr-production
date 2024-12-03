<?php
session_start();
include '../../dbconnections/config.php'; // Update path as needed

// Retrieve task_id, owner_id, semester_id, and task_type from POST request
$task_id = $_POST['task_id'];
$owner_id = $_POST['owner_id'];
$semester_id = $_POST['semester_id']; // Get semester_id
$task_type = $_POST['task_type']; // Get task_type
$new_quality = $_POST['quality']; // New quality value

$response = ['success' => false];

// Update quality
$query = "
    UPDATE task_assignments 
    SET 
        quality = ? 
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
$stmt->bind_param("sssss", $new_quality, $owner_id, $task_id, $semester_id, $task_type);

if ($stmt->execute()) {
    $response['success'] = true;
} else {
    $response['error'] = $stmt->error;
}

$stmt->close();
$conn->close();

echo json_encode($response);
?>
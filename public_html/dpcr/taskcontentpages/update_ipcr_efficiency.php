<?php
session_start();
include '../../dbconnections/config.php'; // Update path as needed
include '../../feature_experiment/notify_users/includes/notify_user_for_changes.php';

// Retrieve task_id, owner_id, semester_id, and task_type from POST request
$task_id = $_POST['task_id'];
$owner_id = $_POST['owner_id'];
$semester_id = $_POST['semester_id']; // Get semester_id
$task_type = $_POST['task_type']; // Get task_type
$new_efficiency = $_POST['efficiency']; // New efficiency value

$task_name = $_POST['task_name']; 
$task_description = $_POST['task_description']; 

$message = 'Dean rated Efficiency: ' . $new_efficiency . " for the Submitted Document" . '<br>Task Type: ' . $task_type . '<br><br>Task Name:<br>' . $task_name . '<br><br>Task Description<br>' . $task_description;

notify_user_for_changes([$owner_id], $message);

$response = ['success' => false, 'message' => $message];

// Update efficiency
$query = "
    UPDATE task_assignments 
    SET 
        efficiency = ? 
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
$stmt->bind_param("sssss", $new_efficiency, $owner_id, $task_id, $semester_id, $task_type);

if ($stmt->execute()) {
    $response['success'] = true;
} else {
    $response['error'] = $stmt->error;
}

$stmt->close();
$conn->close();

echo json_encode($response);
?>
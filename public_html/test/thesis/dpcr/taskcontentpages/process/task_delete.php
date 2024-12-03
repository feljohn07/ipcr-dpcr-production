<?php
session_start();
include '../../../dbconnections/config.php';

// Retrieve task ID and type from the query string
$task_id = isset($_GET['task_id']) ? intval($_GET['task_id']) : 0;
$task_type = isset($_GET['task_type']) ? $_GET['task_type'] : '';
$semester_id = isset($_GET['semester_id']) ? intval($_GET['semester_id']) : 0;

// Validate inputs
if ($task_id <= 0 || empty($task_type) || $semester_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit();
}

// Fetch the documents required for the task before deleting it
$documents_required = 0;
if ($task_type === 'strategic') {
    $stmt = $conn->prepare("SELECT documents_req FROM strategic_tasks WHERE task_id = ?");
} elseif ($task_type === 'core') {
    $stmt = $conn->prepare("SELECT documents_req FROM core_tasks WHERE task_id = ?");
} elseif ($task_type === 'support') {
    $stmt = $conn->prepare("SELECT documents_req FROM support_tasks WHERE task_id = ?");
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid task type.']);
    exit();
}

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit();
}

$stmt->bind_param("i", $task_id);
$stmt->execute();
$stmt->bind_result($documents_required);
$stmt->fetch();
$stmt->close();

// Delete corresponding entries in task_attachments first
$task_type_value = ($task_type === 'strategic') ? 'strategic' : (($task_type === 'core') ? 'core' : 'support');
$delete_attachments_stmt = $conn->prepare("DELETE FROM task_attachments WHERE id_of_task = ? AND id_of_semester = ? AND task_type = ?");
if (!$delete_attachments_stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit();
}

$delete_attachments_stmt->bind_param("iis", $task_id, $semester_id, $task_type_value);
if (!$delete_attachments_stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Execute failed: ' . $delete_attachments_stmt->error]);
    exit();
}

// Get the number of rows deleted from task_attachments
$deleted_rows = $delete_attachments_stmt->affected_rows;
$delete_attachments_stmt->close();

// Now delete the task from its respective table
if ($task_type === 'strategic') {
    $stmt = $conn->prepare("DELETE FROM strategic_tasks WHERE task_id = ?");
} elseif ($task_type === 'core') {
    $stmt = $conn->prepare("DELETE FROM core_tasks WHERE task_id = ?");
} elseif ($task_type === 'support') {
    $stmt = $conn->prepare("DELETE FROM support_tasks WHERE task_id = ?");
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid task type.']);
    exit();
}

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit();
}

$stmt->bind_param("i", $task_id);
if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Execute failed: ' . $stmt->error]);
    exit();
}
$stmt->close();

// Fetch the current value of overall_documents_uploaded
$current_stmt = $conn->prepare("SELECT overall_documents_uploaded FROM semester_tasks WHERE semester_id = ?");
if (!$current_stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit();
}

$current_stmt->bind_param("i", $semester_id);
$current_stmt->execute();
$current_stmt->bind_result($current_documents_uploaded);
$current_stmt->fetch();
$current_stmt->close();

// Calculate the new value based on deleted rows
$new_documents_uploaded = $current_documents_uploaded - $deleted_rows;

// Ensure the new value does not go below zero
if ($new_documents_uploaded < 0) {
    $new_documents_uploaded = 0;
}

// Update the overall_documents_uploaded in semester_tasks
$update_stmt = $conn->prepare("UPDATE semester_tasks SET overall_documents_uploaded = ? WHERE semester_id = ?");
if (!$update_stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit();
}

$update_stmt->bind_param("ii", $new_documents_uploaded, $semester_id);
if (!$update_stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Execute failed: ' . $update_stmt->error]);
    exit();
}
$update_stmt->close();

// Return success response
echo json_encode(['success' => true, 'message' => 'Task deleted successfully.']);
?>
<?php
session_start();
include '../../dbconnections/config.php';
// include '../../feature_experiment/notify_users/includes/notify_user_for_changes.php';

$data = json_decode(file_get_contents("php://input"), true);
$assignuser = $data['assignuser'] ?? null; // Use null coalescing operator to avoid undefined index
$deansMessage = $data['deansMessage'] ?? null;

// notify_user_for_changes([$assignuser], 'Dean\'s Message ');

if ($assignuser === null || $deansMessage === null) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

// Prepare and execute the update query
$query = "UPDATE task_assignments SET deansmessage = ? WHERE assignuser = ?";
$stmt = $conn->prepare($query);
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => "Prepare failed: " . $conn->error]);
    exit;
}
$stmt->bind_param("si", $deansMessage, $userId);
$result = $stmt->execute();
$stmt->close();

if ($result) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $conn->error]);
}

$conn->close();
?>
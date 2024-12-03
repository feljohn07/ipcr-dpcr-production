<?php
session_start();
include '../../dbconnections/config.php'; // Updated relative path

// Retrieve JSON data from the request
$data = json_decode(file_get_contents('php://input'), true);

$task_id = $data['taskId'] ?? null;
$quality = $data['quality'] ?? null;
$efficiency = $data['efficiency'] ?? null;
$timeliness = $data['timeliness'] ?? null;
$task_type = $data['taskType'] ?? null;

if ($task_id && $task_type && in_array($task_type, ['strategic_tasks', 'core_tasks', 'support_tasks'])) {
    // Determine which table to update based on taskType
    switch ($task_type) {
        case 'strategic_tasks':
            $query = "UPDATE strategic_tasks SET 
                        quality = ?, 
                        efficiency = ?, 
                        timeliness = ?, 
                        average = (quality + efficiency + timeliness) / 3 
                      WHERE task_id = ?";
            break;
        case 'core_tasks':
            $query = "UPDATE core_tasks SET 
                        quality = ?, 
                        efficiency = ?, 
                        timeliness = ?, 
                        average = (quality + efficiency + timeliness) / 3 
                      WHERE task_id = ?";
            break;
        case 'support_tasks':
            $query = "UPDATE support_tasks SET 
                        quality = ?, 
                        efficiency = ?, 
                        timeliness = ?, 
                        average = (quality + efficiency + timeliness) / 3 
                      WHERE task_id = ?";
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid task type']);
            exit;
    }

    // Prepare the statement
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
        exit;
    }

    // Change parameter types to accommodate floating-point values
    $types = 'dddi'; // 'd' for double (floating-point), 'i' for integer
    $params = [$quality, $efficiency, $timeliness, $task_id];

    // Bind parameters
    $stmt->bind_param($types, ...$params);

    // Execute the statement
    $result = $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => $result]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
}

$conn->close();
?>
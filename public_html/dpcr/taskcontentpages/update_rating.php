<?php
session_start();
include '../../dbconnections/config.php'; // Updated relative path

include '../../feature_experiment/notify_users/includes/notify_user_for_changes.php';

// Retrieve JSON data from the request
$data = json_decode(file_get_contents('php://input'), true);

$task_id = $data['taskId'] ?? null;
$quality = $data['quality'] ?? null;
$efficiency = $data['efficiency'] ?? null;
$timeliness = $data['timeliness'] ?? null;
$task_type = $data['taskType'] ?? null;

// Rex: added notification message and list of users that will be notified;
$users = $data['users'] ?? null;
$task_name = $data['taskName'] ?? null;
$task_description = $data['taskDescription'] ?? null;

if ($task_id && $task_type && in_array($task_type, ['strategic_tasks', 'core_tasks', 'support_tasks'])) {
    $message = generate_notification_message($task_id, $task_type, $quality, $efficiency, $timeliness, $conn, $users, $task_name, $task_description);
} else {
    $message = "Hmm... something gone wrong";
}


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
        
    echo json_encode(['success' => $result,  'data'=> $data, 'message' => $message]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
}

$conn->close();




// Rex - generates message for the notification
function generate_notification_message($task_id, $task_type, $quality, $efficiency, $timeliness, $conn, $users, $task_name, $task_description) {
    // Initialize the notification message
    $notification_message = "Dean rated your assigned task (DPCR): <br>";

    // Determine which task table to query based on task type
    $task_table = '';
    switch ($task_type) {
        case 'strategic_tasks':
            $task_table = 'strategic_tasks';
            break;
        case 'core_tasks':
            $task_table = 'core_tasks';
            break;
        case 'support_tasks':
            $task_table = 'support_tasks';
            break;
        default:
            return "Invalid task type.";
    }

    // Select current values from the task table
    $query = "SELECT quality, efficiency, timeliness FROM {$task_table} WHERE task_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $task_id);
    $stmt->execute();
    $stmt->bind_result($current_quality, $current_efficiency, $current_timeliness);
    $stmt->fetch();
    $stmt->close();

    // Initialize an array to hold the changed attributes
    $changed_attributes = [];

    // Check if each attribute has changed and add it to the message
    if ($current_quality != $quality) {
        $changed_attributes[] = "<br>Quality: from {$current_quality} to {$quality}";
    }
    if ($current_efficiency != $efficiency) {
        $changed_attributes[] = "<br>Efficiency: from {$current_efficiency} to {$efficiency}";
    }
    if ($current_timeliness != $timeliness) {
        $changed_attributes[] = "<br>Timeliness: from {$current_timeliness} to {$timeliness}";
    }

    // If there are any changes, append them to the notification message
    if (!empty($changed_attributes)) {
        $notification_message .= implode(", ", $changed_attributes) . ". <br>Task ID: " . $task_id . "<br>Task Type: " . $task_type . '<br><br>Task Name: <br>' . $task_name . '<br><br>Task Description:<br>' . $task_description;
        // Notify the users about the changes
        notify_user_for_changes($users, $notification_message);
    } else {
        $notification_message = "No changes detected for this task.";
    }

    return $notification_message;
}

?>
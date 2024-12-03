<?php
session_start();
include '../../dbconnections/config.php'; // Database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $task_id = $_POST['task_id'];
    $new_task_type = $_POST['task_type'];
    $semester_id = $_POST['semester_id'];

    // Prepare and execute the update statement
    $stmt = $conn->prepare("UPDATE task_assignments SET newtask_type = ?, semester_id = ? WHERE id = ?");
    
    // Check if the statement was prepared successfully
    if ($stmt) {
        $stmt->bind_param("sii", $new_task_type, $semester_id, $task_id);

        if ($stmt->execute()) {
            // Update was successful
            echo json_encode(['success' => true, 'message' => 'Task type updated successfully.']);
        } else {
            // Update failed
            echo json_encode(['success' => false, 'message' => 'Failed to update task type.']);
        }

        $stmt->close(); // Close the prepared statement
    } else {
        // Error in preparing the statement
        echo json_encode(['success' => false, 'message' => 'Failed to prepare the statement.']);
    }

    $conn->close(); // Close the database connection
    exit; // Terminate the script
}
?>

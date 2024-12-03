<?php
session_start();
include '../../dbconnections/config.php'; // Include your database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $taskId = $_POST['task_id'];
    $taskName = $_POST['task_name'];
    $description = $_POST['description'];
    $taskType = $_POST['task_type'];
    $documentsRequired = $_POST['documents_required'];

    // Prepare the SQL statement to update the task
    $stmt = $conn->prepare("
        UPDATE ipcrsubmittedtask 
        SET task_name = ?, description = ?, task_type = ?, documents_required = ?
        WHERE task_id = ?
    ");

    // Bind parameters and execute
    $stmt->bind_param("ssssi", $taskName, $description, $taskType, $documentsRequired, $taskId);
    
    if ($stmt->execute()) {
        // Return a success message
        echo "Task updated successfully!";
    } else {
        // Return an error message
        http_response_code(500); // Set HTTP response code to 500 for server error
        echo "Error updating task: " . $stmt->error; // Provide error details
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();
}
?>
<?php
session_start();
include '../../../dbconnections/config.php'; // Include your database connection

// Check if the user is logged in
if (!isset($_SESSION['idnumber'])) {
    echo "Not logged in";
    exit();
}

// Check if the task ID is provided
if (isset($_POST['task_id'])) {
    $taskId = $_POST['task_id'];

    // Prepare the DELETE statement
    $stmt = $conn->prepare("DELETE FROM ipcrsubmittedtask WHERE task_id = ?");
    $stmt->bind_param("i", $taskId);

    // Execute the statement and check if it was successful
    if ($stmt->execute()) {
        echo 'success';
    } else {
        echo 'error';
    }

    $stmt->close();
} else {
    echo 'No task ID provided.';
}

$conn->close();
?>

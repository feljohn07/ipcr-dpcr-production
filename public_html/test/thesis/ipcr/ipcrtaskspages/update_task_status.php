<?php
include '../../dbconnections/config.php'; // Database connection
session_start(); // Start the session to access session variables

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task_id = $_POST['task_id'];
    $action = $_POST['action'];
    $status = ''; // Initialize status variable
    $college = $_SESSION['college']; // Retrieve the college from session

    if ($action === 'approve') {
        $status = 'approved';
        $stmt = $conn->prepare("UPDATE task_assignments SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $task_id);
    } elseif ($action === 'decline') {
        $status = 'declined';
        $message = isset($_POST['message']) ? $_POST['message'] : '';
        $stmt = $conn->prepare("UPDATE task_assignments SET status = ?, message = ? WHERE id = ?");
        $stmt->bind_param("ssi", $status, $message, $task_id);
    }

    if ($stmt->execute()) {
        $stmt->close(); // Close the previous statement

        // Fetch additional data from task_assignments, including firstname and lastname
        $stmt = $conn->prepare("SELECT semester_id, idoftask, task_type, assignuser, task_name, firstname, lastname FROM task_assignments WHERE id = ?");
        $stmt->bind_param("i", $task_id);
        $stmt->execute();
        $stmt->bind_result($semester_id, $idoftask, $task_type, $assignuser, $task_name, $firstname, $lastname);
        $stmt->fetch();
        $stmt->close(); // Close the select statement

        // Set timezone to Asia/Manila
        date_default_timezone_set('Asia/Manila');
        $created_at = date('Y-m-d H:i:s'); // Get the current date and time

        // Insert into task_history without task_id
        $insert_stmt = $conn->prepare("INSERT INTO for_ipcrtask_noty (semester_id, idoftask, task_type, assignuser, task_name, status, college, firstname, lastname, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        // Update the bind_param according to the new column list
        $insert_stmt->bind_param("iissssssss", $semester_id, $idoftask, $task_type, $assignuser, $task_name, $status, $college, $firstname, $lastname, $created_at);
        
        if ($insert_stmt->execute()) {
            header("Location: ipcrtask.php"); // Redirect to assigned tasks page
            exit();
        } else {
            echo "Error inserting record: " . $conn->error;
        }

        $insert_stmt->close();
    } else {
        echo "Error updating record: " . $conn->error;
    }

    $conn->close();
} else {
    // Handle invalid requests
    echo "Invalid request method.";
}
?>
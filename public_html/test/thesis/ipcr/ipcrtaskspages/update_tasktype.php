<?php
session_start();
include '../../dbconnections/config.php'; // Database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $task_id = $_POST['task_id'];
    $new_task_type = $_POST['task_type'];
    $semester_id = $_POST['semester_id'];
    $user_id = $_POST['idnumber']; // Assuming user_id is passed in the POST request

    // Debugging: Check POST values
    var_dump($_POST);

    // First, retrieve the sibling_code of the current task
    $stmt = $conn->prepare("SELECT sibling_code FROM task_assignments WHERE id = ?");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database prepare failed: ' . $conn->error]);
        exit;
    }
    $stmt->bind_param("i", $task_id);
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $sibling_code = $row['sibling_code'];

            // Debugging: Check the retrieved sibling code
            echo json_encode(['sibling_code' => $sibling_code]); // Debug output

            // Prepare and execute the update statement for all tasks with the same sibling_code and assignuser
            $update_stmt = $conn->prepare("UPDATE task_assignments SET newtask_type = ? WHERE sibling_code = ? AND assignuser = ?");
            if (!$update_stmt) {
                echo json_encode(['success' => false, 'message' => 'Failed to prepare the update statement: ' . $conn->error]);
                exit;
            }
            $update_stmt->bind_param("sss", $new_task_type, $sibling_code, $user_id); // Use "sss" if user_id is a string

            if ($update_stmt ->execute()) {
                // Update was successful
                echo json_encode(['success' => true, 'message' => 'Task type updated successfully for all tasks with the same sibling code and user ID.']);
            } else {
                // Update failed
                echo json_encode(['success' => false, 'message' => 'Failed to update task type for sibling code: ' . $update_stmt->error]);
            }

            $update_stmt->close(); // Close the prepared statement
        } else {
            // Task not found
            echo json_encode(['success' => false, 'message' => 'Task not found.']);
        }
    } else {
        // Error in executing the statement
        echo json_encode(['success' => false, 'message' => 'Failed to retrieve sibling code: ' . $stmt->error]);
    }

    $stmt->close(); // Close the prepared statement
    $conn->close(); // Close the database connection
    exit; // Terminate the script
}
?> 
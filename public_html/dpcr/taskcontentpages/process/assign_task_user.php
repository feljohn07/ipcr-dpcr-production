<?php
session_start();
include '../../../dbconnections/config.php'; // Database connection

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $task_id = $_POST['task_id'];
    $task_name = $_POST['task_name'];
    $task_description = $_POST['task_description'];
    $selected_users = $_POST['users']; // This will now be an array of user IDs
    $semester_id = $_POST['semester_id'];
    $end_date = $_POST['end_date']; // Retrieve the end date from the POST data
    $targets = $_POST['targets']; // Get the targets from the POST data

    if (!empty($selected_users)) {
        $assignmentSuccess = true; // Initialize assignment success status
        $existingAssignments = []; // To track already assigned users
        $newAssignments = []; // To track newly assigned users

        foreach ($selected_users as $user_idnumber) {
            $firstname = $_POST['firstname'][$user_idnumber]; // Assuming you have a way to get first name
            $lastname = $_POST['lastname'][$user_idnumber]; // Assuming you have a way to get last name
            $target = isset($targets[$user_idnumber]) ? $targets[$user_idnumber] : 0; // Get the target value
            $task_type = 'type'; // Set the task type accordingly

            // Check for existing assignments
            $check_stmt = $conn->prepare("SELECT COUNT(*) FROM task_assignments WHERE idoftask = ? AND assignuser = ? AND semester_id = ? AND task_type = ?");
            $check_stmt->bind_param("isis", $task_id, $user_idnumber, $semester_id, $task_type);
            $check_stmt->execute();
            $check_stmt->bind_result($count);
            $check_stmt->fetch();
            $check_stmt->close();

            // Log the check for debugging
            error_log("Checking assignment for task_id: $task_id, user_idnumber: $user_idnumber, semester_id: $semester_id, task_type: $task_type, count: $count");

            // If user is already assigned for the same semester and task, add to existing assignments
            if ($count > 0) {
                $existingAssignments[] = $firstname . " " . $lastname; // Store user name
                continue; // Skip to the next user
            }

            // Proceed with inserting a new assignment
            $stmt = $conn->prepare("INSERT INTO task_assignments (idoftask, assignuser, lastname, firstname, task_type, status, message, created_at, semester_id, target, end_date, task_name, task_description) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?)");
            $status = 'pending'; // Default status
            $message = ''; // Set message if needed

            if ($stmt) {
                $stmt->bind_param("issssssissss", $task_id, $user_idnumber, $lastname, $firstname, $task_type, $status, $message, $semester_id, $target, $end_date, $task_name, $task_description);

                if (!$stmt->execute()) {
                    // Log the error message for debugging
                    error_log("Error executing statement: " . $stmt->error);
                    $assignmentSuccess = false; // Mark as failure if insert fails
                    $stmt->close(); // Close the statement before breaking
                    break; // Stop processing further if any insert fails
                }
                $stmt->close(); // Close the statement after executing
                $newAssignments[] = $firstname . " " . $lastname; // Store newly assigned user names

                // Call the function to update the documents_req_by_user based on task_type
                updateDocumentsReqByUser ($conn, $task_type, $semester_id, $task_id, $target);
            } else {
                // Log statement preparation error
                error_log("Error preparing statement: " . $conn->error);
                $assignmentSuccess = false; // Mark as failure if prepare fails
                break; // Stop processing further
            }
        }

        // Handle the response
        if (!empty($existingAssignments)) {
            // Prepare a list of already assigned users
            $assignedUserNames = implode(", ", $existingAssignments);
            http_response_code(400); // Bad Request
            echo json_encode(['message' => "The following users are already assigned: " . $assignedUserNames]);
        } elseif ($assignmentSuccess) {
            // Send success response only if all new assignments were successful
            http_response_code(200); // OK
            echo json_encode(['message' => 'Users assigned successfully!']);
        } else {
            // Handle general assignment failure
            http_response_code(500); // Internal Server Error
            echo json_encode(['message' => 'An error occurred while assigning users.']);
        }

        $conn->close(); // Close the database connection
    } else {
        // Send error response for no users selected
        http_response_code(400); // Bad Request
        echo json_encode(['message' => 'No user selected!']);
    }
} else {
    // If the request method is not POST
    http_response_code(405); // Method Not Allowed
    echo json_encode(['message' => 'Invalid request method!']);
}

// Function to update documents_req_by_user based on task_type
function updateDocumentsReqByUser ($conn, $task_type, $semester_id, $task_id, $target) {
    $tableName = '';
    switch ($task_type) {
        case 'strategic':
            $tableName = 'strategic_tasks';
            break;
        case 'core':
            $tableName = 'core_tasks';
            break;
        case 'support':
            $tableName = 'support_tasks';
            break;
        default:
            // Handle unknown task_type
            error_log("Unknown task_type: " . $task_type);
            return;
    }

    $stmt = $conn->prepare("SELECT task_id FROM $tableName WHERE semester_id = ? AND task_id = ?");
    $stmt->bind_param("ii", $semester_id, $task_id);
    $stmt->execute();
    $stmt->store_result();
    $numRows = $stmt->num_rows;

    if ($numRows > 0) {
        $stmt = $conn->prepare("UPDATE $tableName SET documents_req_by_user = documents_req_by_user + ? WHERE semester_id = ? AND task_id = ?");
        $stmt->bind_param("iii", $target, $semester_id, $task_id);
        $stmt->execute();
    }

    $stmt->close();
}
?>
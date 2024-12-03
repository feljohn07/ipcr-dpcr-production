<?php
// Connect to the database
include '../../../dbconnections/config.php'; // Database connection

if (isset($_POST['id'])) {
    $userId = $_POST['id'];

    // First, retrieve the current target and task_type for the user
    $query = "SELECT target, task_type, semester_id FROM task_assignments WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($currentTarget, $taskType, $semesterId);
    $stmt->fetch();
    $stmt->close();

    // Check if the user was found
    if ($currentTarget !== null && $taskType && $semesterId !== null) {
        // Determine the table name based on task_type
        $tableName = '';
        switch ($taskType) {
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
                echo "Unknown task type.";
                exit;
        }

        // Decrement the documents_req_by_user column
        $decrementQuery = "UPDATE $tableName SET documents_req_by_user = documents_req_by_user - ? WHERE semester_id = ?";
        $decrementStmt = $conn->prepare($decrementQuery);
        $decrementStmt->bind_param("ii", $currentTarget, $semesterId);

        if ($decrementStmt->execute()) {
            // Now delete the user from task_assignments
            $deleteQuery = "DELETE FROM task_assignments WHERE id = ?";
            $deleteStmt = $conn->prepare($deleteQuery);
            $deleteStmt->bind_param("i", $userId);

            if ($deleteStmt->execute()) {
                echo "success"; // Send success response
            } else {
                echo "Error deleting user: " . $deleteStmt->error; // Send error message
            }

            $deleteStmt->close();
        } else {
            echo "Error decrementing documents_req_by_user: " . $conn->error; // Send error message
        }

        $decrementStmt->close();
    } else {
        echo "User  not found.";
    }

    // Close the database connection
    $conn->close();
} else {
    echo "Invalid request.";
}
?>
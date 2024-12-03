<?php
// Connect to the database
include '../../../dbconnections/config.php'; // Database connection

if (isset($_POST['id']) && isset($_POST['target'])) {
    $userId = $_POST['id'];
    $newTarget = $_POST['target'];

    // First, retrieve the current target, task_type, and semester_id for the user
    $query = "SELECT target, task_type, semester_id FROM task_assignments WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($currentTarget, $taskType, $semesterId);
    $stmt->fetch();
    $stmt->close();

    // Check if task_type and semester_id were retrieved successfully
    if ($taskType && $semesterId !== null) {
        // Calculate the difference
        $difference = $newTarget - $currentTarget;

        // Update the target for the specified user
        $updateQuery = "UPDATE task_assignments SET target = ? WHERE id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param("si", $newTarget, $userId);

        if ($updateStmt->execute()) {
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

            // Update the documents_req_by_user column based on the difference
            $incrementQuery = "UPDATE $tableName SET documents_req_by_user = documents_req_by_user + ? WHERE semester_id = ? AND task_id = (SELECT idoftask FROM task_assignments WHERE id = ?)";
            $incrementStmt = $conn->prepare($incrementQuery);
            $incrementStmt->bind_param("iii", $difference, $semesterId, $userId);

            if ($incrementStmt->execute()) {
                echo "success"; // Send success response
            } else {
                echo "Error updating documents_req_by_user: " . $conn->error; // Send error message
            }

            $incrementStmt->close();
        } else {
            echo "Error updating target: " . $updateStmt->error; // Send error message
        }

        $updateStmt->close();
    } else {
        echo "User  not found.";
    }

    // Close the database connection
    $conn->close();
} else {
    echo "Invalid request.";
}
?>
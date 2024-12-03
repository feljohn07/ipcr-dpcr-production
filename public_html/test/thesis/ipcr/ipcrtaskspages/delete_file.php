<?php
session_start();
include '../../dbconnections/config.php'; // Database connection

$message = '';
$message_type = 'success'; // Default to success

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['task_id']) && isset($_POST['file_name'])) {
    $task_id = $_POST['task_id'];
    $file_name = $_POST['file_name'];
    $task_type = $_POST['task_type']; // Added task_type to identify the table
    $semester_id = $_POST['semester_id']; // Retrieve semester_id from the form
    $user_idnumber = $_SESSION['idnumber']; // Retrieve user id number

    // Retrieve idoftask value from task_assignments table
    $select_stmt = $conn->prepare("SELECT idoftask FROM task_assignments WHERE id = ?");
    $select_stmt->bind_param("i", $task_id);
    $select_stmt->execute();
    $select_result = $select_stmt->get_result();
    $idoftask = $select_result->fetch_assoc()['idoftask'];
    $select_stmt->close();

    // Check the is_add value before proceeding
    $check_is_add_stmt = $conn->prepare("SELECT is_add FROM task_attachments WHERE task_id = ? AND file_name = ?");
    $check_is_add_stmt->bind_param("is", $task_id, $file_name);
    $check_is_add_stmt->execute();
    $check_result = $check_is_add_stmt->get_result();
    $is_add = $check_result->fetch_assoc()['is_add'];
    $check_is_add_stmt->close();

    // Prepare delete statement
    $delete_stmt = $conn->prepare("DELETE FROM task_attachments WHERE task_id = ? AND file_name = ?");
    $delete_stmt->bind_param("is", $task_id, $file_name);

    if ($delete_stmt->execute()) {
        // Check if file was successfully deleted from the database
        if ($delete_stmt->affected_rows > 0) {
            // Remove the file from the server
            $file_path = '../../uploads/' . $file_name;
            if (file_exists($file_path)) {
                unlink($file_path);
            }

            // Perform decrement operations only if is_add is not 'yes'
            if ($is_add !== 'yes') {
                // Update the number of documents_uploaded in the appropriate table
                $update_stmt = $conn->prepare("
                    UPDATE " . ($task_type === 'strategic' ? 'strategic_tasks' : ($task_type === 'core' ? 'core_tasks' : 'support_tasks')) . " 
                    SET documents_uploaded = documents_uploaded - 1
                    WHERE task_id = ?
                ");
                $update_stmt->bind_param("i", $idoftask);
                $update_stmt->execute();
                $update_stmt->close();

                // Update the overall_documents_uploaded in semester_tasks based on semester_id
                $semester_update_stmt = $conn->prepare("
                    UPDATE semester_tasks 
                    SET overall_documents_uploaded = overall_documents_uploaded - 1
                    WHERE semester_id = ?
                ");
                $semester_update_stmt->bind_param("i", $semester_id);
                $semester_update_stmt->execute();
                $semester_update_stmt->close();

                // Decrement the num_file in task_assignments for the specific task
                $num_file_stmt = $conn->prepare("
                    UPDATE task_assignments 
                    SET num_file = num_file - 1
                    WHERE id = ?
                ");
                $num_file_stmt->bind_param("i", $task_id);
                $num_file_stmt->execute();
                $num_file_stmt->close();
            }

            $message = 'File deleted successfully.';
            if ($is_add === 'yes') {
                $message .= ' Decrement operations skipped due to is_add value.';
            }
        } else {
            $message = 'File not found.';
            $message_type = 'error';
        }
    } else {
        $message = 'Failed to delete file.';
        $message_type = 'error';
    }

    $delete_stmt->close();
} else {
    $message = 'Invalid request.';
    $message_type = 'error';
}

$_SESSION['delete_message'] = $message;
$_SESSION['delete_message_type'] = $message_type;

$conn->close();
header("Location: ../ipcrtaskspages/approvedtask.php"); // Redirect back to the approved tasks page
exit();
?>

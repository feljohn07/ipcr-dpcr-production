<?php
session_start();
include '../../dbconnections/config.php'; // Database connection

$message = '';
$message_type = 'success'; // Default to success

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file'])) {
    $task_id = $_POST['task_id'];
    $task_type = $_POST['task_type'];
    $semester_id = $_POST['semester_id']; // Retrieve semester_id from the form
    $user_idnumber = $_SESSION['idnumber']; // Retrieve user id number
    $user_firstname = $_SESSION['firstname'];
    $user_lastname = $_SESSION['lastname'];

    // Retrieve idoftask value from task_assignments table
    $select_stmt = $conn->prepare("SELECT idoftask FROM task_assignments WHERE id = ?");
    $select_stmt->bind_param("i", $task_id);
    $select_stmt->execute();
    $select_result = $select_stmt->get_result();
    $idoftask = $select_result->fetch_assoc()['idoftask'];
    $select_stmt->close();

    $upload_count = 0; // Initialize count for successfully uploaded files

    foreach ($_FILES['file']['tmp_name'] as $key => $tmp_name) {
        if ($_FILES['file']['error'][$key] == UPLOAD_ERR_OK) {
            $file_name = $_FILES['file']['name'][$key];
    
            // Replace spaces with underscores
            $file_name = preg_replace('/[()]/', '', $file_name); // Remove parentheses
            $file_name = preg_replace('/[^A-Za-z0-9._-]/', '_', $file_name); // Replace unwanted characters with underscores
            $file_name = str_replace(' ', '_', $file_name); // Replace spaces with underscores
    
            $file_tmp = $_FILES['file']['tmp_name'][$key];
            $file_size = $_FILES['file']['size'][$key];
            $file_type = $_FILES['file']['type'][$key];
    
            // Check if the file already exists for the task
            $check_stmt = $conn->prepare("SELECT COUNT(*) AS file_count FROM task_attachments WHERE task_id = ? AND file_name = ?");
            $check_stmt->bind_param("is", $task_id, $file_name);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            $file_exists = $check_result->fetch_assoc()['file_count'] > 0;
            $check_stmt->close();
    
            if ($file_exists) {
                $message = 'File with name "' . $file_name . '" already attached.';
                $message_type = 'error';
                continue; // Skip this file
            }
    
            // Validate file type
            $allowed_types = ['doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'pdf', 'jpg', 'jpeg', 'png', 'avi', 'mov', 'wmv', 'mkv'];
            $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
            if (!in_array($file_ext, $allowed_types)) {
                $message = 'Invalid file type.';
                $message_type = 'error';
                continue;
            }
    
            // Set upload directory
            $upload_dir = '../../uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
    
            $file_path = $upload_dir . basename($file_name);
            if (move_uploaded_file($file_tmp, $file_path)) {
                // If file_content column is required, read the file content
                $file_content = file_get_contents($file_path);
    
                // Save file information to the database, including the semester_id
                $insert_stmt = $conn->prepare("INSERT INTO task_attachments (task_id, id_of_task, task_type, file_name, user_idnumber, file_content, file_type, id_of_semester, firstname, lastname, upload_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $insert_stmt->bind_param("iisssssiss", $task_id, $idoftask, $task_type, $file_name, $user_idnumber, $file_content, $file_type, $semester_id, $user_firstname, $user_lastname);
                $insert_stmt->execute();
                $insert_stmt->close();
    
                // Update created_at_for_upfiles and set is_read to 0 in task_assignments
                $timezone = new DateTimeZone('Asia/Manila'); // Set timezone to Philippines
                $currentDateTime = new DateTime('now', $timezone); // Get current date and time in Philippines timezone
                $currentDateTimeFormatted = $currentDateTime->format('Y-m-d H:i:s'); // Format the datetime
    
                // Prepare the update statement for task_assignments
                $task_assign_update_stmt = $conn->prepare("
                    UPDATE task_assignments 
                    SET created_at_for_upfiles = ?, is_read = 0 
                    WHERE id = ?
                ");
                $task_assign_update_stmt->bind_param("si", $currentDateTimeFormatted, $task_id); // Bind parameters
                $task_assign_update_stmt->execute(); // Execute the update statement
                $task_assign_update_stmt->close();
    
                $upload_count++;
            } else {
                $message = 'Failed to move uploaded file.';
                $message_type = 'error';
            }
        } else {
            $message = 'File upload error: ' . $_FILES['file']['error'][$key];
            $message_type = 'error';
        }
    }

    if ($upload_count > 0) {
        // Update the number of documents_uploaded in the appropriate table
        $update_stmt = $conn->prepare("
            UPDATE " . ($task_type === 'strategic' ? 'strategic_tasks' : ($task_type === 'core' ? 'core_tasks' : 'support_tasks')) . " 
            SET documents_uploaded = documents_uploaded + ?
            WHERE task_id = ?
        ");
        $update_stmt->bind_param("ii", $upload_count, $idoftask);
        $update_stmt->execute();
        $update_stmt->close();

        // Update the overall_documents_uploaded in semester_tasks based on semester_id
        $semester_update_stmt = $conn->prepare("
            UPDATE semester_tasks 
            SET overall_documents_uploaded = overall_documents_uploaded + ?
            WHERE semester_id = ?
        ");
        $semester_update_stmt->bind_param("ii", $upload_count, $semester_id);
        $semester_update_stmt->execute();
        $semester_update_stmt->close();

        // **Update the num_file in task_assignments**
        $task_assign_update_stmt = $conn->prepare("
            UPDATE task_assignments 
            SET num_file = num_file + ?
            WHERE id = ?
        ");
        $task_assign_update_stmt->bind_param("ii", $upload_count, $task_id);
        $task_assign_update_stmt->execute();
        $task_assign_update_stmt->close();

        // Append success message if no errors occurred
        $message = $upload_count . ' file(s) uploaded successfully.';
        $message_type = 'success';
    }
    
} else {
    $message = 'No files selected.';
    $message_type = 'error';
}

$_SESSION['upload_message'] = $message;
$_SESSION['upload_message_type'] = $message_type;

$conn->close();
?>

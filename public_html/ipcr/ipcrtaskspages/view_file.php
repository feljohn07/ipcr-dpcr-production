<?php
session_start();
include '../../dbconnections/config.php'; // Database connection

if (isset($_GET['id']) && isset($_GET['file_name'])) {
    $task_id = $_GET['id'];
    $file_name = $_GET['file_name'];

    $file_stmt = $conn->prepare("SELECT file_content, file_type FROM task_attachments WHERE task_id = ? AND file_name = ?");
    $file_stmt->bind_param("is", $task_id, $file_name);
    $file_stmt->execute();
    $file_stmt->store_result();
    $file_stmt->bind_result($file_content, $file_type);
    
    if ($file_stmt->num_rows > 0) {
        $file_stmt->fetch();
        $file_stmt->close();
        
        // Set headers and display content based on file type
        switch ($file_type) {
            case 'image/jpeg':
                case 'image/png':
                case 'image/gif':
                    // Display image
                    echo '<div style="display: flex; justify-content: center; align-items: center; height: 100vh;">';
                    echo '<img src="data:' . $file_type . ';base64,' . base64_encode($file_content) . '" alt="Image" style="max-width: 100%; height: auto;">';
                    echo '</div>';
                    break;

            case 'application/pdf':
                // Display PDF
                header('Content-Type: application/pdf');
                header('Content-Disposition: inline; filename="' . $file_name . '"');
                echo $file_content; // Output raw PDF content directly
                break;
                
            // Document types
            case 'application/msword': // .doc
            case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document': // .docx
            case 'application/vnd.ms-powerpoint': // .ppt
            case 'application/vnd.openxmlformats-officedocument.presentationml.presentation': // .pptx
            case 'application/vnd.ms-excel': // .xls
            case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': // .xlsx
                header('Content-Type: ' . $file_type);
                header('Content-Disposition: inline; filename="' . $file_name . '"');
                echo $file_content; // Output raw document content directly
                break;

            default:
                echo 'Unsupported file type.';
                break;
        }
    } else {
        echo 'File not found.';
    }
}

$conn->close();
?>
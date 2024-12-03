<?php
include '../../dbconnections/config.php'; // Include your database connection

// Check if the necessary parameters are set in the URL
if (isset($_GET['file'], $_GET['task_id'], $_GET['group_task_id'])) {
    $fileName = $_GET['file'];
    $taskId = $_GET['task_id'];
    $groupTaskId = $_GET['group_task_id'];

    // Prepare a statement to retrieve the file content from the database
    $stmt = $conn->prepare("
        SELECT file_content, file_type 
        FROM ipcr_file_submitted 
        WHERE file_name = ? AND task_id = ? AND group_task_id = ?
    ");
    $stmt->bind_param('sss', $fileName, $taskId, $groupTaskId);
    $stmt->execute();
    $stmt->bind_result($fileContent, $fileType);
    
    // If a file is found, serve it
    if ($stmt->fetch()) {
        // Set the appropriate headers based on the file type
        switch ($fileType) {
            case 'image/jpeg':
                case 'image/png':
                case 'image/gif':
                    // Display image
                    echo '<img src="data:' . $fileType . ';base64,' . base64_encode($fileContent) . '" alt="Image" style="max-width: 100%; height: 100%">';
                    break;
            case 'application/pdf':
                // For PDF files, set headers for inline display
                header('Content-Type: application/pdf');
                header('Content-Disposition: inline; filename="' . basename($fileName) . '"');
                echo $fileContent; // Output raw PDF content directly
                break;
            // Document types
            case 'application/msword': // .doc
            case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document': // .docx
            case 'application/vnd.ms-powerpoint': // .ppt
            case 'application/vnd.openxmlformats-officedocument.presentationml.presentation': // .pptx
            case 'application/vnd.ms-excel': // .xls
            case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': // .xlsx
                header('Content-Type: ' . $fileType);
                header('Content-Disposition: inline; filename="' . $fileName . '"');
                echo $fileContent; // Output raw document content directly
                break;
            default:
                echo 'Unsupported file type.';
                break;
        }
    } else {
        echo "File not found.";
    }

    $stmt->close();
} else {
    echo "Invalid request.";
}

// Close the database connection
$conn->close();
?>

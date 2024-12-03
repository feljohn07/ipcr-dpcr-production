<?php
session_start();
include '../../dbconnections/config.php'; // Updated relative path

// Check if task_id and file_name are set
if (!isset($_GET['task_id']) || !isset($_GET['file_name'])) {
    die("Invalid parameters.");
}

$task_id = $_GET['task_id'];
$file_name = $_GET['file_name'];

// Validate parameters to prevent directory traversal attacks
$task_id = preg_replace('/[^0-9]/', '', $task_id);
$file_name = basename($file_name);

// Ensure file name and task ID are valid
if (empty($task_id) || empty($file_name)) {
    die("Invalid parameters.");
}

// Prepare query to fetch the file content and its type from the database
$query = "SELECT file_content, file_type FROM task_attachments WHERE id_of_task = ? AND file_name = ?";
$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Error preparing the statement: " . $conn->error);
}

// Bind parameters
$stmt->bind_param("is", $task_id, $file_name);
$stmt->execute();
$result = $stmt->get_result();

// Check if the file was found
if ($result->num_rows === 0) {
    die("File not found in the database.");
}

// Fetch the file data
$file_data = $result->fetch_assoc();
$file_content = $file_data['file_content'];
$file_type = $file_data['file_type'];

$stmt->close();

// Switch case for different file types
switch ($file_type) {
    case 'image/jpeg':
        case 'image/png':
        case 'image/gif':
            // Display image as inline base64-encoded and center it
            echo '<div style="display: flex; justify-content: center; align-items: center; height: 100vh;">';
            echo '<img src="data:' . $file_type . ';base64,' . base64_encode($file_content) . '" alt="Image" style="max-width: 100%; height: auto;">';
            echo '</div>';
            break;
        // Serve image content directly
        header('Content-Type: ' . $file_type);
        header('Content-Disposition: inline; filename="' . htmlspecialchars($file_name) . '"');
        header('Content-Length: ' . strlen($file_content));
        echo $file_content; // Output image content
        break;

    case 'application/pdf':
        // Serve PDF content directly in the browser
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . htmlspecialchars($file_name) . '"');
        header('Content-Length: ' . strlen($file_content));
        echo $file_content; // Output PDF content
        break;

    case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document': // .docx
    case 'application/msword': // .doc
    case 'application/vnd.openxmlformats-officedocument.presentationml.presentation': // .pptx
    case 'application/vnd.ms-powerpoint': // .ppt
    case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': // .xlsx
    case 'application/vnd.ms-excel': // .xls
        // For document types, serve for download instead of inline viewing
        header('Content-Type: ' . $file_type);
        header('Content-Disposition: attachment; filename="' . htmlspecialchars($file_name) . '"');
        header('Content-Length: ' . strlen($file_content));
        echo $file_content; // Output document content for download
        break;

    default:
        die("Unsupported file type.");
}

exit;
?>

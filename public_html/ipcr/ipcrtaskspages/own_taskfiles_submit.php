<?php
session_start();
include '../../dbconnections/config.php'; // Include your database connection

// Ensure user is logged in and retrieve user info
if (!isset($_SESSION['idnumber'])) {
    die('Unauthorized access');
}

// Fetch user information from the session
$idnumber = $_SESSION['idnumber'];
$firstname = $_SESSION['firstname']; // Assuming you store firstname in session
$lastname = $_SESSION['lastname']; // Assuming you store lastname in session
$college = $_SESSION['college']; // Assuming you store college in session

function uploadFiles($files, $group_task_id, $task_id, $task_type, $semester_id) {
    global $conn, $idnumber, $firstname, $lastname, $college;

    $uploadedFiles = []; // To store uploaded file names
    $successfulUploads = 0; // To count successfully uploaded files
    $processedFiles = []; // To track files that have already been processed

    foreach ($files['name'] as $key => $name) {
       // Replace all whitespace characters with a single underscore and remove parentheses
$name = preg_replace('/\s+|[\(\)]/', '_', $name);

        // Check if the file has already been processed
        if (in_array($name, $processedFiles)) {
            echo "File with name \"$name\" has already been uploaded in this session. Skipping...\n";
            continue; // Skip this file
        }

        if ($files['error'][$key] === UPLOAD_ERR_OK) {
            $tmpName = $files['tmp_name'][$key];

            // Check if the file already exists for the task
            $check_stmt = $conn->prepare("SELECT COUNT(*) AS file_count FROM ipcr_file_submitted WHERE task_id = ? AND file_name = ?");
            $check_stmt->bind_param("is", $task_id, $name);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            $file_exists = $check_result->fetch_assoc()['file_count'] > 0;
            $check_stmt->close();

            // Skip if file already exists for this task
            if ($file_exists) {
                echo "File with name \"$name\" already attached to this task. Skipping...\n";
                continue; // Skip this file
            }

            // Read the file content
            $fileContent = file_get_contents($tmpName);
            $fileType = $files['type'][$key]; // Get the MIME type

            // Prepare SQL to insert the file details into the new table
            $stmt = $conn->prepare("INSERT INTO ipcr_file_submitted (user_id, firstname, lastname, college, group_task_id, task_id, semester_id, task_type, file_content, file_type, file_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssssss", $idnumber, $firstname, $lastname, $college, $group_task_id, $task_id, $semester_id, $task_type, $fileContent, $fileType, $name);
            
            // Execute the statement
            if ($stmt->execute()) {
                $uploadedFiles[] = $name; // Store successfully uploaded file names
                $successfulUploads++; // Increment the count of successful uploads
                $processedFiles[] = $name; // Track this file as processed
            } else {
                echo "Failed to save file details: " . $stmt->error;
            }

            // Close the statement
            $stmt->close();
        } else {
            echo "Error uploading file: " . $files['error'][$key];
        }
    }

    // Update the documents_uploaded column in the ipcrsubmittedtask table
    if ($successfulUploads > 0) {
        // Prepare to update the documents_uploaded count
        $stmt = $conn->prepare("UPDATE ipcrsubmittedtask SET documents_uploaded = documents_uploaded + ? WHERE group_task_id = ? AND task_id = ?");
        $stmt->bind_param("iis", $successfulUploads, $group_task_id, $task_id);

        if ($stmt->execute()) {
            echo "Updated documents_uploaded successfully.";
        } else {
            echo "Failed to update documents_uploaded: " . $stmt->error;
        }

        // Close the statement
        $stmt->close();
    }

    // Return the list of uploaded files
    return $uploadedFiles;
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $group_task_id = $_POST['group_task_id'];
    $task_id = $_POST['task_id']; // Ensure this is passed in the form
    $task_type = $_POST['task_type']; // Ensure this is passed in the form
    $semester_id = $_POST['id_of_semester']; // Retrieve semester_id from the form

    // Call the upload function
    $uploadedFiles = uploadFiles($_FILES['file'], $group_task_id, $task_id, $task_type, $semester_id);

    // You can display a success message or redirect the user
    if (!empty($uploadedFiles)) {
        echo "Files uploaded successfully: " . implode(', ', $uploadedFiles);
    } else {
        echo "No files were uploaded.";
    }
}

// Close the database connection
$conn->close();
?>
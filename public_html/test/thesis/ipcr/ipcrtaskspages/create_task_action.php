<?php
session_start();
include '../../dbconnections/config.php'; // Include your database connection

function createTask($data) {
    global $conn; // Use the database connection

    // Set the timezone to the Philippines
    date_default_timezone_set('Asia/Manila');
    $created_at = date('Y-m-d H:i:s'); // Get the current date and time

    // Get the selected semester_id and semester_name
    $semester_id = $data['semester_id'] ?? null; 
    $semester_name = $data['semester_name'] ?? null; 

    // Generate a unique group_task_id for the task group
    $group_task_id = time(); // Using the current timestamp as a unique group ID

    // Prepare the SQL statement for tasks
    $stmt = $conn->prepare("INSERT INTO ipcrsubmittedtask 
        (task_name, description, documents_required, college, idnumber, firstname, lastname, task_type, group_task_id, id_of_semester, name_of_semester, created_at, sibling_code, due_date) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    if (!$stmt) {
        echo json_encode(["status" => "error", "message" => "Failed to prepare statement: " . $conn->error]);
        return;
    }

    // Assuming you have the user's ID and name in the session
    $idnumber = $_SESSION['idnumber'] ?? null;
    $firstname = $_SESSION['firstname'] ?? null;
    $lastname = $_SESSION['lastname'] ?? null;

    $successfulInserts = 0; // Counter for successful inserts

    // Insert Strategic Tasks
    foreach ($data['strategic_task_name'] as $index => $task_name) {
        $description = $data['strategic_description'][$index];
        $documents_required = $data['strategic_documents_required'][$index];
        $college = $data['strategic_college'][$index];
        $task_type = 'strategic';
        $due_date = $data['strategic_due_date'][$index]; // Capture due date

        // Check for existing sibling_code
        $sibling_code = getSiblingCode($conn, $task_name, $task_type, $group_task_id);

        $stmt->bind_param("ssisssssssssss", $task_name, $description, $documents_required, $college, $idnumber, $firstname, $lastname, $task_type, $group_task_id, $semester_id, $semester_name, $created_at, $sibling_code, $due_date);
        if (!$stmt->execute()) {
            error_log("Error executing statement for strategic task: " . $stmt->error);
            continue; // Skip to the next iteration on error
        }
        $successfulInserts++;

        // Insert Header for Strategic Task
        if (isset($data['strategic_section_header'][$index]) && !empty($data['strategic_section_header'][$index])) {
            insertHeader($conn, $data['strategic_section_header'][$index], $semester_id, $idnumber, $task_type);
        }
    }

    // Insert Core Tasks
    foreach ($data['core_task_name'] as $index => $task_name) {
        $description = $data['core_description'][$index];
        $documents_required = $data['core_documents_required'][$index];
        $college = $data['core_college'][$index];
        $task_type = 'core';
        $due_date = $data['core_due_date'][$index]; // Capture due date

        // Check for existing sibling_code
        $sibling_code = getSiblingCode($conn, $task_name, $task_type, $group_task_id);

        $stmt->bind_param("ssisssssssssss", $task_name , $description, $documents_required, $college, $idnumber, $firstname, $lastname, $task_type, $group_task_id, $semester_id, $semester_name, $created_at, $sibling_code, $due_date);
        if (!$stmt->execute()) {
            error_log("Error executing statement for core task: " . $stmt->error);
            continue; // Skip to the next iteration on error
        }
        $successfulInserts++;

        // Insert Header for Core Task
        if (isset($data['core_section_header'][$index]) && !empty($data['core_section_header'][$index])) {
            insertHeader($conn, $data['core_section_header'][$index], $semester_id, $idnumber, $task_type);
        }
    }

    // Insert Support Tasks
    foreach ($data['support_task_name'] as $index => $task_name) {
        $description = $data['support_description'][$index];
        $documents_required = $data['support_documents_required'][$index];
        $college = $data['support_college'][$index];
        $task_type = 'support';
        $due_date = $data['support_due_date'][$index]; // Capture due date

        // Check for existing sibling_code
        $sibling_code = getSiblingCode($conn, $task_name, $task_type, $group_task_id);

        $stmt->bind_param("ssisssssssssss", $task_name, $description, $documents_required, $college, $idnumber, $firstname, $lastname, $task_type, $group_task_id, $semester_id, $semester_name, $created_at, $sibling_code, $due_date);
        if (!$stmt->execute()) {
            error_log("Error executing statement for support task: " . $stmt->error);
            continue; // Skip to the next iteration on error
        }
        $successfulInserts++;

        // Insert Header for Support Task
        if (isset($data['support_section_header'][$index]) && !empty($data['support_section_header'][$index])) {
            insertHeader($conn, $data['support_section_header'][$index], $semester_id, $idnumber, $task_type);
        }
    }

    $stmt->close();

    // Check for errors and return the appropriate response
    if ($successfulInserts > 0) {
        echo json_encode(["status" => "success", "message" => "Tasks created successfully!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to create tasks."]);
    }
}

// Function to get or generate sibling code
function getSiblingCode($conn, $task_name, $task_type, $group_task_id) {
    // Check if there's an existing sibling code for the same task name, type, and group ID (batch)
    $query = "SELECT sibling_code FROM ipcrsubmittedtask WHERE task_name = ? AND task_type = ? AND group_task_id = ? LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssi", $task_name, $task_type, $group_task_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $existingSiblingCode = $result->fetch_assoc()['sibling_code'] ?? null;
    $stmt->close();

    // If no sibling code exists for this batch, generate a new unique one
    if (!$existingSiblingCode) {
        $existingSiblingCode = uniqid($task_name . "_");
    }
    
    return $existingSiblingCode;
}

// Capture the form data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    createTask($_POST);
}

$conn->close(); // Close the database connection
?> 

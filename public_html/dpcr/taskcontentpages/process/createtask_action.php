<?php
session_start();
include '../../../dbconnections/config.php';

// Function to get or generate sibling code
function getSiblingCode($conn, $task_name, $task_type, $semester_id, $table_name) {
    // Check if there's an existing sibling code for the same task name and type
    $query = "SELECT sibling_code FROM " . strtolower($task_type) . "_tasks WHERE task_name = ? AND semester_id = ? LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $task_name, $semester_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $existingSiblingCode = $result->fetch_assoc()['sibling_code'] ?? null;
    $stmt->close();

    // If no sibling code exists for this task, generate a new unique one with the table name
    if (!$existingSiblingCode) {
        $existingSiblingCode = uniqid($table_name . "_" . $task_name . "_");
    }
    
    return $existingSiblingCode;
}

// Insert into semester_tasks, strategic_tasks, core_tasks, support_tasks
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $semester_name = $_POST['semester_name'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $college = $_POST['college'];

    // Retrieve office head idnumber and college from session
    $idnumber = $_SESSION['idnumber']; // Assuming this is correctly set in your login process
    $user_college = $_SESSION['college']; // Use the college value from the session

    // Ensure office_head_id is not empty or zero
    if (empty($idnumber) || $idnumber == '0') {
        die("Error: Office head ID is not set properly.");
    }

    // Insert into semester_tasks
    $stmt = $conn->prepare("INSERT INTO semester_tasks (semester_name, start_date, end_date, office_head_id, college) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $semester_name, $start_date, $end_date, $idnumber, $college);
    $stmt->execute();
    $semester_id = $stmt->insert_id;
    $stmt->close();

    $total_required_documents = 0;

    // Function to replace line breaks with "<break(+)line>"
    function replaceLineBreaks($string) {
        return str_replace(array("\r", "\n"), '<break(+)line>', $string);
    }

    // Insert into strategic_tasks
    foreach ($_POST['strategic_task_name'] as $index => $task_name) {
        $description = $_POST['strategic_description'][$index];
        $documents_req = $_POST['strategic_documents_required'][$index];
        $strategic_college = $_POST['strategic_college'][$index];
        $due_date = $_POST['strategic_due_date'][$index]; // Get the due date for the strategic task
        $limit_date = $_POST['end_date']; // Use the end_date input for limitdate

        // Replace line breaks with <break(+)line>
        $task_name = replaceLineBreaks($task_name);
        $description = replaceLineBreaks($description);

        if (!empty(trim($task_name)) && !empty(trim($description))) { // Ensure task_name and description are not empty
            $sibling_code = getSiblingCode($conn, $task_name, 'strategic', $semester_id, 'strategic_tasks'); // Get sibling code with table name

            $stmt = $conn->prepare("INSERT INTO strategic_tasks (semester_id, college, task_name, description, documents_req, limitdate, due_date, sibling_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssiss", $semester_id, $strategic_college, $task_name, $description, $documents_req, $limit_date, $due_date, $sibling_code);
            $stmt->execute();

            // Add to total required documents
            $total_required_documents += $documents_req;
        }
    }

    // Insert into core_tasks
    foreach ($_POST['core_task_name'] as $index => $task_name) {
        $description = $_POST['core_description'][$index];
        $documents_req = $_POST['core_documents_required'][$index];
        $core_college = $_POST['core_college'][$index];
        $due_date = $_POST['core_due_date'][$index]; // Get the due date for the core task
        $limit_date = $_POST['end_date']; // Use the end_date input for limitdate

        // Replace line breaks with <break(+)line>
        $task_name = replaceLineBreaks($task_name);
        $description = replaceLineBreaks($description);

        if (!empty(trim($task_name)) && !empty(trim($description))) { // Ensure task_name and description are not empty
            $sibling_code = getSiblingCode($conn, $task_name, 'core', $semester_id, 'core_tasks'); // Get sibling code with table name

            $stmt = $conn->prepare("INSERT INTO core_tasks (semester_id, college, task_name, description, documents_req, limitdate, due_date, sibling_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssiss", $semester_id, $core_college, $task_name, $description, $documents_req, $limit_date, $due_date, $sibling_code);
            $stmt->execute();

            // Add to total required documents
            $total_required_documents += $documents_req;
        }
    }

    // Insert into support_tasks
    foreach ($_POST['support_task_name'] as $index => $task_name) {
        $description = $_POST['support_description'][$index];
        $documents_req = $_POST['support_documents_required'][$index];
        $support_college = $_POST['support_college'][$index];
        $due_date = $_POST['support_due_date'][$index]; // Get the due date for the support task
        $limit_date = $_POST['end_date']; // Use the end_date input for limitdate

        // Replace line breaks with <break(+)line>
        $task_name = replaceLineBreaks($task_name);
        $description = replaceLineBreaks($description);

        if (!empty(trim($task_name)) && !empty(trim($description))) { // Ensure task_name and description are not empty
            $sibling_code = getSiblingCode($conn, $task_name, 'support', $semester_id, 'support_tasks'); // Get sibling code with table name

            $stmt = $conn->prepare("INSERT INTO support_tasks (semester_id, college, task_name, description, documents_req, limitdate, due_date, sibling_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssiss", $semester_id, $support_college, $task_name, $description, $documents_req, $limit_date, $due_date, $sibling_code);
            $stmt->execute();

            // Add to total required documents
            $total_required_documents += $documents_req;
        }
    }

    // Update the semester_tasks table with the total required documents
    $stmt = $conn->prepare("UPDATE semester_tasks SET overall_required_documents = ? WHERE semester_id = ?");
    $stmt->bind_param("ii", $total_required_documents, $semester_id);
    $stmt->execute();
    $stmt->close();

    // Insert into semester_task_logs in the other database
    $stmt = $conn->prepare("INSERT INTO semester_task_logs (semester_id, semester_name, office_head_id, college) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $semester_id, $semester_name, $idnumber, $user_college);
    $stmt->execute();
    $stmt->close();
    $conn->close();

    echo "Tasks added successfully.";
}
?>

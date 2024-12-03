<?php
session_start();
include '../../../dbconnections/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve the semester ID
    $semester_id = isset($_POST['semester_id']) ? $_POST['semester_id'] : '';
    if (empty($semester_id)) {
        die('Error: Semester ID is missing.');
    }

    // Retrieve and validate semester details
    $semester_name = isset($_POST['semester_name']) ? $_POST['semester_name'] : '';
    $start_date = isset($_POST['start_date']) ? $_POST['start_date'] : '';
    $end_date = isset($_POST['end_date']) ? $_POST['end_date'] : '';

    // Fetch the current college value
    $stmt = $conn->prepare("SELECT college FROM semester_tasks WHERE semester_id = ?");
    if (!$stmt) {
        die('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param("i", $semester_id);
    $stmt->execute();
    $stmt->bind_result($college);
    $stmt->fetch();
    $stmt->close();

    // Update semester details
    $stmt = $conn->prepare("UPDATE semester_tasks SET semester_name = ?, start_date = ?, end_date = ? WHERE semester_id = ?");
    if (!$stmt) {
        die('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param("sssi", $semester_name, $start_date, $end_date, $semester_id);
    if (!$stmt->execute()) {
        die('Execute failed: ' . $stmt->error);
    }
    $stmt->close();

    function updateTasks($conn, $task_type, $task_names, $descriptions, $documents_required, $due_dates, $college, $end_date, $semester_id) {
        // Update existing tasks
        $stmt = $conn->prepare("SELECT task_id FROM {$task_type}_tasks WHERE semester_id = ?");
        if (!$stmt) {
            die('Prepare failed: ' . $conn->error);
        }
        $stmt->bind_param("i", $semester_id);
        $stmt->execute();
        $stmt->bind_result($task_id);
        $existing_task_ids = array();
        while ($stmt->fetch()) {
            $existing_task_ids[] = $task_id;
        }
        $stmt->close();
        
        // Update existing tasks
        foreach ($existing_task_ids as $index => $task_id) {
            if (isset($task_names[$index])) {
                // Replace breaklines with <break(+)line> for specific tables
                $task_name_table = str_replace(["\r\n", "\n"], "<break(+)line>", $task_names[$index]);
                $description_table = str_replace(["\r\n", "\n"], "<break(+)line>", $descriptions[$index]);
    
                // Keep original breaklines for task_assignments
                $task_name_original = $task_names[$index];
                $description_original = $descriptions[$index];
                $documents_req = $documents_required[$index];
                $due_date = isset($due_dates[$index]) ? $due_dates[$index] : ''; // Get due_date
    
                // Update the task in the respective table
                $stmt = $conn->prepare("UPDATE {$task_type}_tasks SET task_name = ?, description = ?, documents_req = ?, limitdate = ?, due_date = ? WHERE task_id = ?");
                if (!$stmt) {
                    die('Prepare failed: ' . $conn->error);
                }
                $stmt->bind_param("sssssi", $task_name_table, $description_table, $documents_req, $end_date, $due_date, $task_id);
                if (!$stmt->execute()) {
                    die('Execute failed: ' . $stmt->error);
                }
                $stmt->close();
    
                // Update the task_assignments table, keeping the original format, without updating due_date
                $task_type_value = ($task_type === 'strategic') ? 'strategic' : (($task_type === 'core') ? 'core' : 'support');
                $update_assignments_stmt = $conn->prepare("UPDATE task_assignments SET task_name = ?, task_description = ?, end_date = ? WHERE idoftask = ? AND semester_id = ? AND task_type = ?");
                if (!$update_assignments_stmt) {
                    die('Prepare failed: ' . $conn->error);
                }
                $update_assignments_stmt->bind_param("ssissi", $task_name_original, $description_original, $end_date, $task_id, $semester_id, $task_type_value);
                if (!$update_assignments_stmt->execute()) {
                    die('Execute failed: ' . $update_assignments_stmt->error);
                }
                $update_assignments_stmt->close();
            } else {
                // If the task is removed, delete it
                $stmt = $conn->prepare("DELETE FROM {$task_type}_tasks WHERE task_id = ?");
                if (!$stmt) {
                    die('Prepare failed: ' . $conn->error);
                }
                $stmt->bind_param("i", $task_id);
                if (!$stmt->execute()) {
                    die('Execute failed: ' . $stmt->error);
                }
                $stmt->close();
            }
        }
    
        // Insert new tasks
        $new_task_index = count($existing_task_ids);
        for ($i = $new_task_index; $i < count($task_names); $i++) {
            // Replace breaklines with <break(+)line> for specific tables
            $task_name_table = str_replace(["\r\n", "\n"], "<break(+)line>", $task_names[$i]);
            $description_table = str_replace(["\r\n", "\n"], "<break(+)line>", $descriptions[$i]);
    
            // Keep original breaklines for task_assignments
            $task_name_original = $task_names[$i];
            $description_original = $descriptions[$i];
            $documents_req = $documents_required[$i];
            $due_date = isset($due_dates[$i]) ? $due_dates[$i] : ''; // Get due_date
    
            $stmt = $conn->prepare("INSERT INTO {$task_type}_tasks (semester_id, task_name, description, documents_req, college, limitdate, due_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if (!$stmt) {
                die('Prepare failed: ' . $conn->error);
            }
            $stmt->bind_param("issssss", $semester_id, $task_name_table, $description_table, $documents_req, $college, $end_date, $due_date);
            if (!$stmt->execute()) {
                die('Execute failed: ' . $stmt->error);
            }
            $stmt->close();
    
            // Insert into task_assignments table
            $task_type_value = ($task_type === 'strategic') ? 'strategic' : (($task_type === 'core') ? 'core' : 'support');
            $insert_assignments_stmt = $conn->prepare("INSERT INTO task_assignments (task_name, task_description, end_date, idoftask, semester_id, task_type) VALUES (?, ?, ?, LAST_INSERT_ID(), ?, ?)");
            if (!$insert_assignments_stmt) {
                die('Prepare failed: ' . $conn->error);
            }
            $insert_assignments_stmt->bind_param("ssssi", $task_name_original, $description_original, $end_date, $semester_id, $task_type_value);
            if (!$insert_assignments_stmt->execute()) {
                die('Execute failed: ' . $insert_assignments_stmt->error);
            }
            $insert_assignments_stmt->close();
        }
    
    

        // Calculate the total documents required for the semester
        $total_documents_req = 0;

        // Fetch total documents required from strategic tasks
        $stmt = $conn->prepare("SELECT SUM(documents_req) FROM strategic_tasks WHERE semester_id = ?");
        $stmt->bind_param("i", $semester_id);
        $stmt->execute();
        $stmt->bind_result($total_strategic);
        $stmt->fetch();
        $total_documents_req += $total_strategic ? $total_strategic : 0;
        $stmt->close();

        // Fetch total documents required from core tasks
        $stmt = $conn->prepare("SELECT SUM(documents_req) FROM core_tasks WHERE semester_id = ?");
        $stmt->bind_param("i", $semester_id);
        $stmt->execute();
        $stmt->bind_result($total_core);
        $stmt->fetch();
        $total_documents_req += $total_core ? $total_core : 0;
        $stmt->close();

        // Fetch total documents required from support tasks
        $stmt = $conn->prepare("SELECT SUM(documents_req) FROM support_tasks WHERE semester_id = ?");
        $stmt->bind_param("i", $semester_id);
        $stmt->execute();
        $stmt->bind_result($total_support);
        $stmt->fetch();
        $total_documents_req += $total_support ? $total_support : 0;
        $stmt->close();

        // Update the overall_required_documents in semester_tasks
        $stmt = $conn->prepare("UPDATE semester_tasks SET overall_required_documents = ? WHERE semester_id = ?");
        if (!$stmt) {
            die('Prepare failed: ' . $conn->error);
        }
        $stmt->bind_param("ii", $total_documents_req, $semester_id);
        if (!$stmt->execute()) {
            die('Execute failed: ' . $stmt->error);
        }
        $stmt->close();
    }

    if (isset($_POST['strategic_task_name'])) {
        $strategic_task_names = $_POST['strategic_task_name'];
        $strategic_descriptions = $_POST['strategic_description'];
        $strategic_documents_required = $_POST['strategic_documents_required'];
        $strategic_due_dates = $_POST['strategic_due_date']; // Add due_date field
    
        updateTasks($conn, 'strategic', $strategic_task_names, $strategic_descriptions, $strategic_documents_required, $strategic_due_dates, $college, $end_date, $semester_id);
    }

    // Update core tasks
    if (isset($_POST['core_task_name'])) {
        $core_task_names = $_POST['core_task_name'];
        $core_descriptions = $_POST['core_description'];
        $core_documents_required = $_POST['core_documents_required'];
        $core_due_dates = $_POST['core_due_date']; // Add due_date field
    
        updateTasks($conn, 'core', $core_task_names, $core_descriptions, $core_documents_required, $core_due_dates, $college, $end_date, $semester_id);
    }
    

    // Update support tasks
    if (isset($_POST['support_task_name'])) {
        $support_task_names = $_POST['support_task_name'];
        $support_descriptions = $_POST['support_description'];
        $support_documents_required = $_POST['support_documents_required'];
        $support_due_dates = $_POST['support_due_date']; // Add due_date field
    
        updateTasks($conn, 'support', $support_task_names, $support_descriptions, $support_documents_required, $support_due_dates, $college, $end_date, $semester_id);
    }
    

    // Set a success message in the session
    $_SESSION['success_message'] = "Tasks updated successfully.";

    // Redirect to dpcrdash.php
    header("Location: ../../dpcrdash.php");
    exit();
} else {
    die("Invalid request method.");
}
?>
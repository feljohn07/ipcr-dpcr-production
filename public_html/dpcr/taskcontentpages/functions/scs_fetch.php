<?php
include '../../../dbconnections/config.php'; // Updated relative path

// Retrieve semester_id from URL parameter
$semester_id = $_POST['semester_id'];
if (!isset($_POST['semester_id']) || empty($_POST['semester_id'])) {
    header("Location: ../../dpcrdash.php");
    exit(); // Ensure no further code is executed after the redirect
}

// Fetch semester details
$semester_stmt = $conn->prepare("SELECT * FROM semester_tasks WHERE semester_id = ?");
$semester_stmt->bind_param("i", $semester_id);
$semester_stmt->execute();
$semester_result = $semester_stmt->get_result();
$semester = $semester_result->fetch_assoc();
$semester_stmt->close();

// Function to fetch tasks and their assigned users with status
function fetch_tasks_with_status($conn, $table_name, $semester_id) {
    $stmt = $conn->prepare("
        SELECT t.task_name, t.description, t.*, 
            GROUP_CONCAT(CASE WHEN ta.status = 'pending' THEN CONCAT(ta.Lastname, ' ', ta.firstname, ' (', ta.target, ')') END) AS pending_users,
            GROUP_CONCAT(CASE WHEN ta.status = 'approved' THEN CONCAT(ta.Lastname, ' ', ta.firstname, ' (', ta.target, ')') END) AS approved_users,
            GROUP_CONCAT(CASE WHEN ta.status = 'declined' THEN CONCAT(ta.Lastname, ' ', ta.firstname, ': ', ta.message) END) AS declined_users
        FROM {$table_name} t
        LEFT JOIN task_assignments ta ON t.task_id = ta.idoftask AND ta.task_type = ?
        WHERE t.semester_id = ?
        GROUP BY t.task_id
    ");
    $task_type = substr($table_name, 0, strpos($table_name, '_'));
    $stmt->bind_param("si", $task_type, $semester_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $tasks = $result->fetch_all(MYSQLI_ASSOC);

        // Escaping special characters
// Escaping special characters and handling double line breaks
foreach ($tasks as &$row) {
    // Escape special characters
    $row['task_name'] = addslashes($row['task_name']);
    $row['description'] = addslashes($row['description']);

    // Replace double line breaks with a single line break
    $row['task_name'] = preg_replace('/\n{2,}/', "\n", $row['task_name']);
    $row['description'] = preg_replace('/\n{2,}/', "\n", $row['description']);

    // Escape single line breaks for JavaScript
    $row['task_name'] = str_replace("\n", '\\n', $row['task_name']);
    $row['description'] = str_replace("\n", '\\n', $row['description']);
}
$stmt->close();
return $tasks;
}

// Fetch tasks with user status
$strategic_tasks = fetch_tasks_with_status($conn, 'strategic_tasks', $semester_id);
$core_tasks = fetch_tasks_with_status($conn, 'core_tasks', $semester_id);
$support_tasks = fetch_tasks_with_status($conn, 'support_tasks', $semester_id);

?>

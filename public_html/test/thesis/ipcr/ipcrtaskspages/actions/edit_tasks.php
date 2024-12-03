<?php
session_start();
include '../../../dbconnections/config.php'; // Include your database connection

    // Retrieve user information from session
    $college = $_SESSION['college'];
    $idnumber = $_SESSION['idnumber'];
    $firstname = $_SESSION['firstname'];
    $lastname = $_SESSION['lastname'];

// Check if the user is logged in
if (!isset($_SESSION['idnumber'])) {
    header('Location: ../../ipcrdash.php'); // Redirect to login if not logged in
    exit();
}

// Initialize variables
$groupTaskId = $_GET['group_task_id'] ?? null;
$semesterId = $_GET['semester_id'] ?? null;

// Check if the group_task_id and semester_id are set in session
if (!isset($_SESSION['original_group_task_id']) || !isset($_SESSION['original_semester_id'])) {
    // Store original values in session if not set
    $_SESSION['original_group_task_id'] = $groupTaskId;
    $_SESSION['original_semester_id'] = $semesterId;
} else {
    // If the values in the URL don't match the session values, redirect
    if ($_SESSION['original_group_task_id'] !== $groupTaskId || $_SESSION['original_semester_id'] !== $semesterId) {
        header('Location: ../../ipcrdash.php'); // Redirect back to the dashboard
        exit();
    }
}

$categorizedTasks = [
    'strategic' => [],
    'core' => [],
    'support' => []
];

// Fetch tasks for the specified group task ID and semester ID
if ($groupTaskId && $semesterId) {
    $stmt = $conn->prepare("
    SELECT task_id, task_name, description, documents_required, due_date, task_type, sibling_code
    FROM ipcrsubmittedtask 
    WHERE group_task_id = ? AND id_of_semester = ?
    ORDER BY task_id
");
$stmt->bind_param("ss", $groupTaskId, $semesterId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    // Group tasks by their type
    $categorizedTasks[$row['task_type']][] = $row; 
}

    $stmt->close();
} else {
    echo "Invalid request.";
    exit();
}

// Set the default timezone to the Philippines
date_default_timezone_set('Asia/Manila');

// Handle form submission for editing tasks and adding new tasks
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Function to generate a unique sibling_code
    function generateUniqueSiblingCode($conn) {
        do {
            $siblingCode = uniqid(); // Example using uniqid for uniqueness
            $checkStmt = $conn->prepare("SELECT COUNT(*) FROM ipcrsubmittedtask WHERE sibling_code = ?");
            $checkStmt->bind_param("s", $siblingCode);
            $checkStmt->execute();
            $checkStmt->bind_result($count);
            $checkStmt->fetch();
            $checkStmt->close();
        } while ($count > 0); // Repeat until a unique code is found

        return $siblingCode;
    }
// Inside the loop where you insert new tasks
foreach ($_POST['new_tasks'] as $newTaskData) {
    // Other task data
    $taskName = $conn->real_escape_string($newTaskData['task_name']);
    $description = $conn->real_escape_string($newTaskData['description']);
    $documentsRequired = (int)$newTaskData['documents_required'];
    $taskType = $newTaskData['task_type'];
    $dueDate = $newTaskData['due_date'];

    // Generate a unique sibling_code
    $siblingCode = generateUniqueSiblingCode($conn);

    // Insert new task into the database
    $sql = "INSERT INTO ipcrsubmittedtask (task_name, description, documents_required, task_type, group_task_id, id_of_semester, college, idnumber, firstname, lastname, name_of_semester, created_at, is_read, due_date, sibling_code) 
            VALUES ('$taskName', '$description', $documentsRequired, '$taskType', '$groupTaskId', '$semesterId', '$college', '$idnumber', '$firstname', '$lastname', '$semesterName', NOW(), 1, '$dueDate', '$siblingCode')";

    if (!$conn->query($sql)) {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}
    // Update task names separately
// Update task names separately
$taskNamesToUpdate = []; // Array to hold sibling_code and task_name

foreach ($_POST['tasks'] as $taskData) {
    $taskId = $taskData['task_id'];
    $siblingCode = $taskData['sibling_code']; // Get sibling_code

    // Only add the task_name for the first task of each sibling_code to avoid duplicates
    if (!isset($taskNamesToUpdate[$siblingCode])) {
        $taskNamesToUpdate[$siblingCode] = $taskData['task_name'];
    }
}

// Now update all tasks with the same sibling_code
foreach ($taskNamesToUpdate as $siblingCode => $taskName) {
    $updateNameStmt = $conn->prepare("
    UPDATE ipcrsubmittedtask 
    SET task_name = ? 
    WHERE sibling_code = ?
    ");
    $updateNameStmt->bind_param("ss", $taskName, $siblingCode);
    if (!$updateNameStmt->execute()) {
        error_log("Error updating task name: " . $updateNameStmt->error);
        echo "Error updating task name: " . $updateNameStmt->error;
    } else {
        echo "Task name updated successfully for sibling code: $siblingCode";
    }
    $updateNameStmt->close();
}
    // Update other task details
    foreach ($_POST['tasks'] as $taskData) {
        $taskId = $taskData['task_id'];
        $description = $taskData['description'];
        $documentsRequired = $taskData['documents_required'];
        $dueDate = $taskData['due_date']; // Get the due date from the form

        $updateDetailsStmt = $conn->prepare("
        UPDATE ipcrsubmittedtask 
        SET description = ?, documents_required = ?, due_date = ? 
        WHERE task_id = ?
        ");
        $updateDetailsStmt->bind_param("sssi", $description, $documentsRequired, $dueDate, $taskId);
        if (!$updateDetailsStmt->execute()) {
            error_log("Error updating task details: " . $updateDetailsStmt->error);
            echo "Error updating task details: " . $updateDetailsStmt->error;
        } else {
            echo "Task details updated successfully for Task ID: $taskId";
        }
        $updateDetailsStmt->close();
    }
    // Insert new tasks
    if (isset($_POST['new_tasks'])) {
        // Fetch the semester name based on the semester_id
        $semesterName = '';
        $semesterId = $_GET['semester_id'] ?? null;
        // Retrieve user information from session
        $college = $_SESSION['college'];
        $idnumber = $_SESSION['idnumber'];
        $firstname = $_SESSION['firstname'];
        $lastname = $_SESSION['lastname'];
    
        if ($semesterId) {
            $semesterQuery = $conn->prepare("SELECT semester_name FROM semester_tasks WHERE semester_id = ?");
            $semesterQuery->bind_param("s", $semesterId);
            $semesterQuery->execute();
            $semesterResult = $semesterQuery->get_result();
    
            if ($semesterRow = $semesterResult->fetch_assoc()) {
                $semesterName = $semesterRow['semester_name'];
            }
            $semesterQuery->close();
        }
    
        foreach ($_POST['new_tasks'] as $newTaskData) {
            $taskName = $conn->real_escape_string($newTaskData['task_name']);
            $description = $conn->real_escape_string($newTaskData['description']);
            $documentsRequired = (int)$newTaskData['documents_required'];
            $taskType = $newTaskData['task_type']; // This should be set based on the button clicked
            $dueDate = $newTaskData['due_date']; // Capture the due date
    
            // Generate a unique sibling_code
            $siblingCode = generateUniqueSiblingCode($conn);
    
            // Check if the task already exists
            $checkTaskStmt = $conn->prepare("
                SELECT COUNT(*) FROM ipcrsubmittedtask 
                WHERE task_name = ? AND due_date = ? AND idnumber = ?
            ");
            $checkTaskStmt->bind_param("ssi", $taskName, $dueDate, $idnumber);
            $checkTaskStmt->execute();
            $checkTaskStmt->bind_result($taskCount);
            $checkTaskStmt->fetch();
            $checkTaskStmt->close();
    
            if ($taskCount > 0) {
                echo "Task already exists. Skipping insertion.";
                continue; // Skip this task if it already exists
            }
    
            // Insert new task into the database
            $sql = "INSERT INTO ipcrsubmittedtask (task_name, description, documents_required, task_type, group_task_id, id_of_semester, college, idnumber, firstname, lastname, name_of_semester, created_at, is_read, due_date, sibling_code) 
                    VALUES ('$taskName', '$description', $documentsRequired, '$taskType', '$groupTaskId', '$semesterId', '$college', '$idnumber', '$firstname', '$lastname', '$semesterName', NOW(), 1, '$dueDate', '$siblingCode')";
    
            if (!$conn->query($sql)) {
                echo "Error: " . $sql . "<br>" . $conn->error;
            }
        }
    }

    // Redirect back to the tasks page or show a success message
    header('Location: ../../ipcrdash.php'); // Change to your tasks page
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Tasks</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px; /* Space between tables */
        }
        th, td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f0f0f0;
        }
        textarea {
            width: 100%; /* Ensure textarea takes full width */
            height: 50px; /* Set a fixed height */
            box-sizing: border-box; /* Include padding in width */
        }
        .delete-task {
            background-color: #dc2626;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 5px 10px;
            cursor: pointer;
        }
        .add-task-button {
            background-color: #4caf50;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 5px 10px;
            cursor: pointer;
            margin-top: 10px;
        }
        button[type="submit"] {
            background-color: #2563eb; /* Bright blue button */
            color: white;
            padding: 14px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            width: 100%;
            font-size: 17px;
            font-weight: bold;
            transition: background-color 0.3s, transform 0.2s;
        }
        button[type="submit"]:hover {
            background-color: #1e40af; /* Darker blue on hover */
            transform: translateY(-2px);
        }
        button[type="submit"]:active {
            transform: translateY(0);
        }
    </style>
    <script>
        function deleteTask(taskId) {
            if (confirm("Are you sure you want to delete this task?")) {
                // Send an AJAX request to delete the task
                var xhr = new XMLHttpRequest();
                xhr.open('POST', '../actions/delete_own_task.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

                xhr.onload = function() {
                    if (xhr.status === 200) {
                        if (xhr.responseText.trim() === 'success') {
                            document.getElementById('task_' + taskId).remove();
                        } else {
                            alert('Failed to delete task: ' + xhr.responseText);
                        }
                    }
                };

                xhr.send('task_id=' + taskId);
            }
        }   

        function addTaskRow(type) {
    const taskContainer = document.getElementById(type + '_tasks');
    const taskId = Date.now(); // Unique ID for the new task

    const newTaskRow = `
        <tr class="task" id="task_${taskId}">
            <td>
                <input type="hidden" name="new_tasks[${taskId}][task_id]" value="${taskId}">
                <textarea name="new_tasks[${taskId}][task_name]" required></textarea>
            </td>
            <td>
                <textarea name="new_tasks[${taskId}][description]" required></textarea>
            </td>
            <td>
                <input type="number" name="new_tasks[${taskId}][documents_required]" min="0" required>
            </td>
            <td>
                <input type="date" name="new_tasks[${taskId}][due_date]" required>
            </td>
            <td>
                <input type="hidden" name="new_tasks[${taskId}][task_type]" value="${type}"> <!-- Add this line -->
                <button type="button" class="delete-task" onclick="deleteTask(${taskId})">Delete Task</button>
            </td>
        </tr>
    `;
    taskContainer.insertAdjacentHTML('beforeend', newTaskRow);
}
    </script>
    <script>
        function goBack() {
            window.location.href = '../../ipcrdash.php';
        }
    </script>
</head>
<body>
    <button id="back-button" type="button" style="background-color: green; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; position: fixed ; right: 20px; top: 20px; z-index: 1000;" onclick="goBack()">Back</button>
    <h2>Edit Tasks</h2>
    <form method="POST" action="">
        <?php foreach ($categorizedTasks as $type => $tasks): ?>
            <h3><?php echo ucfirst($type); ?> Tasks</h3>
            <table>
                <thead>
                    <tr>
                        <th>Task Name</th>
                        <th>Descriptions</th>
                        <th>Target</th>
                        <th>Due Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="<?php echo $type; ?>_tasks">
                    <?php
                    // Group tasks by task_name and sibling_code
                    $groupedTasks = [];
                    foreach ($tasks as $task) {
                        $key = $task['task_name'] . '|' . $task['sibling_code'] . '|' . $task['task_type'];
                        if (!isset($groupedTasks[$key])) {
                            $groupedTasks[$key] = [];
                        }
                        $groupedTasks[$key][] = $task;
                    }

                    // Build the table rows
                    foreach ($groupedTasks as $groupKey => $group) {
                        $firstTask = $group[0];
                        $rowSpan = count($group);
                        ?>
                            <tr class="task" id="task_<?php echo $firstTask['task_id']; ?>">
                                <td rowspan="<?php echo $rowSpan; ?>">
                                    <input type="hidden" name="tasks[<?php echo $firstTask['task_id']; ?>][task_id]" value="<?php echo htmlspecialchars($firstTask['task_id']); ?>">
                                    <input type="hidden" name="tasks[<?php echo $firstTask['task_id']; ?>][sibling_code]" value="<?php echo htmlspecialchars($firstTask['sibling_code']); ?>"> <!-- Hidden input for sibling_code -->
                                    <textarea name="tasks[<?php echo $firstTask['task_id']; ?>][task_name]" required><?php echo htmlspecialchars($firstTask['task_name']); ?></textarea>
                                </td>
                                <td>
                                <textarea name="tasks[<?php echo $firstTask['task_id']; ?>][description]" required><?php echo htmlspecialchars($firstTask['description']); ?></textarea>
                            </td>
                            <td>
                                <input type="number" name="tasks[<?php echo $firstTask['task_id']; ?>][documents_required]" value="<?php echo htmlspecialchars($firstTask['documents_required']); ?>" min="0" required>
                            </td>
                            <td>
                                <input type="date" name="tasks[<?php echo $firstTask['task_id']; ?>][due_date]" value="<?php echo htmlspecialchars($firstTask['due_date']); ?>" required>
                            </td>
                            <td>
                                <button type="button" class="delete-task" onclick="deleteTask(<?php echo $firstTask['task_id']; ?>)">Delete Task</button>
                            </td>
                        </tr>
                        <?php
                        // Output remaining tasks in the group
                        for ($i = 1; $i < $rowSpan; $i++) {
                            $task = $group[$i];
                            ?>
                            <tr class="task" id="task_<?php echo $task['task_id']; ?>">
                                <td>
                                    <textarea name="tasks[<?php echo $task['task_id']; ?>][description]" required><?php echo htmlspecialchars($task['description']); ?></textarea>
                                </td>
                                <td>
                                    <input type="number" name="tasks[<?php echo $task['task_id']; ?>][documents_required]" value="<?php echo htmlspecialchars($task['documents_required']); ?>" min="0" required>
                                </td>
                                <td>
                                    <input type="date" name="tasks[<?php echo $task['task_id']; ?>][due_date]" value="<?php echo htmlspecialchars($task['due_date']); ?>" required>
                                </td>
                                <td>
                                    <input type="hidden" name="tasks[<?php echo $task['task_id']; ?>][task_id]" value="<?php echo htmlspecialchars($task['task_id']); ?>">
                                    <input type="hidden" name="tasks[<?php echo $task['task_id']; ?>][task_name]" value="<?php echo htmlspecialchars($firstTask['task_name']); ?>"> <!-- Hidden field for task_name -->
                                    <button type="button" class="delete-task" onclick="deleteTask(<?php echo $task['task_id']; ?>)">Delete Task</button>
                                </td>
                            </tr>
                            <?php
                        }
                    }
                    ?>
                </tbody>
            </table>
            <button type="button" class="add-task-button" onclick="addTaskRow('<?php echo $type; ?>')">Add Task</button>
        <?php endforeach; ?>
        <button type="submit" style="margin-top: 20px;">Save Changes</button>
    </form>
</body>
</html>
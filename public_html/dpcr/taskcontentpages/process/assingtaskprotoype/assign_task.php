<?php
// Start session and include database connection
session_start();
include '../../../dbconnections/config.php';

if (!isset($_SESSION['college']) || !isset($_SESSION['idnumber'])) {
    echo "<p>Please log in to view this content.</p>";
    exit;
}

$college = $_SESSION['college'];
$current_user_idnumber = $_SESSION['idnumber'];

// Fetch task details
$task_id = isset($_GET['task_id']) ? (int)$_GET['task_id'] : 0;
$task_type = isset($_GET['type']) ? htmlspecialchars($_GET['type']) : '';
$semester_id = isset($_GET['semester_id']) ? (int)$_GET['semester_id'] : 0;

// Determine the table to query based on task_type
$table = '';
if ($task_type === 'strategic') {
    $table = 'strategic_tasks';
} elseif ($task_type === 'core') {
    $table = 'core_tasks';
} elseif ($task_type === 'support') {
    $table = 'support_tasks';
} else {
    echo "<p>Invalid task type!</p>";
    exit;
}

// Fetch task details including documents_req
$task_sql = "SELECT task_name, description, documents_req FROM $table WHERE task_id = ? AND semester_id = ?";
$task_stmt = $conn->prepare($task_sql);
$task_stmt->bind_param("ii", $task_id, $semester_id);
$task_stmt->execute();
$task_result = $task_stmt->get_result();
$task = $task_result->fetch_assoc();
$task_stmt->close();

if (!$task) {
    echo "<p>No task found with the specified parameters.</p>";
    exit;
}

// Fetch users from the same college excluding the current user
$usersinfo = $conn->prepare("SELECT * FROM usersinfo WHERE college = ? AND idnumber != ?");
$usersinfo->bind_param("ss", $college, $current_user_idnumber);
$usersinfo->execute();
$users_result = $usersinfo->get_result();
$users = $users_result->fetch_all(MYSQLI_ASSOC);
$usersinfo->close();

$target = !empty($task['documents_req']) ? $task['documents_req'] : '';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Table</title>
    <style>
        /* Table Container Styles */
        .table-container {
            width: 100%;
            overflow-y: auto;
            max-height: 400px; /* Set the desired height for the scrollable table body */
        }

        /* Table Styles */
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            padding: 8px;
            border: 1px solid #ddd;
            text-align: center;
        }
        th {
            background-color: #f2f2f2;
            position: sticky;
            top: 0;
            z-index: 1;
        }
        .target-input {
            width: 100%;
            padding: 4px;
        }

        /* Task Details Styling */
        .task-details {
            margin: 20px 0;
            padding: 15px;
            background-color: #f4f4f4;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1);
        }

        .task-details p {
            font-size: 16px;
            line-height: 1.6;
            color: black;
        }

        /* Footer Styling */
        .footer {
            margin-top: 30px;
            text-align: center;
            position: fixed;
            width: 100%;
            bottom: 0;
        }
        .footer button {
            padding: 10px 20px;
            margin: 5px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .footer button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>

<!-- Header Section: Task Name and Description -->
<div class="task-details">
    <p>Task: <?php echo htmlspecialchars($task['task_name']); ?></p>
    <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($task['description'])); ?></p>
    <p><strong>Documents Required:</strong> <?php echo(htmlspecialchars($task['documents_req'])); ?></p>
</div>

<h2>Select users to Assign</h2>

<!-- Form to submit selected users -->
<form action="../process/assign_task_user.php" method="post">
    <input type="hidden" name="task_id" value="<?php echo $task_id; ?>">
    <input type="hidden" name="task_name" value="<?php echo htmlspecialchars($task['task_name']); ?>">
    <input type="hidden" name="task_description" value="<?php echo htmlspecialchars($task['description']); ?>">
    <input type="hidden" name="semester_id" value="<?php echo $semester_id; ?>">

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Photo</th>
                    <th>ID Number</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Designation</th>
                    <th id="targetHeader">Target (<?php echo $target; ?>)</th>
                    <th>
                        Select (all 
                        <label for="selectAllCheckbox" style="margin-left: 5px;">
                            <input type="checkbox" id="selectAllCheckbox">
                        </label>)
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td>
                            <?php if (!empty($user['picture'])): ?>
                                <img src="data:image/jpeg;base64,<?php echo base64_encode($user['picture']); ?>" alt="Picture" width="50" height="50" style="display: block; margin: 0 auto;">
                            <?php else: ?>
                                <span>No Image</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($user['idnumber']); ?></td>
                        <td><?php echo htmlspecialchars($user['firstname']); ?></td>
                        <td><?php echo htmlspecialchars($user['lastname']); ?></td>
                        <td><?php echo htmlspecialchars($user['designation']); ?></td>
                        <td>
                            <input type="number" name="targets[<?php echo htmlspecialchars($user['idnumber']); ?>]" min="0" step="1" placeholder="Enter target" class="target-input" data-id="<?php echo htmlspecialchars($user['idnumber']); ?>">
                        </td>
                        <td>
                            <input type="checkbox" name="users[]" 
                                value="<?php echo htmlspecialchars($user['idnumber']); ?>" 
                                data-firstname="<?php echo htmlspecialchars($user['firstname']); ?>" 
                                data-lastname="<?php echo htmlspecialchars($user['lastname']); ?>"
                                class="user-checkbox"
                                data-target-selector=".target-input[data-id='<?php echo htmlspecialchars($user['idnumber']); ?>']">
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="footer">
        <button type="submit">Assign Selected Users</button>
    </div>
</form>

<script>
    // Toggle target input when a checkbox is checked
    document.querySelectorAll('.user-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const targetInput = document.querySelector(this.dataset.targetSelector);
            targetInput.disabled = !this.checked;
        });
    });

    // Select all checkboxes functionality
    document.getElementById('selectAllCheckbox').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.user-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
            const targetInput = document.querySelector(checkbox.dataset.targetSelector);
            targetInput.disabled = !checkbox.checked;
        });
    });
</script>

</body>
</html>
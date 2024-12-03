<?php
session_start();
// Include the database connection file
include '../dbconnections/config.php'; // Replace with the correct path to your database connection file

$semester_id = $_GET['semester_id'];

// Fetch semester details
$semester_stmt = $conn->prepare("SELECT * FROM semester_tasks WHERE semester_id = ?");
$semester_stmt->bind_param("i", $semester_id);
$semester_stmt->execute();
$semester_result = $semester_stmt->get_result();
$semester = $semester_result->fetch_assoc();
$semester_stmt->close();

// Fetch strategic tasks
$strategic_stmt = $conn->prepare("SELECT * FROM strategic_tasks WHERE semester_id = ?");
$strategic_stmt->bind_param("i", $semester_id);
$strategic_stmt->execute();
$strategic_result = $strategic_stmt->get_result();
$strategic_tasks = $strategic_result->fetch_all(MYSQLI_ASSOC);
$strategic_stmt->close();

// Transform task names and descriptions for strategic tasks
foreach ($strategic_tasks as &$task) {
    $task['task_name'] = str_replace('<break(+)line>', '<br>', $task['task_name']);
    $task['description'] = str_replace('<break(+)line>', '<br>', $task['description']);
}

// Fetch core tasks
$core_stmt = $conn->prepare("SELECT * FROM core_tasks WHERE semester_id = ?");
$core_stmt->bind_param("i", $semester_id);
$core_stmt->execute();
$core_result = $core_stmt->get_result();
$core_tasks = $core_result->fetch_all(MYSQLI_ASSOC);
$core_stmt->close();

// Transform task names and descriptions for core tasks
foreach ($core_tasks as &$task) {
    $task['task_name'] = str_replace('<break(+)line>', '<br>', $task['task_name']);
    $task['description'] = str_replace('<break(+)line>', '<br>', $task['description']);
}

// Fetch support tasks
$support_stmt = $conn->prepare("SELECT * FROM support_tasks WHERE semester_id = ?");
$support_stmt->bind_param("i", $semester_id);
$support_stmt->execute();
$support_result = $support_stmt->get_result();
$support_tasks = $support_result->fetch_all(MYSQLI_ASSOC);
$support_stmt->close();

// Transform task names and descriptions for support tasks
foreach ($support_tasks as &$task) {
    $task['task_name'] = str_replace('<break(+)line>', '<br>', $task['task_name']);
    $task['description'] = str_replace('<break(+)line>', '<br>', $task['description']);
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Semester Task Details - Semester <?php echo $semester['semester_name']; ?></title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 20px;
            color: #333;
            background-color: #f9f9f9;
        }

        h2 {
            font-size: 24px;
            color: #4CAF50;
            margin-bottom: 20px;
        }

        h3 {
            font-size: 20px;
            color: #4CAF50;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        th, td {
            padding: 12px;
            text-align: left;
            border: 1px solid #ddd;
        }

        th {
            background-color: #4CAF50;
            color: white;
            text-align: center;
        }

        tbody tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        tbody tr:hover {
            background-color: #e2f7e0;
        }

        td {
            font-size: 14px;
        }

        td a {
            color: #4CAF50;
            text-decoration: none;
        }

        td a:hover {
            text-decoration: underline;
        }

        /* Responsive Table Design */
        @media (max-width: 768px) {
            table {
                width: 100%;
                font-size: 12px;
            }

            th, td {
                padding: 10px;
            }
        }
        .close-btn {
        position: fixed;
        top: 10px;
        right: 10px;
        background-color: #ff5c5c;
        color: white;
        border: none;
        border-radius: 5px;
        padding: 10px 15px;
        cursor: pointer;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        z-index: 1000;
    }
</style>
<script>
    function closeTab() {
        window.close(); // Attempt to close the current tab
    }
</script>
    </style>
</head>
<body>
<button class="close-btn" onclick="closeTab()">Close This Tab</button>
    <h2><?php echo htmlspecialchars($semester['semester_name']); ?></h2>

    <h3>Strategic Tasks:</h3>
    <table>
        <thead>
            <tr>
                <th>Outputs</th>
                <th>Success Indicator
                (Target + Measures)</th>
                <th>Target</th>
                <th>Due date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($strategic_tasks as $task): ?>
                <tr>
                    <td><?php echo $task['task_name']; ?></td>
                    <td><?php echo $task['description']; ?></td>
                    <td><?php echo htmlspecialchars($task['documents_req']); ?></td>
                    <td><?php echo date('F d, Y', strtotime($task['due_date'])); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h3>Core Tasks:</h3>
    <table>
        <thead>
            <tr>
                <th>Outputs</th>
                <th>Success Indicator
                (Target + Measures)</th>
                <th>Target</th>
                <th>Due date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($core_tasks as $task): ?>
                <tr>
                    <td><?php echo $task['task_name']; ?></td>
                    <td><?php echo $task['description']; ?></td>
                    <td><?php echo htmlspecialchars($task['documents_req']); ?></td>
                    <td><?php echo date('F d, Y', strtotime($task['due_date'])); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h3>Support Tasks:</h3>
    <table>
        <thead>
            <tr>
                <th>Outputs</th>
                <th>Success Indicator
                (Target + Measures)</th>
                <th>Target</th>
                <th>Due date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($support_tasks as $task): ?>
                <tr>
                    <td><?php echo $task['task_name']; ?></td>
                    <td><?php echo $task['description']; ?></td>
                    <td><?php echo htmlspecialchars($task['documents_req']); ?></td>
                    <td><?php echo date('F d, Y', strtotime($task['due_date'])); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>    

</body>
</html>

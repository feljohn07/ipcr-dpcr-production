<?php
session_start();
include '../../../dbconnections/config.php'; // Updated relative path

// Retrieve semester_id from URL parameter
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

// Fetch core tasks
$core_stmt = $conn->prepare("SELECT * FROM core_tasks WHERE semester_id = ?");
$core_stmt->bind_param("i", $semester_id);
$core_stmt->execute();
$core_result = $core_stmt->get_result();
$core_tasks = $core_result->fetch_all(MYSQLI_ASSOC);
$core_stmt->close();

// Fetch support tasks
$support_stmt = $conn->prepare("SELECT * FROM support_tasks WHERE semester_id = ?");
$support_stmt->bind_param("i", $semester_id);
$support_stmt->execute();
$support_result = $support_stmt->get_result();
$support_tasks = $support_result->fetch_all(MYSQLI_ASSOC);
$support_stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Tasks for Semester <?php echo $semester_id; ?></title>
    <style>
/* styles.css */

/* General styles for the page */
body {
    font-family: Arial, sans-serif;
    line-height: 1.6;
    color: #333;
    margin: 0;
    padding: 0;
}

.header {
    background-color: #f4f4f4;
    padding: 20px;
    border-bottom: 2px solid #ddd;
}

.header h2 {
    margin: 0;
    font-size: 24px;
    color: #333;
}

.header p {
    font-size: 16px;
    color: #555;
}

/* Table styling */
.tabledata {
    padding: 20px;
}

.tabledata h3 {
    font-size: 20px;
    color: #333;
    margin-bottom: 10px;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

table th, table td {
    border: 1px solid #ddd;
    padding: 10px;
    text-align: left;
}

table th {
    background-color: #f4f4f4;
    color: #333;
}

table tr:nth-child(even) {
    background-color: #f9f9f9;
}

table tr:hover {
    background-color: #f1f1f1;
}

/* Responsive design */
@media (max-width: 768px) {
    table {
        width: 100%;
        display: block;
        overflow-x: auto;
    }

    thead {
        display: none;
    }

    tr {
        display: block;
        margin-bottom: 10px;
    }

    td {
        display: block;
        text-align: right;
        position: relative;
        padding-left: 50%;
        white-space: nowrap;
        border: 1px solid #ddd;
    }

    td::before {
        content: attr(data-label);
        position: absolute;
        left: 0;
        width: 50%;
        padding-left: 10px;
        font-weight: bold;
        white-space: nowrap;
        background: #f4f4f4;
    }
}



    </style>
</head>
<body>
    <div class="header">
    <h2>Semester Tasks : <?php echo htmlspecialchars($semester['semester_name']); ?></h2>

    <p><strong>Start Date:</strong> <?php echo date('F/d/Y', strtotime($semester['start_date'])); ?></p>
    <p><strong>End Date:</strong> <?php echo date('F/d/Y', strtotime($semester['end_date'])); ?></p>


    </div>

    <div class="tabledata">
    <h3>Strategic Tasks:</h3>
    <table>
    <thead>
        <tr>
            <th>Task Name</th>
            <th>Description</th>
            <th>Documents Required</th>
            <th>Owner</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($strategic_tasks as $task): ?>
            <tr>
                <td data-label="Task Name"><?php echo htmlspecialchars($task['task_name']); ?></td>
                <td data-label="Description"><?php echo htmlspecialchars($task['description']); ?></td>
                <td data-label="Documents Required"><?php echo htmlspecialchars($task['documents_req']); ?></td>
                <td data-label="Owner"><?php echo htmlspecialchars($task['owner']); ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>


    <h3>Core Tasks:</h3>
    <table>
        <thead>
            <tr>
                <th>Task Name</th>
                <th>Description</th>
                <th>Documents Required</th>
                <th>Owner</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($core_tasks as $task): ?>
                <tr>
                    <td><?php echo htmlspecialchars($task['task_name']); ?></td>
                    <td><?php echo htmlspecialchars($task['description']); ?></td>
                    <td><?php echo htmlspecialchars($task['documents_req']); ?></td>
                    <td><?php echo htmlspecialchars($task['owner']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h3>Support Tasks:</h3>
    <table>
        <thead>
            <tr>
                <th>Task Name</th>
                <th>Description</th>
                <th>Documents Required</th>
                <th>Owner</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($support_tasks as $task): ?>
                <tr>
                    <td><?php echo htmlspecialchars($task['task_name']); ?></td>
                    <td><?php echo htmlspecialchars($task['description']); ?></td>
                    <td><?php echo htmlspecialchars($task['documents_req']); ?></td>
                    <td><?php echo htmlspecialchars($task['owner']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>    
    </div>
</body>
</html>

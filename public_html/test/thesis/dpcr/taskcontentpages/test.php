<?php
session_start();
include '../../dbconnections/db04_task.php'; // Updated relative path

// Retrieve semester_id from URL parameter
$semester_id = $_GET['semester_id'];

// Function to update overall document counts
function updateSemesterDocuments($semester_id) {
    global $conn;

    // Calculate total required documents
    $query = "
        SELECT 
            COALESCE(SUM(documents_req), 0) AS total_required
        FROM (
            SELECT documents_req FROM strategic_tasks WHERE semester_id = ?
            UNION ALL
            SELECT documents_req FROM core_tasks WHERE semester_id = ?
            UNION ALL
            SELECT documents_req FROM support_tasks WHERE semester_id = ?
        ) AS all_tasks
    ";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("iii", $semester_id, $semester_id, $semester_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $total_required = $result['total_required'];
    $stmt->close();

    // Calculate total uploaded documents
    $query = "
        SELECT 
            COALESCE(SUM(documents_uploaded), 0) AS total_uploaded
        FROM (
            SELECT documents_uploaded FROM strategic_tasks WHERE semester_id = ?
            UNION ALL
            SELECT documents_uploaded FROM core_tasks WHERE semester_id = ?
            UNION ALL
            SELECT documents_uploaded FROM support_tasks WHERE semester_id = ?
        ) AS all_tasks
    ";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("iii", $semester_id, $semester_id, $semester_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $total_uploaded = $result['total_uploaded'];
    $stmt->close();

    return [
        'total_required' => $total_required,
        'total_uploaded' => $total_uploaded
    ];
}

$totals = updateSemesterDocuments($semester_id);

// Calculate progress percentage
$progress_percentage = ($totals['total_required'] > 0) ? ($totals['total_uploaded'] / $totals['total_required']) * 100 : 0;

// Fetch semester details
$semester_stmt = $conn->prepare("SELECT * FROM semester_tasks WHERE semester_id = ?");
if (!$semester_stmt) {
    die("Prepare failed: " . $conn->error);
}
$semester_stmt->bind_param("i", $semester_id);
$semester_stmt->execute();
$semester_result = $semester_stmt->get_result();
$semester = $semester_result->fetch_assoc();
$semester_stmt->close();

// Function to fetch tasks with aggregated owner details and file attachments
function fetchTasksWithDetails($task_table, $task_type, $semester_id) {
    global $conn;

    $query = "
        SELECT 
            t.task_id,
            t.task_name, 
            t.description, 
            t.documents_req, 
            t.documents_uploaded, 
            GROUP_CONCAT(CONCAT(u.lastname, ' ', u.firstname) SEPARATOR ', ') AS owner,
            GROUP_CONCAT(
                CONCAT(
                    '<a href=\"data:', a.file_type, ';base64,', 
                    TO_BASE64(a.file_content), 
                    '\" download=\"', a.file_name, '\">', 
                    a.file_name, 
                    '</a>'
                ) SEPARATOR '<br>'
            ) AS files
        FROM $task_table t
        LEFT JOIN task_assignments ta 
            ON t.task_id = ta.idoftask 
            AND ta.task_type = ? 
            AND ta.status = 'approved'
        LEFT JOIN 01_users.usersinfo u
            ON ta.assignuser = u.idnumber
        LEFT JOIN task_attachments a
            ON t.task_id = a.id_of_task 
            AND a.task_type = ?
        WHERE t.semester_id = ?
        GROUP BY t.task_id, t.task_name, t.description, t.documents_req, t.documents_uploaded
    ";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("ssi", $task_type, $task_type, $semester_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $tasks = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $tasks;
}

$strategic_tasks = fetchTasksWithDetails('strategic_tasks', 'strategic', $semester_id);
$core_tasks = fetchTasksWithDetails('core_tasks', 'core', $semester_id);
$support_tasks = fetchTasksWithDetails('support_tasks', 'support', $semester_id);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Tasks for Semester <?php echo htmlspecialchars($semester_id); ?></title>
    <style>
        /* General styles for the page */
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .header {
            text-align: center;
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
            text-align: center;
            background-color: #f4f4f4;
            color: #333;
        }

        table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        table tr:hover {
            background-color: #f1f1f1;
        }

        .progress-bar-container {
            width: 100%;
            background-color: #f3f3f3;
            border-radius: 5px;
            overflow: hidden;
            margin: 5px 0;
        }

        .progress-bar {
            height: 20px;
            background-color: #4caf50;
            text-align: center;
            color: white;
            line-height: 20px;
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

        /* Vertical owner names styling */
        .owner-cell {
            line-height: 1.5;
            padding: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Semester Tasks: <?php echo htmlspecialchars($semester['semester_name']); ?></h2>
        <p><strong>Start Date:</strong> <?php echo date('F d, Y', strtotime($semester['start_date'])); ?></p>
        <p><strong>End Date:</strong> <?php echo date('F d, Y', strtotime($semester['end_date'])); ?></p>
        <p><strong>Progress:</strong>
            <div class="progress-bar-container">
                <div class="progress-bar" style="width: <?php echo htmlspecialchars($progress_percentage); ?>%;">
                    <?php echo htmlspecialchars(round($progress_percentage, 2)); ?>%
                </div>
            </div>
        </p>
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
                    <th>Progress</th>
                    <th>File Attached</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($strategic_tasks as $task): 
                    $progress = ($task['documents_req'] > 0) ? ($task['documents_uploaded'] / $task['documents_req']) * 100 : 0;
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($task['task_name']); ?></td>
                    <td><?php echo htmlspecialchars($task['description']); ?></td>
                    <td><?php echo htmlspecialchars($task['documents_req']); ?></td>
                    <td class="owner-cell"><?php echo htmlspecialchars($task['owner']); ?></td>
                    <td><?php echo htmlspecialchars(round($progress, 2)); ?>%</td>
                    <td><?php echo $task['files'] ? $task['files'] : 'No files attached'; ?></td>
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
                    <th>Progress</th>
                    <th>File Attached</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($core_tasks as $task): 
                    $progress = ($task['documents_req'] > 0) ? ($task['documents_uploaded'] / $task['documents_req']) * 100 : 0;
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($task['task_name']); ?></td>
                    <td><?php echo htmlspecialchars($task['description']); ?></td>
                    <td><?php echo htmlspecialchars($task['documents_req']); ?></td>
                    <td class="owner-cell"><?php echo htmlspecialchars($task['owner']); ?></td>
                    <td><?php echo htmlspecialchars(round($progress, 2)); ?>%</td>
                    <td><?php echo $task['files'] ? $task['files'] : 'No files attached'; ?></td>
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
                    <th>Progress</th>
                    <th>File Attached</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($support_tasks as $task): 
                    $progress = ($task['documents_req'] > 0) ? ($task['documents_uploaded'] / $task['documents_req']) * 100 : 0;
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($task['task_name']); ?></td>
                    <td><?php echo htmlspecialchars($task['description']); ?></td>
                    <td><?php echo htmlspecialchars($task['documents_req']); ?></td>
                    <td class="owner-cell"><?php echo htmlspecialchars($task['owner']); ?></td>
                    <td><?php echo htmlspecialchars(round($progress, 2)); ?>%</td>
                    <td><?php echo $task['files'] ? $task['files'] : 'No files attached'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

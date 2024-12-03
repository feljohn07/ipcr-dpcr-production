<?php
session_start();
include '../../dbconnections/config.php'; // Database connection

// Retrieve logged-in user's idnumber
$current_user_idnumber = $_SESSION['idnumber'];

// Fetch approved tasks grouped by semester
$approved_stmt = $conn->prepare("
    SELECT 
        ta.id,
        ta.task_type,
        ta.semester_id,
        CASE 
            WHEN ta.task_type = 'strategic' THEN st.task_name
            WHEN ta.task_type = 'core' THEN ct.task_name
            WHEN ta.task_type = 'support' THEN supt.task_name
        END AS task_name,
        CASE 
            WHEN ta.task_type = 'strategic' THEN st.description
            WHEN ta.task_type = 'core' THEN ct.description
            WHEN ta.task_type = 'support' THEN supt.description
        END AS description,
        CASE 
            WHEN ta.task_type = 'strategic' THEN st.documents_req
            WHEN ta.task_type = 'core' THEN ct.documents_req
            WHEN ta.task_type = 'support' THEN supt.documents_req
        END AS documents_req,
        CASE 
            WHEN ta.task_type = 'strategic' THEN st.documents_uploaded
            WHEN ta.task_type = 'core' THEN ct.documents_uploaded
            WHEN ta.task_type = 'support' THEN supt.documents_uploaded
        END AS documents_uploaded,
        ta.status,
        ta.message,
        ta.created_at,
        CASE 
            WHEN ta.task_type = 'strategic' THEN st.limitdate
            WHEN ta.task_type = 'core' THEN ct.limitdate
            WHEN ta.task_type = 'support' THEN supt.limitdate
        END AS limitdate
    FROM 
        task_assignments ta
    LEFT JOIN 
        strategic_tasks st ON ta.idoftask = st.task_id AND ta.task_type = 'strategic'
    LEFT JOIN 
        core_tasks ct ON ta.idoftask = ct.task_id AND ta.task_type = 'core'
    LEFT JOIN 
        support_tasks supt ON ta.idoftask = supt.task_id AND ta.task_type = 'support'
    WHERE 
        ta.assignuser = ? AND ta.status = 'approved'  -- Fetch only approved tasks
    ORDER BY 
        ta.semester_id, ta.created_at DESC
");
$approved_stmt->bind_param("s", $current_user_idnumber);
$approved_stmt->execute();
$approved_result = $approved_stmt->get_result();
$approved_tasks = [];
while ($row = $approved_result->fetch_assoc()) {
    $approved_tasks[$row['semester_id']][] = $row;
}
$approved_stmt->close();

// Fetch already uploaded files for each task
$uploaded_files = [];
foreach ($approved_tasks as $semester_tasks) {
    foreach ($semester_tasks as $task) {
        $task_id = $task['id'];
        $file_stmt = $conn->prepare("SELECT file_name, file_type FROM task_attachments WHERE task_id = ?");
        $file_stmt->bind_param("i", $task_id);
        $file_stmt->execute();
        $file_result = $file_stmt->get_result();
        $uploaded_files[$task_id] = $file_result->fetch_all(MYSQLI_ASSOC);
        $file_stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approved Tasks</title>
    <style>
        /* Basic reset for consistency */
        body, h2, table, th, td, form, input, button {
            margin: 0;
            padding: 0;
            border: 0;
            font-family: Arial, sans-serif;
        }

        /* Container for the header */
        .head {
            text-align: center;
            margin: 20px;
        }

        .head h2 {
            font-size: 24px;
            color: #333;
        }

        /* Table styling */
        .tabledata {
            width: 100%;
            margin: 20px 0;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background-color: #f4f4f4;
        }

        th, td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: center;
        }

        th {
            font-size: 11px;
            background-color:#eceff1;;
            color: #333;
            font-weight: bold; /* Makes text bold */
        }

        td {
            font-size: 14px;
            text-align: left;
        }

        /* Styling for table rows */
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        /* File upload section */
        .file-upload {
            margin-top: 10px;
        }

        .file-upload input[type="file"] {
            margin-bottom: 5px;
        }

        .file-upload button {
            background-color: #4caf50;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 5px;
        }

        .file-upload button:hover {
            background-color: #45a049;
        }

        .uploaded-files h4 {
            margin-top: 10px;
            font-size: 18px;
        }

        .uploaded-files ul {
            list-style-type: none;
            padding: 0;
        }

        .uploaded-files li {
            margin-bottom: 5px;
        }

        .uploaded-files a {
            color: #007bff;
            text-decoration: none;
        }

        .uploaded-files a:hover {
            text-decoration: underline;
        }

        .uploaded-files button {
            background-color: #f44336;
            color: white;
            border: none;
            padding: 5px;
            border-radius: 5px;
            cursor: pointer;
            margin-left: 10px;
        }

        .uploaded-files button:hover {
            background-color: #d32f2f;
        }

        /* Progress bar styling */
        .progress-bar {
            width: 100%;
            background-color: #f3f3f3;
            border-radius: 5px;
            overflow: hidden;
            margin-top: 5px;
        }

        .progress-bar-fill {
            height: 20px;
            background-color: #4caf50;
            text-align: center;
            color: white;
            line-height: 20px;
            border-radius: 5px;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            th, td {
                font-size: 12px;
                padding: 8px;
            }

            .file-upload button {
                padding: 8px;
            }

            .uploaded-files h4 {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="head">
        <h2>Approved Tasks</h2>
    </div>
    <div class="tabledata">
        <?php foreach ($approved_tasks as $semester_id => $tasks): ?>
            <h3>Semester ID: <?php echo htmlspecialchars($semester_id); ?></h3>
            <table>
                <thead>
                    <tr>
                        <th>Task Type</th>
                        <th>Task Name</th>
                        <th>Description</th>
                        <th>Documents Required</th>
                        <th>Documents Uploaded</th>
                        <th>Assigned At</th>
                        <th>Deadline</th>
                        <th>Progress</th>
                        <th>Attach Files</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tasks as $task): ?>
                        <?php
                            $task_id = $task['id'];
                            $documents_req = $task['documents_req'];
                            $documents_uploaded = $task['documents_uploaded'];
                            $uploaded_count = isset($uploaded_files[$task_id]) ? count($uploaded_files[$task_id]) : 0;
                            $progress = $documents_req > 0 ? round(($documents_uploaded / $documents_req) * 100) : 0;
                            $remaining_count = max($documents_req - $uploaded_count, 0);
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($task['task_type']); ?></td>
                            <td><?php echo htmlspecialchars($task['task_name']); ?></td>
                            <td><?php echo htmlspecialchars($task['description']); ?></td>
                            <td><?php echo htmlspecialchars($documents_req); ?></td>
                            <td><?php echo htmlspecialchars($documents_uploaded); ?></td>
                            <td><?php echo date('F d, Y', strtotime($task['created_at'])); ?></td>
                            <td><?php echo date('F d, Y', strtotime($task['limitdate'])); ?></td>
                            <td style="width: 200px;">
                                <div class="progress-bar">
                                    <div class="progress-bar-fill" style="width: <?php echo $progress; ?>%;">
                                        <?php echo $progress; ?>%
                                    </div>
                                </div>
                                <div>Remaining: <?php echo $remaining_count; ?></div>
                            </td>
                        <td>
                            <div class="file-upload">
                                <form id="upload-doc" enctype="multipart/form-data">
                                    <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                    <input type="hidden" name="task_type" value="<?php echo $task['task_type']; ?>">
                                    <input type="hidden" name="semester_id" value="<?php echo $task['semester_id']; ?>">
                                    <?php for ($i = 0; $i < $remaining_count; $i++): ?>
                                        <input type="file" name="file[]" accept=".doc, .docx, .ppt, .pptx, .xls, .xlsx, .pdf, .jpg, .jpeg, .png, .mp4, .avi, .mov, .mkv">
                                    <?php endfor; ?>
                                    <button type="submit">Upload</button>
                                </form>
                                <div class="uploaded-files">
                                    <h4>Uploaded Files:</h4>
                                    <?php if (isset($uploaded_files[$task['id']]) && count($uploaded_files[$task['id']]) > 0): ?>
                                        <ul>
                                            <?php foreach ($uploaded_files[$task['id']] as $file): ?>
                                                <li>
                                                    <a href="ipcrtaskspages/view_file.php?id=<?php echo $task_id; ?>&file_name=<?php echo urlencode($file['file_name']); ?>" target="_blank">
                                                        <?php echo htmlspecialchars($file['file_name']); ?>
                                                    </a>
                                                    <form id="delete-doc" method="POST" style="display:inline;">
                                                        <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                                        <input type="hidden" name="file_name" value="<?php echo htmlspecialchars($file['file_name']); ?>">
                                                        <input type="hidden" name="task_type" value="<?php echo $task['task_type']; ?>">
                                                        <input type="hidden" name="semester_id" value="<?php echo $task['semester_id']; ?>">
                                                        <button type="submit">Delete</button>
                                                    </form>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <p>No files uploaded.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <!-- js/formhandler sudmition using id -->
    <?php endforeach; ?>
</body>
</html>

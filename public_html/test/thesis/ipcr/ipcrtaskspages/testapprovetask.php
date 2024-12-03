<?php
    session_start();
    include '../../dbconnections/config.php'; // Database connection

    // Retrieve logged-in user's idnumber
    $current_user_idnumber = $_SESSION['idnumber'];

    // Fetch approved tasks
    $approved_stmt = $conn->prepare("
    SELECT 
        ta.id,
        ta.task_type,
        ta.progress,  -- Ensure this column is selected
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
        ta.assignuser = ? AND ta.status = 'approved'
");

    $approved_stmt->bind_param("s", $current_user_idnumber);
    $approved_stmt->execute();
    $approved_result = $approved_stmt->get_result();
    $approved_tasks = $approved_result->fetch_all(MYSQLI_ASSOC);
    $approved_stmt->close();

    // Fetch already uploaded files for each task
    $uploaded_files = [];
    foreach ($approved_tasks as $task) {
        $task_id = $task['id'];
        $file_stmt = $conn->prepare("SELECT file_name, file_type FROM task_attachments WHERE task_id = ?");
        $file_stmt->bind_param("i", $task_id);
        $file_stmt->execute();
        $file_result = $file_stmt->get_result();
        $uploaded_files[$task_id] = $file_result->fetch_all(MYSQLI_ASSOC);
        $file_stmt->close();
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
                border: 1px solid #ddd;
            }

            thead {
                background-color: #f4f4f4;
            }

            th, td {
                padding: 12px;
                text-align: left;
                border-bottom: 1px solid #ddd;
            }

            th {
                background-color: #f2f2f2;
                color: #333;
            }

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

        </style>
    </head>
    <body>
        <div class="head">
            <h2>Approved Tasks</h2>
        </div>
        <div class="tabledata">
            <table>
                <thead>
                    <tr>
                        <th>Task Type</th>
                        <th>Task Name</th>
                        <th>Description</th>
                        <th>Documents Required</th>
                        <th>Status</th>
                        <th>Assigned At</th>
                        <th>Deadline</th>
                        <th>Progress</th>
                        <th>Attach Files</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($approved_tasks as $task): ?>
                        <?php
                            $task_id = $task['id'];
                            $documents_req = $task['documents_req'];
                            $uploaded_count = isset($uploaded_files[$task_id]) ? count($uploaded_files[$task_id]) : 0;
                            // Calculate progress
                            $progress = isset($task['progress']) ? number_format($task['progress'], 2) : '0.00';
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($task['task_type']); ?></td>
                            <td><?php echo htmlspecialchars($task['task_name']); ?></td>
                            <td><?php echo htmlspecialchars($task['description']); ?></td>
                            <td><?php echo htmlspecialchars($task['documents_req']); ?></td>
                            <td><?php echo htmlspecialchars($task['status']); ?></td>
                            <td><?php echo date('F d, Y', strtotime($task['created_at'])); ?></td>
                            <td><?php echo date('F d, Y', strtotime($task['limitdate'])); ?></td>
                            <td><?php echo number_format($progress, 2); ?>%</td>
                            <td>
                                <div class="file-upload">
                                    <form action="../ipcrtaskspages/upload_documents.php" method="POST" enctype="multipart/form-data">
                                        <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                        <input type="hidden" name="task_type" value="<?php echo $task['task_type']; ?>">
                                        <?php for ($i = 0; $i < $documents_req - $uploaded_count; $i++): ?>
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
                                                        <a href="view_file.php?id=<?php echo $task_id; ?>&file_name=<?php echo urlencode($file['file_name']); ?>" target="_blank">
                                                            <?php echo htmlspecialchars($file['file_name']); ?>
                                                        </a>
                                                        <form action="../ipcrtaskspages/delete_file.php" method="POST" style="display:inline;">
                                                        <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                                        <input type="hidden" name="file_name" value="<?php echo htmlspecialchars($file['file_name']); ?>">
                                                        <button type="submit" onclick="return confirm('Are you sure you want to delete this file?');">Delete</button>
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
    </body>
    </html>

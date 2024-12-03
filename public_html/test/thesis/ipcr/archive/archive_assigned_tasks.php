<?php
session_start();
include '../../dbconnections/config.php'; // Database connection

// Retrieve semester_id from POST data
$semester_id = $_POST['semester_id'] ?? null; // Use null coalescing to avoid undefined index notice

if ($semester_id === null) {
    die("Error: Semester ID not provided.");
}

// Get the user's ID number from the session
$idnumber = $_SESSION['idnumber'] ?? null; // Make sure to retrieve the idnumber

if ($idnumber === null) {
    die("Error: User ID not found in session.");
}

// Fetch approved tasks grouped by semester for the specific user
$approved_stmt = $conn->prepare("
SELECT 
    ta.id,
    ta.task_type,
    ta.newtask_type,
    ta.semester_id,
    st.semester_name,
    st.start_date,
    st.end_date,
    CASE 
        WHEN ta.task_type = 'strategic' THEN stask.task_name
        WHEN ta.task_type = 'core' THEN ctask.task_name
        WHEN ta.task_type = 'support' THEN sptask.task_name
    END AS task_name,
    CASE 
        WHEN ta.task_type = 'strategic' THEN stask.description
        WHEN ta.task_type = 'core' THEN ctask.description
        WHEN ta.task_type = 'support' THEN sptask.description
    END AS description,
    ta.target,
    ta.num_file,  
    ta.status,
    ta.message,
    ta.deansmessage  -- Fetching deansmessage from task_assignments
FROM 
    task_assignments ta
LEFT JOIN 
    strategic_tasks stask ON ta.idoftask = stask.task_id AND ta.task_type = 'strategic'
LEFT JOIN 
    core_tasks ctask ON ta.idoftask = ctask.task_id AND ta.task_type = 'core'
LEFT JOIN 
    support_tasks sptask ON ta.idoftask = sptask.task_id AND ta.task_type = 'support'
LEFT JOIN
    semester_tasks st ON ta.semester_id = st.semester_id
WHERE 
    ta.semester_id = ? AND ta.assignuser = ?  -- Filter by semester_id and assignuser
ORDER BY 
    ta.semester_id, ta.task_type DESC
");

// Bind parameters and execute the statement
$approved_stmt->bind_param("ss", $semester_id, $idnumber); // Bind semester_id and idnumber
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

// Check conditions to hide the "Strategic" option
$designation = $_SESSION['designation'] ?? 'None'; // Example designation from session
$position = $_SESSION['position'] ?? ''; // Example position from session
$hideStrategic = false;

if ($designation === 'None' && (preg_match('/^instructor-[1-3]$/', $position) || preg_match('/^assistant-professor-[1-4]$/', $position))) {
    $hideStrategic = true; // Set the flag to hide the "Strategic" option
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

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
        font-size: 15px;
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
        padding: 8px; /* Reduced padding for shorter rows */
        border: 1px solid #ddd;
        text-align: center;
    }

    th {
        font-size: 13px;
        background-color: #eceff1;
        color: #333;
        font-weight: bold; /* Makes text bold */
    }

    td {
        font-size: 13px;
        text-align: left;
    }

    /* Styling for table rows */
    tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    tr:hover {
        background-color: #f1f1f1;
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
        display: flex;
        align-items: center;
    }

    .uploaded-files a {
        color: #007bff;
        text-decoration: none;
        margin-right: 10px;
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
    .circle {
        position: relative;
        width: 60px; /* Adjusted width */
        height: 60px; /* Adjusted height */
        margin: auto; /* Center the circle within the td */
    }

    svg {
        transform: rotate(-90deg);
    }

    .circle-bg {
        fill: none;
        stroke: #e6e6e6;
        stroke-width: 6; /* Adjusted stroke width */
    }

    .circle-progress {
        fill: none;
        stroke: #4caf50; /* Change this color as needed */
        stroke-width: 6; /* Adjusted stroke width */
        stroke-linecap: round;
        transition: stroke-dasharray 0.5s ease;
    }

    .percentage {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        font-size: 14px; /* Adjusted font size */
        font-weight: bold;
        color: #333;
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

    /* Semester information styling */
    .semester-info {
        margin: 20px 0;
        padding: 15px;
        background-color: #f9f9f9;
        border-radius: 8px;
        border: 1px solid #ddd;
    }

    .semester-info h3 {
        font-size: 17px;
        color: #333;
        margin-bottom: 10px;
    }

    .semester-info p {
        font-size: 14px;
        color: #555;
        margin: 5px 0;
    }

    .semester-info p span {
        font-weight: bold;
        color: #333;
    }

    /* Spinner style */
.button-loading {
    pointer-events: none; /* Disable the button while loading */
    opacity: 0.6; /* Optional: Make the button look disabled */
    position: relative;
}

.button-loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 16px;
    height: 16px;
    margin: -8px;
    border: 2px solid white;
    border-top-color: transparent;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    100% {
        transform: rotate(360deg);
    }
}

    </style>

<script>
        function closeTab() {
            window.close(); // Attempt to close the current tab
        }
    </script>
</head>
<body>
<button class="close-btn" onclick="closeTab()" style="position: fixed; top: 10px; right: 10px; z-index: 1000; background-color: #ff4d4d; color: white; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer;">
    Close This Tab
</button>
        <div class="head">
            <h2>Archive Assigned Tasks</h2>
        </div>
        <div class="tabledata">
        <?php foreach ($approved_tasks as $semester_id => $tasks): ?>
            <div class="semester-info">
        <h3>Semester: <?php echo htmlspecialchars($tasks[0]['semester_name']); ?></h3>
        <p><span>Start Date:</span> <?php echo htmlspecialchars(date('F d, Y', strtotime($tasks[0]['start_date']))); ?></p>
        <p><span>End Date:</span> <?php echo htmlspecialchars(date('F d, Y', strtotime($tasks[0]['end_date']))); ?></p>
            <table>
                    <tr>
                        <th>Area of Evaluation</th> <!-- New column header -->
                        <th>Task Name</th>
                        <th>Description</th>
                        <th>Documents Required</th>
                        <th>Documents Uploaded</th>
                        <th>Progress</th>
                        <th>Message</th>
                        <th>Attach Files</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tasks as $task): ?>
                        <?php
                            $task_id = $task['id'];
                            $documents_req = $task['target'];
                            $documents_uploaded = $task['num_file'];  // Change to num_file here
                            $uploaded_count = isset($uploaded_files[$task_id]) ? count($uploaded_files[$task_id]) : 0;
                            $progress = $documents_req > 0 ? round(($documents_uploaded / $documents_req) * 100) : 0;
                            $remaining_count = max($documents_req - $uploaded_count, 0);
                        ?>
                        <tr>
                        <td style="width: 100px; font-size: 10px;">
                                <form class="update-tasktype-form" id="update-task-form-<?php echo $task['id']; ?>">
                                    <select name="task_type" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; background-color: #fff; font-size: 13px; color: #333; appearance: none;" disabled>
                                        <option value="" disabled <?php echo empty($task['newtask_type']) ? 'selected' : ''; ?>>Select Task Type</option>
                                        <?php if (!$hideStrategic) { ?>
                                            <option value="strategic" <?php echo $task['newtask_type'] === 'strategic' ? 'selected' : ''; ?>>Strategic</option>
                                        <?php } ?>
                                        <option value="core" <?php echo $task['newtask_type'] === 'core' ? 'selected' : ''; ?>>Core</option>
                                        <option value="support" <?php echo $task['newtask_type'] === 'support' ? 'selected' : ''; ?>>Support</option>
                                    </select>
                                    <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                    <input type="hidden" name="semester_id" value="<?php echo $task['semester_id']; ?>">
                                    <button type="button" class="edit-button" style="margin-top: 10px; padding: 8px 12px; background-color: #4caf50; color: white; border: none; border-radius: 5px; cursor: pointer;">Edit</button>
                                    <button type="submit" class="save-button" style="margin-top: 10px; padding: 8px 12px; background-color: #4caf50; color: white; border: none; border-radius: 5px; cursor: pointer; display: none;">Save</button>
                                </form>
                            </td>
                            <td>
                            <?php 
                                    // First remove any escape characters using stripslashes()
                                    $task_name = stripslashes($task['task_name']);
                                        
                                    // Replace <breakline> with <br> after removing slashes
                                    $task_name = str_replace('<break(+)line>', '<br>', $task_name);
                                        
                                    // Display the task name after replacing <breakline> with <br>
                                    echo $task_name; 
                                ?>
                               
                            </td>
                            <td>
                                <?php 
                                        // First remove any escape characters using stripslashes()
                                        $description = stripslashes($task['description']);
                                            
                                        // Replace <breakline> with <br> after removing slashes
                                        $description = str_replace('<break(+)line>', '<br>', $description);
                                            
                                        // Display the description after replacing <breakline> with <br>
                                        echo $description; 
                                    ?>
                            </td>
                            <td><?php echo htmlspecialchars($documents_req); ?></td>
                            <td><?php echo htmlspecialchars($documents_uploaded); ?></td>
                            <td style="width: 90px; height: 200px; vertical-align: middle;">
                                <div class="circle">
                                    <svg width="60" height="60"> <!-- Further reduced size -->
                                        <circle class="circle-bg" cx="30" cy="30" r="25"></circle> <!-- Adjusted radius -->
                                        <circle class="circle-progress" cx="30" cy="30" r="25" style="stroke-dasharray: <?php echo ($progress / 100) * (2 * pi() * 25); ?>, 157.08;"></circle> <!-- Adjusted radius -->
                                    </svg>
                                    <div class="percentage"><?php echo $progress; ?>%</div>
                                </div>
                            </td>
                            <td style="text-align: center;">
                                <?php if (!empty($task['deansmessage'])): ?>
                                    <button type="button" 
                                            title="Send Message" 
                                            aria-label="Send Message" 
                                            style="background: none; border: none; cursor: pointer;" 
                                            data-deansmessage="<?php echo htmlspecialchars($task['deansmessage']); ?>" 
                                            onclick="showDeansMessage(this)">
                                        <i class="fas fa-envelope" style="font-size: 24px; color: #007bff;"></i> <!-- Envelope Icon -->
                                    </button>
                                <?php endif; ?>
                            </td>
                            <td style="width: 20px;">
                                <div class="file-upload">
                                    <?php if (empty($task['newtask_type'])): ?>
                                        <p>Please set the Area of Evaluation to access the file upload section.</p>
                                    <?php else: ?>
                                        <?php if ($documents_uploaded < $documents_req): // Only show the file upload form if not all documents are uploaded ?>
                                        <?php else: ?>
                                            <p>All required documents have been uploaded.</p>
                                        <?php endif; ?>
                                        <div class="uploaded-files">
                                            <h4>Uploaded Files:</h4>
                                            <?php if (isset($uploaded_files[$task['id']]) && count($uploaded_files[$task['id']]) > 0): ?>
                                                <ul>
                                                    <?php foreach ($uploaded_files[$task['id']] as $file): ?>
                                                        <li>
                                                            <a href="#" onclick="openFileModal('<?php echo $task_id; ?>', '<?php echo urlencode($file['file_name']); ?>')">
                                                                <?php echo htmlspecialchars($file['file_name']); ?>
                                                            </a>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php else: ?>
                                                <p>No files uploaded.</p>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<!-- File Modal -->
<!-- File Modal -->
<div id="fileModal" class="modal">
    <style>
        .modal {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 1000; /* Sit on top */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            overflow: auto; /* Enable scroll if needed */
            background-color: rgba(0, 0, 0, 0.6); /* Dark background for modal */
        }

        .modal-content {
            width: 80%; 
            max-width: 800px; 
            margin: auto; 
            padding: 20px; 
            border-radius: 5px; 
            background-color: white; 
            position: relative; 
            top: 50px;
        }

        .close {
            position: absolute; /* Position it absolutely within the modal-content */
            right: 15px; /* Distance from the right */
            top: 1px; /* Distance from the top */
            font-size: 50px; /* Adjust size as needed */
            cursor: pointer; /* Change cursor to pointer */
        }
    </style>
    <div class="modal-content">
        <span class="close" onclick="closeFileModal()">&times;</span>
        <iframe id="fileIframe" src="" style="width: 100%; height: 500px; border: none;"></iframe>
    </div>
</div>
       <!-- Notification Container -->
   <div id="notification" style="display: none; padding: 10px; position: fixed; top: 10px; right: 10px; background-color: #28a745; color: white; z-index: 1000; border-radius: 5px;"></div>

     <!-- ipcrconfirmation-model -->
<div id="ipcrconfirmation-model" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background-color: #fff; padding: 20px; border-radius: 5px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);">
        <p style="font-size: 18px; font-weight: bold;">Confirm Deletion</p>
        <p>Click OK to confirm deletion of this file.</p>
        <button id="ipcr-ok-button" style="background-color: #4CAF50; color: #fff; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">OK</button>
        <button id="ipcr-cancel-button" style="background-color: #e74c3c; color: #fff; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">Cancel</button>
    </div>
</div>


<!-- Message-Modal Structure -->
<div id="message-modal" class="message-model" style="display: none;">
    <style>
    .message-model {
        display: none; /* Hidden by default */
        position: fixed; /* Stay in place */
        z-index: 1; /* Sit on top */
        left: 0;
        top: 0;
        width: 100%; /* Full width */
        height: 100%; /* Full height */
        overflow: auto; /* Enable scroll if needed */
        background-color: rgb(0,0,0); /* Fallback color */
        background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
    }

    .modal-content {
        background-color: #fefefe;
        margin: 15% auto; /* 15% from the top and centered */
        padding: 20px;
        border: 1px solid #888;
        width: 80%; /* Could be more or less, depending on screen size */
    }

    .close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
    }

    .close:hover,
    .close:focus {
        color: black;
        text-decoration: none;
        cursor: pointer;
    }
    </style>
    <div class="modal-content">
        <span class="close" onclick="messageCloseModel()">&times;</span>
        <h2>Dean's Message</h2>
        <p id="deans-message-text"></p>
    </div>
</div>

<script>
function showDeansMessage(button) {
    var deansMessage = button.getAttribute('data-deansmessage');
    
    if (deansMessage) {
        document.getElementById('deans-message-text').innerText = deansMessage;
        document.getElementById('message-modal').style.display = 'block'; // Show the modal
    } else {
        alert("No message from the dean.");
    }
}

function messageCloseModel() {
    document.getElementById('message-modal').style.display = 'none'; // Close the modal
}
</script>


    <?php endforeach; ?>




<script>
document.addEventListener('submit', function(event) {
    if (event.target && event.target.matches('#upload-doc')) {
        event.preventDefault(); // Prevent the default form submission

        var uploadButton = event.target.querySelector('button[type="submit"]');
        uploadButton.classList.add('button-loading'); // Add loading spinner class
        uploadButton.disabled = true; // Disable the button

        // Store the original document title
        var originalTitle = document.title;
        // Set the title to indicate loading
        document.title = "Uploading...";

        var formData = new FormData(event.target);

        var xhr = new XMLHttpRequest();
        xhr.open('POST', '../ipcrtaskspages/upload_documents.php', true);

        xhr.onload = function () {
            // Reset the title to the original after the upload completes
            document.title = originalTitle;

            uploadButton.classList.remove('button-loading'); // Remove loading spinner class
            uploadButton.disabled = false; // Re-enable the button

            if (xhr.status === 200) {
                localStorage.setItem('notificationMessage', 'Upload successfully!');
                localStorage.setItem('notificationError', 'false'); // No error
            } else {
                localStorage.setItem('notificationMessage', 'An error occurred while uploading the document.');
                localStorage.setItem('notificationError', 'true'); // Error occurred
            }
            location.reload(); // Reload the page to show the notification
        };

        xhr.send(formData);
    }
});



    // Check if the page should show the notification (similar to the approveTask function)
    var notification = document.getElementById('notification');
    if (localStorage.getItem('notificationMessage')) {
        notification.textContent = localStorage.getItem('notificationMessage');
        if (localStorage.getItem('notificationError') === 'true') {
            notification.style.backgroundColor = '#dc3545'; // Red background for error
        } else {
            notification.style.backgroundColor = '#28a745'; // Green background for success
        }
        notification.style.color = 'white';
        notification.style.display = 'block'; // Show the notification

        // Hide notification after 3 seconds
        setTimeout(function () {
            notification.style.display = 'none';
            localStorage.removeItem('notificationMessage');
            localStorage.removeItem('notificationError');
        }, 3000);
    }


    document.addEventListener('submit', function(event) {
        if (event.target && event.target.matches('#delete-doc')) {
            event.preventDefault(); // Prevent the form from submitting the default way

            // Show the confirmation modal
            var confirmationModal = document.getElementById('ipcrconfirmation-model');
            confirmationModal.style.display = 'block';

            // OK button click handler
            document.getElementById('ipcr-ok-button').onclick = function() {
                var formData = new FormData(event.target);

                var xhr = new XMLHttpRequest();
                xhr.open('POST', '../ipcrtaskspages/delete_file.php', true);

                xhr.onload = function () {
                    if (xhr.status === 200) {
                        localStorage.setItem('notificationMessage', 'Delete successfully!');
                        localStorage.setItem('notificationError', 'false'); // No error
                    } else {
                        localStorage.setItem('notificationMessage', 'An error occurred while deleting the file.');
                        localStorage.setItem('notificationError', 'true'); // Error occurred
                    }
                    location.reload(); // Reload the page to show the notification
                };

                xhr.send(formData);

                // Hide the modal after confirming
                confirmationModal.style.display = 'none';
            };

            // Cancel button click handler
            document.getElementById('ipcr-cancel-button').onclick = function() {
                // Hide the confirmation modal without doing anything
                confirmationModal.style.display = 'none';
            };
        }
    });

    // Check if the page should show the notification (similar to the approveTask function)
    var notification = document.getElementById('notification');
    if (localStorage.getItem('notificationMessage')) {
        notification.textContent = localStorage.getItem('notificationMessage');
        if (localStorage.getItem('notificationError') === 'true') {
            notification.style.backgroundColor = '#dc3545'; // Red background for error
        } else {
            notification.style.backgroundColor = '#28a745'; // Green background for success
        }
        notification.style.color = 'white';
        notification.style.display = 'block'; // Show the notification

        // Hide notification after 3 seconds
        setTimeout(function () {
            notification.style.display = 'none';
            localStorage.removeItem('notificationMessage');
            localStorage.removeItem('notificationError');
        }, 3000);
    }




    document.querySelectorAll('.update-tasktype-form').forEach(form => {
        const editButton = form.querySelector('.edit-button');
        const saveButton = form.querySelector('.save-button');
        const select = form.querySelector('select');

        editButton.addEventListener('click', function() {
            select.disabled = false; // Enable the dropdown
            editButton.style.display = 'none'; // Hide the Edit button
            saveButton.style.display = 'inline-block'; // Show the Save button
        });

        form.addEventListener('submit', function(event) {
            if (select.disabled) {
                event.preventDefault(); // Prevent form submission if the dropdown is still disabled
                alert('Please click "Edit" to enable the dropdown first.');
            }
        });
    });

    document.querySelectorAll('.update-tasktype-form').forEach(form => {
        const editButton = form.querySelector('.edit-button');
        const saveButton = form.querySelector('.save-button');
        const select = form.querySelector('select');

        editButton.addEventListener('click', function() {
            select.disabled = false; // Enable the dropdown
            editButton.style.display = 'none'; // Hide the Edit button
            saveButton.style.display = 'inline-block'; // Show the Save button
        });

        form.addEventListener('submit', function(event) {
            if (select.disabled) {
                event.preventDefault(); // Prevent form submission if the dropdown is still disabled
                alert('Please click "Edit" to enable the dropdown first.');
            }
        });
    });

    document.addEventListener('submit', function(event) {
        if (event.target && event.target.matches('.update-tasktype-form')) { // Use a class
            event.preventDefault(); // Prevent the default form submission

            var formData = new FormData(event.target); // Get form data

            var xhr = new XMLHttpRequest();
            xhr.open('POST', '../ipcrtaskspages/update_tasktype.php', true);

            xhr.onload = function () {
                if (xhr.status === 200) {
                    // Set success message in localStorage
                    localStorage.setItem('notificationMessage', 'Area of Evaluation updated successfully!');
                    localStorage.setItem('notificationError', 'false'); // No error
                } else {
                    // Set error message in localStorage
                    localStorage.setItem('notificationMessage', 'An error occurred while updating the task.');
                    localStorage.setItem('notificationError', 'true'); // Error occurred
                }
                location.reload(); // Reload the page to show the notification
            };

            xhr.send(formData); // Send the form data
        }
    });

    // Check if the page should show the notification
    var notification = document.getElementById('notification');
    if (localStorage.getItem('notificationMessage')) {
        notification.textContent = localStorage.getItem('notificationMessage');
        if (localStorage.getItem('notificationError') === 'true') {
            notification.style.backgroundColor = '#dc3545'; // Red background for error
        } else {
            notification.style.backgroundColor = '#28a745'; // Green background for success
        }
        notification.style.color = 'white';
        notification.style.display = 'block'; // Show the notification

        // Hide notification after 3 seconds
        setTimeout(function () {
            notification.style.display = 'none';
            localStorage.removeItem('notificationMessage');
            localStorage.removeItem('notificationError');
        }, 3000);
    }


        </script>
        
        <script>
            document.querySelectorAll('.update-tasktype-form').forEach(form => {
        const editButton = form.querySelector('.edit-button');
        const saveButton = form.querySelector('.save-button');
        const select = form.querySelector('select');

        editButton.addEventListener('click', function() {
            select.disabled = false; // Enable the dropdown
            editButton.style.display = 'none'; // Hide the Edit button
            saveButton.style.display = 'inline-block'; // Show the Save button
        });

        form.addEventListener('submit', function(event) {
            if (select.disabled) {
                event.preventDefault(); // Prevent form submission if the dropdown is still disabled
                alert('Please click "Edit" to enable the dropdown first.');
            }
        });
    });
</script>
        

<script>
        // Function to save the scroll position
        function saveScrollPosition() {
            const tableContainer = document.querySelector('.table-container');
            localStorage.setItem('scrollPosition', tableContainer.scrollTop);
        }

        // Function to restore the scroll position
        function restoreScrollPosition() {
            const tableContainer = document.querySelector('.table-container');
            const scrollPosition = localStorage.getItem('scrollPosition');
            if (scrollPosition) {
                tableContainer.scrollTop = scrollPosition;
            }
        }

        // Event listeners to save scroll position before unloading the page
        window.addEventListener('beforeunload', saveScrollPosition);

        // Restore scroll position when the page loads
        window.addEventListener('load', restoreScrollPosition);
</script>

<script>
    function openFileModal(taskId, fileName) {
        var url = '../ipcrtaskspages/view_file.php?id=' + encodeURIComponent(taskId) + '&file_name=' + encodeURIComponent(fileName);
        document.getElementById('fileIframe').src = url;
        document.getElementById('fileModal').style.display = 'block';
    }

    function closeFileModal() {
        document.getElementById('fileModal').style.display = 'none';
    }

    // Close the modal when user clicks anywhere outside of the modal content
    window.onclick = function(event) {
        const modal = document.getElementById("fileModal");
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }
</script>
</body>
</html>

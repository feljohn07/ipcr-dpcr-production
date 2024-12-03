<?php
session_start();
include '../../dbconnections/config.php'; // Include your database connection

// Retrieve semester_id from POST request
$semester_id = isset($_POST['semester_id']) ? $_POST['semester_id'] : null;

function fetchTasks($semester_id) {
    global $conn; // Use the global database connection

    // Assuming user ID is stored in the session
    $idnumber = $_SESSION['idnumber'];

    // Prepare the SQL query to fetch tasks for the specified semester
    // Note: This is NOT recommended due to potential SQL injection risk
    $stmt = $conn->prepare("
        SELECT st.task_id, st.group_task_id, st.task_name, st.description, 
            st.documents_required, st.documents_uploaded, st.task_type, 
            st.id_of_semester, st.name_of_semester, 
            GROUP_CONCAT(fs.file_name) AS uploaded_files,
            stt.status,
            st.quality, st.efficiency, st.timeliness, st.average
        FROM ipcrsubmittedtask st
        LEFT JOIN ipcr_file_submitted fs ON st.task_id = fs.task_id AND st.group_task_id = fs.group_task_id
        LEFT JOIN semester_tasks stt ON st.id_of_semester = stt.semester_id
        WHERE st.idnumber = ? AND st.id_of_semester = ?  -- Filter by user ID and semester_id
        GROUP BY st.group_task_id, st.task_id
        ORDER BY 
            CASE st.task_type 
                WHEN 'strategic' THEN 1 
                WHEN 'core' THEN 2 
                WHEN 'support' THEN 3 
                ELSE 4 
            END;
    ");

    // Check if the statement was prepared successfully
    if (!$stmt) {
        echo "Failed to prepare statement: " . $conn->error;
        return [];
    }

    // Bind the user's idnumber and semester_id to the query
    $stmt->bind_param("ss", $idnumber, $semester_id);

    // Execute the query
    $stmt->execute();

    // Get the result
    $result = $stmt->get_result();

    // Create an array to store the fetched tasks
    $tasks = [];

    // Check if tasks exist
    if ($result->num_rows > 0) {
        // Fetch all tasks and store them in the array
        while ($row = $result->fetch_assoc()) {
            // Escape single quotes in task_name and description
            $row['task_name'] = addslashes($row['task_name']);
            $row['description'] = addslashes($row['description']);
            
            // Group tasks by group_task_id
            $tasks[$row['group_task_id']][] = $row; 
        }
    }

    // Close the statement
    $stmt->close();

    // Return the tasks array
    return $tasks;
}

// Fetch the tasks based on the semester_id
$tasks = fetchTasks($semester_id);

// Check conditions to hide the "Strategic" option
$designation = $_SESSION['designation'] ?? 'None'; // Example designation from session
$position = $_SESSION['position'] ?? ''; // Example position from session
$hideStrategic = false;

if ($designation === 'None' && (preg_match('/^instructor-[1-3]$/', $position) || preg_match('/^assistant-professor-[1-4]$/', $position))) {
    $hideStrategic = true; // Set the flag to hide the "Strategic" option
}

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Created Tasks</title>
<style>
        .container {
                width: 90%; /* Adjusted for better responsiveness */
                max-width: 100%;
                margin: 0 auto;
                padding: 20px;
                font-family: Arial, sans-serif;
                background-color: #f9f9f9;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            }

            /* Table styling */
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
                border-radius: 8px;
                overflow: hidden;
            }

            /* Table borders */
            table, th, td {
                border: 1px solid #ddd;
            }

            /* Table cell padding */
            th, td {
                padding: 12px;
                text-align: left;
            }

            /* Table header styling */
            th {
                background-color: #4CAF50;
                color: white;
                text-align: center; 
            }

            /* Alternate row coloring */
            tr:nth-child(even) {
                background-color: #f2f2f2;
            }

            /* Table hover effect */
            tr:hover {
                background-color: #e0e0e0; /* Slightly darker on hover */
            }

            /* Heading */
            h3 {
                color: #333;
                font-size: 24px;
                text-align: center;
                margin-bottom: 20px;
            }

            /* Message styling */
            p {
                text-align: center;
                font-size: 12px;
                color: #555;
            }

            /* Button styling */
            .generate-button {
                background-color: #4CAF50; /* Green background */
                color: white;
                padding: 10px 15px;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                margin-bottom: 15px; /* Space below button */
                transition: background-color 0.3s; /* Smooth background transition */
            }

            .generate-button:hover {
                background-color: #45a049; /* Darker green on hover */
            }

            /* Modal Styles */
            .ipcr-modal {
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

            /* Modal content */
            .ipcr-modal-content {
                background-color: #fefefe;
                margin: 5% auto; /* Centered with a slight margin */
                padding: 20px;
                border: 1px solid #888;
                width: 80%; /* Could be more or less, depending on screen size */
                max-height: 90%; /* Limit the max height of the modal content */
                overflow-y: auto; /* Enable vertical scrolling if content overflows */
                border-radius: 8px; /* Rounded corners for modal */
            }

            /* Close button */
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

            /* Notification container */
            #notification {
                display: none;
                padding: 10px;
                position: fixed;
                top: 10px;
                right: 10px;
                background-color: #28a745; /* Green background for success */
                color: white;
                z-index: 1000;
                border-radius: 5px;
            }

            /* Confirmation modal styles */
            #ipcrconfirmation-model {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.5);
                z-index: 1000;
            }

            #ipcrconfirmation-model div {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background-color: #fff;
                padding: 20px;
                border-radius: 5px;
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
            }

            .view-rate-button {
                background-color: #4CAF50; /* Green background */
                color: white;
                padding: 10px 15px;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                margin-bottom: 15px; /* Space below button */
                transition: background-color 0.3s; /* Smooth background transition */
            }

            .view-rate-button:hover {
                background-color: #45a049; /* Darker green on hover */
            }

            /* Modal container */
            .modal {
            display: none; /* Hidden by default */
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
            }

            /* Modal content box */
            .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 50%;
            border-radius: 8px;
            }

            /* Close button */
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

                    /* Modal container */
            .edit-modal {
                display: none; /* Hidden by default */
                position: fixed;
                z-index: 1;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                overflow: auto;
                background-color: rgba(0, 0, 0, 0.5); /* Semi-transparent background */
            }

            /* Modal content box */
            .edit-modal-content {
                background-color: #fefefe;
                margin: 15% auto; /* Centered with a margin */
                padding: 20px;
                border: 1px solid #888;
                width: 50%; /* Width of the modal */
                border-radius: 8px; /* Rounded corners */
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

                /* Style the submit button */
    button[type="submit"] {
        padding: 8px 16px;
        font-size: 14px;
        cursor: pointer;
        position: relative;
        overflow: hidden;
    }

    /* Spinner effect when uploading */
    .button-loading {
        color: transparent; /* Hide the button text during loading */
        pointer-events: none; /* Disable clicks */
    }

    .button-loading::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 18px;
        height: 18px;
        margin: -9px; /* Center the spinner */
        border: 2px solid black; /* Set spinner border color to black */
        border-top-color: transparent; /* Top border transparent to create spinner effect */
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
        100% {
            transform: rotate(360deg);
        }
    }

    /* Simple Dropdown Container styling */
        .select-container {
            display: inline-block; /* Inline-block for compact layout */
            margin: 5px 0; /* Margin for spacing */
        }

        /* Simple Dropdown styling */
        select {
            padding: 5px; /* Reduced padding for a simpler design */
            border: 1px solid #ccc; /* Light gray border */
            border-radius: 3px; /* Slightly rounded corners */
            font-size: 14px; /* Font size */
            background-color: #fff; /* White background */
            color: #333; /* Dark text color */
            cursor: pointer; /* Pointer cursor on hover */
        }

        /* Change border color on focus */
        select:focus {
            border-color: #4CAF50; /* Green border on focus */
            outline: none; /* Remove default outline */
        }

        /* Style for the default option */
        select option {
            color: #333; /* Dark text color for options */
        }

</style>
<script>
        function closeTab() {
            window.close(); // Attempt to close the current tab
        }
    </script>
</head>
<body>
<div class="container">
    <h3>Created Tasks</h3>
    <button class="close-btn" onclick="closeTab()" style="position: fixed; top: 10px; right: 10px; z-index: 1000; background-color: #ff4d4d; color: white; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer;">
    Close This Tab
</button>

    <?php if (!empty($tasks)): ?>
        <?php foreach ($tasks as $group_task_id => $taskGroup): ?>
            <h4>Semester Name: <?php echo htmlspecialchars($taskGroup[0]['name_of_semester']); ?></h4>
            <div class="select-container">
                <select id="actionSelect_<?php echo htmlspecialchars($group_task_id); ?>" onchange="handleActionSelect('<?php echo htmlspecialchars($group_task_id); ?>', '<?php echo isset($taskGroup[0]['id_of_semester']) ? htmlspecialchars($taskGroup[0]['id_of_semester']) : ''; ?>', '<?php echo htmlspecialchars($designation); ?>')">
                    <option value="">Select Action</option>
                    <option value="targetForm">Target Form</option>
                    <option value="generateForms">Generate Forms</option>
                </select>
            </div>
            <table>
            <tr>
                <th rowspan="2" style="font-size: 14px;">Areas of Evaluation</th>
                <th rowspan="2" style="font-size: 14px;">Name</th>
                <th rowspan="2" style="font-size: 14px;">Description</th>
                <th rowspan="2" style="font-size: 14px;">Target</th>
                <th rowspan="2" style="font-size: 14px;">Documents Uploaded</th>
                <th colspan="4" rowspan="1">Rating</th> <!-- Rating with 4 sub-columns -->
                <th rowspan="2" style="font-size: 14px;">Uploaded Files</th>
                <th rowspan="2" style="font-size: 14px;">Progress</th>
            </tr>
            <tr>
                <th style="font-size: 10px;">Q</th>
                <th style="font-size: 10px;">E</th>
                <th style="font-size: 10px;">T</th>
                <th style="font-size: 10px;">A</th>
            </tr>
                <?php foreach ($taskGroup as $task): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($task['task_type']); ?></td>
                        <td><?php echo htmlspecialchars(stripslashes($task['task_name'])); ?></td>
                        <td><?php echo htmlspecialchars(stripslashes($task['description'])); ?></td>
                        <td><?php echo htmlspecialchars($task['documents_required']); ?></td>
                        <td><?php echo htmlspecialchars($task['documents_uploaded']); ?></td>
                        <?php
                            // Calculate progress for the current task
                            $documents_req = (int) $task['documents_required']; // Total documents required
                            $documents_uploaded = (int) $task['documents_uploaded']; // Documents uploaded
                            $progress = $documents_req > 0 ? round(($documents_uploaded / $documents_req) * 100) : 0; // Calculate progress
                        ?>
                         <!-- Ratings for each sub-category -->
                        <td><?php echo htmlspecialchars($task['quality']); ?></td>
                        <td><?php echo htmlspecialchars($task['efficiency']); ?></td>
                        <td><?php echo htmlspecialchars($task['timeliness']); ?></td>
                        <td><?php echo htmlspecialchars($task['average']); ?></td>
                        <td style="word-break: break-word; font-size: 12px; padding: 5px;">
                            <?php
                            if (!empty($task['uploaded_files'])) {
                                $uploadedFiles = explode(',', $task['uploaded_files']);
                                echo '<ul>';
                                foreach ($uploadedFiles as $file) {
                                    echo '<li>
                                            <a href="#" onclick="openFileModal(\''.urlencode($file).'\', \''.urlencode($task['task_id']).'\', \''.urlencode($task['group_task_id']).'\')"> '.htmlspecialchars($file).'</a>
                                            <button class="delete-file" data-file="'.urlencode($file).'" data-task-id="'.urlencode($task['task_id']).'" data-group-task-id="'.urlencode($task['group_task_id']).'">Delete</button>
                                        </li>';
                                }
                                echo '</ul>';
                            } else {
                                echo 'No files uploaded';
                            }
                            ?>
                        </td>
                        <td style="width: 90px; height: 200px; vertical-align: middle;">
                                <div class="circle">
                                    <svg width="60" height="60"> <!-- Further reduced size -->
                                        <circle class="circle-bg" cx="30" cy="30" r="25"></circle> <!-- Adjusted radius -->
                                        <circle class="circle-progress" cx="30" cy="30" r="25" style="stroke-dasharray: <?php echo ($progress / 100) * (2 * pi() * 25); ?>, 157.08;"></circle> <!-- Adjusted radius -->
                                    </svg>
                                    <div class="percentage"><?php echo $progress; ?>%</div>
                                </div>
                            </td>
                       
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No tasks created.</p>
    <?php endif; ?>
</div>
<div id="fileModal" class="modal">
    <div class="modal-content" style="width: 80%; max-width: 800px; margin: auto; padding: 20px; border-radius: 5px; background-color: white; position: relative; top: 50px;">
        <span class="close" onclick="closeModalfile()">&times;</span>
        <iframe id="fileIframe" src="" style="width: 100%; height: 500px; border: none;"></iframe>
    </div>
</div>

<!-- Edit Modal -->
<!-- Edit Modal -->
<div id="editModal" class="edit-modal">
    <style>

        .edit-modal {
            justify-content: center; /* Center horizontally */
            align-items: center; /* Center vertically */
        }

        .edit-modal-content {
            background-color: #fefefe;
            padding: 20px;
            border: 1px solid #888;
            width: 80%; /* Could be more or less, depending on screen size */
            max-width: 500px; /* Optional: limit maximum width */
            border-radius: 5px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            margin-top: 20px; /* Add some space from the top */
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

        h2 {
            text-align: center;
        }

        label {
            display: block;
            margin: 10px 0 5px;
        }

        input[type="text"],
        input[type="number"],
        textarea,
        select {
            width: 100%; /* Full width */
            padding: 10px;
            margin: 5px 0 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box; /* Include padding and border in element's total width and height */
        }
        button:hover {
            background-color: #45a049; /* Darker green */
        }
    </style>
    <div class="edit-modal-content">
        <span class="close" onclick="closeEditModal()">&times;</span>
        <h2>Edit Task</h2>
        <form id="editTaskForm">
            <input type="hidden" name="task_id" id="edit_task_id">
            <label for="edit_task_name">Task Name:</label>
            <input type="text" name="task_name" id="edit_task_name" required>
            <label for="edit_description">Description:</label>
            <textarea name="description" id="edit_description" required></textarea>
            <label for="edit_task_type">Task Type:</label>
            <select id="edit_task_type" name="task_type">
                <?php if (!$hideStrategic) { ?>
                    <option value="strategic">Strategic</option>
                <?php } ?>
                <option value="core">Core</option>
                <option value="support">Support</option>
            </select>
            <label for="edit_documents_required">Documents Required:</label>
            <input type="number" id="edit_documents_required" name="documents_required" min="0" value="0">
            <button type="submit">Save Changes</button>
        </form>
    </div>
    <script>
    // Function to open the edit modal with pre-filled data
    function openEditModal(taskId, taskName, description, taskType, documentsRequired) {
        document.getElementById('edit_task_id').value = taskId;
        document.getElementById('edit_task_name').value = taskName;
        document.getElementById('edit_description').value = description;
        document.getElementById('edit_task_type').value = taskType;
        document.getElementById('edit_documents_required').value = documentsRequired;
        document.getElementById('editModal').style.display = 'block'; // Show the modal
    }

    // Function to close the modal
    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none'; // Hide the modal
    }

    // Add event listener to handle form submission with AJAX
    document.getElementById('editTaskForm').addEventListener('submit', function(event) {
        event.preventDefault(); // Prevent the default form submission

        var formData = new FormData(this);

        var xhr = new XMLHttpRequest();
        xhr.open('POST', '../ipcrtaskspages/edit_own_task.php', true);

        xhr.onload = function () {
            if (xhr.status === 200) {
                localStorage.setItem('notificationMessage', 'Task updated successfully!');
                localStorage.setItem('notificationError', 'false'); // No error
            } else {
                localStorage.setItem('notificationMessage', 'An error occurred while updating the task.');
                localStorage.setItem('notificationError', 'true'); // Error occurred
            }
            location.reload(); // Reload the page to show the notification
        };

        xhr.send(formData);
    });

    // Check if the page should show the notification
    function displayNotification() {
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
    }

    // Call displayNotification on page load
    window.onload = displayNotification;
</script>

</div>



</div>

<!-- The Modal -->
<div id="myModal" class="ipcr-modal">
    <div class="ipcr-modal-content">
        <span class="close" id="closeModal">&times;</span>
        <iframe id="iframeContent" src="" style="width: 100%; height: 500px; border: none;"></iframe>
    </div>
</div>
  <!-- Notification Container -->
  <div id="notification" style="display: none; padding: 10px; position: fixed; top: 10px; right: 10px; background-color: #28a745; color: white; z-index: 1000; border-radius: 5px;"></div>

  <!-- ipcrconfirmation-model -->
<div id="ipcrconfirmation-model" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background-color: #fff; padding: 20px; border-radius: 5px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);">
        <p style="font-size: 22px; font-weight: bold;">Confirm Deletion</p>
        <p style="font-size: 20px;">Click OK to confirm deletion of this file.</p>
        <button id="ipcr-ok-button" style="background-color: #4CAF50; color: #fff; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">OK</button>
        <button id="ipcr-cancel-button" style="background-color: #e74c3c; color: #fff; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">Cancel</button>
    </div>
</div>



</body>
<script>
    function handleActionSelect(groupTaskId, idOfSemester, designation) {
    var selectElement = document.getElementById('actionSelect_' + groupTaskId);
    var selectedValue = selectElement.value;

    if (selectedValue === 'targetForm') {
        generateNoSignBottom(groupTaskId, idOfSemester, designation);
    } else if (selectedValue === 'generateForms') {
        generateForms(groupTaskId, idOfSemester, designation);
    }

    // Reset the select element after an action is taken
    selectElement.selectedIndex = 0; // Reset to "Select Action"
}
function generateForms(groupTaskId, idOfSemester, designation) {
    let url;

    // Check the designation and set the URL accordingly
    if (designation.toLowerCase() === 'dean') {
        url = `../ipcrtaskspages/ipcrform_for_dean_ipcr.php?group_task_id=${encodeURIComponent(groupTaskId)}&id_of_semester=${encodeURIComponent(idOfSemester)}`;
    } else {
        url = `../ipcrtaskspages/ipcrgenerate_form.php?group_task_id=${encodeURIComponent(groupTaskId)}&id_of_semester=${encodeURIComponent(idOfSemester)}`;
    }

    // Set the iframe source and display the modal
    document.getElementById('iframeContent').src = url;
    document.getElementById('myModal').style.display = "block";
}

function generateNoSignBottom(groupTaskId, idOfSemester, designation) {
    let url;

    // Check the designation and set the URL accordingly
    if (designation.toLowerCase() === 'dean') {
        url = `dean_form_no_sisnature_bottom.php?group_task_id=${encodeURIComponent(groupTaskId)}&id_of_semester=${encodeURIComponent(idOfSemester)}`;
    } else {
        url = `ipcrform_no_sign_bottom.php?group_task_id=${encodeURIComponent(groupTaskId)}&id_of_semester=${encodeURIComponent(idOfSemester)}`;
    }

    // Set the iframe source and display the modal
    document.getElementById('iframeContent').src = url;
    document.getElementById('myModal').style.display = "block";
}


    // Close the modal when the user clicks on <span> (x)
    document.getElementById('closeModal').onclick = function() {
        document.getElementById('myModal').style.display = "none";
    };

    // Close the modal when the user clicks anywhere outside of the modal
    window.onclick = function(event) {
        if (event.target == document.getElementById('myModal')) {
            document.getElementById('myModal').style.display = "none";
        }
    };
    </script>

    <script>
   document.addEventListener('submit', function(event) {
    if (event.target && event.target.matches('#ipctraskupload')) {
        event.preventDefault(); // Prevent the form from submitting the default way

        var uploadButton = event.target.querySelector('button[type="submit"]');
        uploadButton.classList.add('button-loading'); // Add loading spinner class
        uploadButton.disabled = true; // Disable the button

        // Store the original document title
        var originalTitle = document.title;
        // Set the title to indicate loading
        document.title = "Uploading...";

        var formData = new FormData(event.target);

        var xhr = new XMLHttpRequest();
        xhr.open('POST', '../ipcrtaskspages/own_taskfiles_submit.php', true);

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

            // Save scroll position before reloading
            localStorage.setItem('scrollPosition', window.scrollY);
            location.reload(); // Reload the page to show the notification
        };

        xhr.send(formData);
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

// Restore scroll position after page reload
window.addEventListener('load', function() {
    var scrollPosition = localStorage.getItem('scrollPosition');
    if (scrollPosition !== null) {
        window.scrollTo(0, parseInt(scrollPosition, 10));
        localStorage.removeItem('scrollPosition');
    }
});


    // Handle file deletion
    document.addEventListener('click', function(event) {
        if (event.target && event.target.matches('.delete-file')) {
            event.preventDefault(); // Prevent default button behavior

            // Show the confirmation modal
            const modal = document.getElementById('ipcrconfirmation-model');
            modal.style.display = 'block';

            // Get file data from the clicked element
            var file = event.target.getAttribute('data-file');
            var taskId = event.target.getAttribute('data-task-id');
            var groupTaskId = event.target.getAttribute('data-group-task-id');

            // Handle OK button click
            document.getElementById('ipcr-ok-button').onclick = function() {
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'ipcrtaskspages/delete_own_taskfile.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

                xhr.onload = function () {
                    var notification = document.getElementById('notification');
                    if (xhr.status === 200) {
                        // Set the text and show the notification
                        notification.innerText = 'File deleted successfully!';
                        notification.style.backgroundColor = '#28a745'; // Success background color
                    } else {
                        // Set the text for error and show the notification
                        notification.innerText = 'An error occurred while deleting the file.';
                        notification.style.backgroundColor = '#dc3545'; // Error background color
                        console.error('Error deleting file:', xhr.status, xhr.statusText);
                    }
                    notification.style.display = 'block';

                    // Hide the notification after 3 seconds
                    setTimeout(function() {
                        notification.style.display = 'none';
                        console.log('Reloading page...');
                        window.location.reload(); // Reload page to reflect changes
                    }, 3000);
                };

                // Send the request
                xhr.send('file=' + encodeURIComponent(file) + '&task_id=' + encodeURIComponent(taskId) + '&group_task_id=' + encodeURIComponent(groupTaskId));

                // Close the modal after submission
                modal.style.display = 'none';
            };

            // Handle Cancel button click
            document.getElementById('ipcr-cancel-button').onclick = function() {
                // Close the modal without doing anything
                modal.style.display = 'none';
            };
        }
    });

    // Handle form submission
    // Handle file deletion
    document.addEventListener('click', function(event) {
        if (event.target && event.target.matches('.delete-file')) {
            event.preventDefault(); // Prevent default button behavior

            // Show the confirmation modal
            const modal = document.getElementById('ipcrconfirmation-model');
            modal.style.display = 'block';

            // Get file data from the clicked element
            var file = event.target.getAttribute('data-file');
            var taskId = event.target.getAttribute('data-task-id');
            var groupTaskId = event.target.getAttribute('data-group-task-id');

            // Handle OK button click
            document.getElementById('ipcr-ok-button').onclick = function() {
                var xhr = new XMLHttpRequest();
                xhr.open('POST', '../ipcrtaskspages/delete_own_taskfile.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

                xhr.onload = function () {
                    if (xhr.status === 200) {
                        localStorage.setItem('notificationMessage', 'File deleted successfully!');
                        localStorage.setItem('notificationError', 'false'); // No error
                    } else {
                        localStorage.setItem('notificationMessage', 'An error occurred while deleting the file.');
                        localStorage.setItem('notificationError', 'true'); // Error occurred
                        console.error('Error deleting file:', xhr.status, xhr.statusText);
                    }
                    console.log('Reloading page...');
                    window.location.reload(); // Reload page to reflect changes
                };

                // Send the request
                xhr.send('file=' + encodeURIComponent(file) + '&task_id=' + encodeURIComponent(taskId) + '&group_task_id=' + encodeURIComponent(groupTaskId));

                // Close the modal after submission
                modal.style.display = 'none';
            };

            // Handle Cancel button click
            document.getElementById('ipcr-cancel-button').onclick = function() {
                // Close the modal without doing anything
                modal.style.display = 'none';
            };
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
        function openFileModal(file, taskId, groupTaskId) {
        var url = '../ipcrtaskspages/own_task_view_file.php?file=' + encodeURIComponent(file) + '&task_id=' + encodeURIComponent(taskId) + '&group_task_id=' + encodeURIComponent(groupTaskId);
        document.getElementById('fileIframe').src = url;
        document.getElementById('fileModal').style.display = 'block';
    }

    function closeModalfile() {
        document.getElementById('fileModal').style.display = 'none';
    }
</script>
</html>

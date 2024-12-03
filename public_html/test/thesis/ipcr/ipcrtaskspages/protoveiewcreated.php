<?php
session_start();
include '../../dbconnections/config.php'; // Include your database connection

// Function to fetch tasks created by the logged-in user
function fetchTasks() {
    global $conn; // Use the global database connection

            // Assuming user ID is stored in the session
            $idnumber = $_SESSION['idnumber'];

            // Prepare the SQL query to fetch tasks created by this user
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
            WHERE st.idnumber = ? AND stt.status = 'undone'  -- Filter by status
            GROUP BY st.group_task_id, st.task_id;
        ");

    // Check if the statement was prepared successfully
    if (!$stmt) {
        echo "Failed to prepare statement: " . $conn->error;
        return;
    }

    // Bind the user's idnumber to the query
    $stmt->bind_param("s", $idnumber);

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
            $tasks[$row['group_task_id']][] = $row; // Group tasks by group_task_id
        }
    }

    // Close the statement
    $stmt->close();

    // Return the tasks array
    return $tasks;
}


// Fetch the tasks so they are available for use in the HTML
$tasks = fetchTasks();

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
        /* Style for the container */
        .container {
            width: 80%;
            margin: 0 auto;
            padding: 20px;
            font-family: Arial, sans-serif;
            background-color: #f9f9f9; /* Light background for better contrast */
            border-radius: 8px; /* Rounded corners */
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); /* Subtle shadow for depth */
        }

        /* Table styling */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            border-radius: 8px; /* Rounded corners for table */
            overflow: hidden; /* Ensures rounded corners are applied */
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
            background-color: #4CAF50; /* Green background for header */
            color: white;
            text-align: center; 
        }

        /* Alternate row coloring */
        tr:nth-child(even) {
            background-color: #f2f2f2; /* Light grey for even rows */
        }

        /* Table hover effect */
        tr:hover {
            background-color: #ddd; /* Light grey on hover */
        }

        /* Heading */
        h3 {
            color: #333;
            font-size: 24px;
            text-align: center;
            margin-bottom: 20px; /* Space below the heading */
        }

        /* Message styling */
        p {
            text-align: center;
            font-size: 18px;
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

    </style>
</head>
<body>
<div class="container">
    <h3>Your Created Tasks</h3>

    <?php if (!empty($tasks)): ?>
        <?php foreach ($tasks as $group_task_id => $taskGroup): ?>
            <h4>Semester Name: <?php echo htmlspecialchars($taskGroup[0]['name_of_semester']); ?></h4>
            <button class="generate-button" onclick="generateForms('<?php echo htmlspecialchars($group_task_id); ?>', '<?php echo isset($taskGroup[0]['id_of_semester']) ? htmlspecialchars($taskGroup[0]['id_of_semester']) : ''; ?>')">Generate Forms</button>
            <button class="view-rate-button" onclick="viewRate('<?php echo htmlspecialchars($group_task_id); ?>')">View Rate</button>
            <table>
                <tr>
                    <th>Task Type</th>
                    <th>Task Name</th>
                    <th>Description</th>
                    <th>Target Documents</th>
                    <th>Documents Uploaded</th>
                    <th>Uploaded Files</th>
                    <th>Upload</th>
                </tr>
                <?php foreach ($taskGroup as $task): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($task['task_type']); ?></td>
                        <td><?php echo htmlspecialchars($task['task_name']); ?></td>
                        <td><?php echo htmlspecialchars($task['description']); ?></td>
                        <td><?php echo htmlspecialchars($task['documents_required']); ?></td>
                        <td><?php echo htmlspecialchars($task['documents_uploaded']); ?></td>
                        <td style="break-word; font-size: 12px; padding: 5px;">
                            <?php
                            if (!empty($task['uploaded_files'])) {
                                $uploadedFiles = explode(',', $task['uploaded_files']);
                                echo '<ul>';
                                foreach ($uploadedFiles as $file) {
                                    echo '<li>
                                            <a href="ipcrtaskspages/own_task_view_file.php?file=' . urlencode($file) . '&task_id=' . urlencode($task['task_id']) . '&group_task_id=' . urlencode($task['group_task_id']) . '" target="_blank">' . htmlspecialchars($file) . '</a>
                                            <button class="delete-file" data-file="' . urlencode($file) . '" data-task-id="' . urlencode($task['task_id']) . '" data-group-task-id="' . urlencode($task['group_task_id']) . '">Delete</button>
                                        </li>';
                                }
                                echo '</ul>';
                            } else {
                                echo 'No files uploaded';
                            }
                            ?>
                        </td>

                        <td colspan="7">
                            <form id="ipctraskupload" method="post" enctype="multipart/form-data">
                                <input type="hidden" name="group_task_id" value="<?php echo htmlspecialchars($group_task_id); ?>">
                                <input type="hidden" name="task_id" value="<?php echo htmlspecialchars($task['task_id']); ?>">
                                <input type="hidden" name="id_of_semester" value="<?php echo htmlspecialchars($task['id_of_semester']); ?>">
                                <input type="hidden" name="task_type" value="<?php echo htmlspecialchars($task['task_type']); ?>">

                                <?php
                                $documentsRequired = (int) $task['documents_required'];
                                $documentsUploaded = (int) $task['documents_uploaded'];
                                $filesToUpload = $documentsRequired - $documentsUploaded;

                                if ($filesToUpload > 0): 
                                    for ($i = 1; $i <= $filesToUpload; $i++): ?>
                                        <input type="file" name="file[]" accept=".doc, .docx, .ppt, .pptx, .xls, .xlsx, .pdf, .jpg, .jpeg, .png, .mp4, .avi, .mov, .mkv">
                                    <?php endfor; ?>
                                    <button type="submit">Upload</button>
                                <?php else: ?>
                                    <p>All required documents have been uploaded.</p>
                                <?php endif; ?>
                            </form>
                        </td>
                        <td>
                            <button class="view-rate-button" 
                                    onclick="openModal(
                                        '<?php echo htmlspecialchars($task['task_name']); ?>', 
                                        '<?php echo htmlspecialchars($task['description']); ?>', 
                                        '<?php echo htmlspecialchars($task['quality']); ?>', 
                                        '<?php echo htmlspecialchars($task['efficiency']); ?>', 
                                        '<?php echo htmlspecialchars($task['timeliness']); ?>', 
                                        '<?php echo htmlspecialchars($task['average']); ?>'
                                    )">View Rate</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No tasks created yet.</p>
    <?php endif; ?>
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
        <p style="font-size: 18px; font-weight: bold;">Confirm Deletion</p>
        <p>Click OK to confirm deletion of this file.</p>
        <button id="ipcr-ok-button" style="background-color: #4CAF50; color: #fff; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">OK</button>
        <button id="ipcr-cancel-button" style="background-color: #e74c3c; color: #fff; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">Cancel</button>
    </div>
</div>

<!-- Modal structure -->
<div id="rateModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeModal()">&times;</span>
    <h3>Task Ratings</h3>
    <p><strong>Task Name:</strong> <span id="taskName"></span></p>
    <p><strong>Description:</strong> <span id="taskDescription"></span></p>
    <p><strong>Quality:</strong> <span id="taskQuality"></span></p>
    <p><strong>Efficiency:</strong> <span id="taskEfficiency"></span></p>
    <p><strong>Timeliness:</strong> <span id="taskTimeliness"></span></p>
    <p><strong>Average:</strong> <span id="taskAverage"></span></p>
  </div>
</div>


</body>
<script>
    function generateForms(groupTaskId, idOfSemester) {
        // Construct the URL with both parameters
        const url = `ipcrtaskspages/ipcrgenerate_form.php?group_task_id=${encodeURIComponent(groupTaskId)}&id_of_semester=${encodeURIComponent(idOfSemester)}`;

        
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

            var formData = new FormData(event.target);

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'ipcrtaskspages/own_taskfiles_submit.php', true);

            xhr.onload = function () {
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
                xhr.open('POST', 'ipcrtaskspages/delete_own_taskfile.php', true);
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
    // Function to open modal and fill in task details
    function openModal(taskName, taskDescription, quality, efficiency, timeliness, average) {
        // Set the task details inside the modal
        document.getElementById("taskName").textContent = taskName;
        document.getElementById("taskDescription").textContent = taskDescription;
        document.getElementById("taskQuality").textContent = quality;
        document.getElementById("taskEfficiency").textContent = efficiency;
        document.getElementById("taskTimeliness").textContent = timeliness;
        document.getElementById("taskAverage").textContent = average;
        
        // Display the modal
        document.getElementById("rateModal").style.display = "block";
    }

    // Function to close modal
    function closeModal() {
        document.getElementById("rateModal").style.display = "none";
    }

    // Close the modal when user clicks anywhere outside the modal content
    window.onclick = function(event) {
        const modal = document.getElementById("rateModal");
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }
</script>

</html>

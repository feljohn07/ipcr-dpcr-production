<?php
session_start();
// Include the database connection file
include '../dbconnections/config.php'; // Connection to the main database

// Retrieve office head idnumber from session
$idnumber = $_SESSION['idnumber']; // Assuming this is correctly set in your login process

if (empty($idnumber) || $idnumber == '0') {
    die("Error: Office head ID is not set properly.");
}

// Set the timezone to the Philippines
date_default_timezone_set('Asia/Manila');

// Handle AJAX request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task_id']) && isset($_POST['action'])) {
    $task_id = $_POST['task_id'];
    $action = $_POST['action'];
    $vpcomment = ''; // Add a comment variable if needed

    // Set approval values based on the action
    if ($action === 'approve') {
        $vpapproval = 1; // Approve
        $status = '1';
        $vp_first_created_at = date('Y-m-d H:i:s'); // Current date and time in PHP timezone

        $update_stmt = $conn->prepare("UPDATE semester_tasks SET vpapproval = ?, vpcomment = ?, vp_first_created_at = ? WHERE semester_id = ?");
        $update_stmt->bind_param("issi", $vpapproval, $vpcomment, $vp_first_created_at, $task_id);
        $update_stmt->execute();
        $update_stmt->close();
    } elseif ($action === 'pending') {
        $vpapproval = null; // Set to null for pending
        $status = null;
        $vp_first_created_at = null; // No date for pending

        $update_stmt = $conn->prepare("UPDATE semester_tasks SET vpapproval = ?, vpcomment = ?, vp_first_created_at = ? WHERE semester_id = ?");
        $update_stmt->bind_param("issi", $vpapproval, $vpcomment, $vp_first_created_at, $task_id);
        $update_stmt->execute();
        $update_stmt->close();
    } elseif ($action === 'approve_final') {
        $final_approval = 1; // Approve final
        $vp_final_created_at = date('Y-m-d H:i:s'); // Current date and time in PHP timezone

        $update_stmt = $conn->prepare("UPDATE semester_tasks SET final_approval_vpaa = ?, vp_final_created_at = ? WHERE semester_id = ?");
        $update_stmt->bind_param("isi", $final_approval, $vp_final_created_at, $task_id);
        $update_stmt->execute();
        $update_stmt->close();
        echo json_encode(['status' => 'success']);
        exit();
    } elseif ($action === 'final_pending') {
        $final_approval = 0; // Set final to pending
        $vp_final_created_at = null; // No date for pending
        $update_stmt = $conn->prepare("UPDATE semester_tasks SET final_approval_vpaa = ?, vp_final_created_at = ? WHERE semester_id = ?");
        $update_stmt->bind_param("isi", $final_approval, $vp_final_created_at, $task_id);
        $update_stmt->execute();
        $update_stmt->close();
        echo json_encode(['status' => 'success']);
        exit();
    } else {
        // Invalid action
        echo json_encode(['error' => 'Invalid action']);
        exit();
    }

    // Fetch the office_head_id, semester_name, and college for the task
    $task_stmt = $conn->prepare("SELECT office_head_id, semester_name, college FROM semester_tasks WHERE semester_id = ?");
    $task_stmt->bind_param("i", $task_id);
    $task_stmt->execute();
    $task_stmt->bind_result($office_head_id, $semester_name, $college);
    $task_stmt->fetch();
    $task_stmt->close();

    // Insert a record into the notifications table
    $notification_stmt = $conn->prepare("INSERT INTO task_notification (task_id, semester_name, officehead_id_number, college, status) VALUES (?, ?, ?, ?, ?)");
    $notification_stmt->bind_param("isssi", $task_id, $semester_name, $office_head_id, $college, $status);
    $notification_stmt->execute();
    $notification_stmt->close();

    // Output success message or status
    echo json_encode(['status' => 'success']);
    exit();
}

// Fetch semester tasks from the main database
$semester_stmt = $conn->prepare("SELECT * FROM semester_tasks ORDER BY created_at DESC");
$semester_stmt->execute();
$semester_result = $semester_stmt->get_result();
$semesters = $semester_result->fetch_all(MYSQLI_ASSOC);
$semester_stmt->close();

$conn->close(); // Close the database connection
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VP Tasks</title>
    <style>
        /* Add your CSS styling here */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }
        th {
            text-align: center; 
            background-color: #f4f4f4;
        }
        .pending {
            color: blue;
        }
        .signed {
            color: rgb(32, 244, 32);
        }
        .button-container {
    display: flex;
    flex-wrap: wrap; /* Allows buttons to wrap if the screen is narrow */
    gap: 10px; /* Adds space between buttons */
    justify-content: center; /* Centers the buttons */
}

.btn {
    padding: 8px 12px;
    font-size: 14px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.3s;
}

.approve-btn {
    background-color: #28a745; /* Green for approval */
    color: white;
}

.pending-btn {
    background-color: blue; /* Blue for pending */
    color: white;
}

.final-approve-btn {
    background-color: #007bff; /* Blue for final approval */
    color: white;
}

.final-pending-btn {
    background-color: orange; /* Orange for final pending */
    color: white;
}

.view-details-btn {
    background-color: #6c757d; /* Gray for view details */
    color: white;
}

.btn:hover {
    opacity: 0.8; /* Slightly dim the button on hover */
}
    </style>
</head>
<body>
    <!-- Notification Container -->
    <div id="notification" style="display: none; padding: 10px; position: fixed; top: 10px; right: 10px; background-color: #28a745; color: white; z-index: 1000; border-radius: 5px;"></div>

    <h2>Created Tasks</h2>

    <table>
        <thead>
            <tr>
                <th>Semester Name</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>College</th>
                <th>First signature</th>
                <th>Final signature</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="taskTableBody">
            <?php foreach ($semesters as $semester): ?>
                <tr id="task-row-<?php echo htmlspecialchars($semester['semester_id']); ?>">
                    <td><?php echo htmlspecialchars($semester['semester_name']); ?></td>
                    <td><?php echo date('F d, Y', strtotime($semester['start_date'])); ?></td>
                    <td><?php echo date('F d, Y', strtotime($semester['end_date'])); ?></td>
                    <td><?php echo htmlspecialchars($semester['college']); ?></td>
                    <td>
                        <?php 
                        $vp_approval = $semester['vpapproval'];
                        if ($vp_approval === null || $vp_approval === '') {
                            echo '<span class="pending">Pending</span>'; // Display "Pending" if vpapproval is null or empty
                        } elseif ($vp_approval == '0') {
                            echo '<span class="pending">Pending</span>'; // Display "Pending" if disapproved
                        } else {
                            echo '<span class="signed">Signed</span>'; // Display "Signed" if approved
                        }
                        ?>
                    </td>
                    <td>
                        <?php 
                        $final_approval = $semester['final_approval_vpaa'];
                        if ($final_approval === null || $final_approval === '') {
                            echo '<span class="pending">Pending</span>'; // Display "Pending" if vpapproval is null or empty
                        } elseif ($final_approval == '0') {
                            echo '<span class="pending">Pending</span>'; // Display "Pending" if disapproved
                        } else {
                            echo '<span class="signed">Signed</span>'; // Display "Signed" if approved
                        }
                        ?>
                    </td>
                    <td class="button-group" style="text-align: center">
    <div class="button-container">
        <button type="button" class="btn approve-btn" onclick="approveTask('<?php echo htmlspecialchars($semester['semester_id']); ?>')">Sign</button>
        <button type="button" class="btn pending-btn" onclick="setPending('<?php echo htmlspecialchars($semester['semester_id']); ?>')">Pending</button>
        <button type="button" class="btn final-approve-btn" onclick="approveFinal('<?php echo htmlspecialchars($semester['semester_id']); ?>')">Sign Final</button>
        <button type="button" class="btn final-pending-btn" onclick="setFinalPending('<?php echo htmlspecialchars($semester['semester_id']); ?>')">Final Pending</button>
        <button class="btn view-details-btn" onclick="viewTaskDetails('<?php echo htmlspecialchars($semester['semester_id']); ?>')">View in Form</button>
    </div>
</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>


    <script>
         function viewTaskDetails(semesterId) {
            // Open a new tab with detailed view
            window.open('view_semestertask_details.php?semester_id=' + semesterId, '_blank');
        }
    // Function to display a notification message
    function showNotification(message, isError = false, isPending = false) {
    var notification = document.getElementById('notification');
    notification.innerHTML = message;
    notification.style.display = 'block';

    // Set the background color based on the type of notification
    if (isPending) {
        notification.style.backgroundColor = 'blue'; // Blue for pending notifications
    } else {
        notification.style.backgroundColor = isError ? '#dc3545' : '#28a745'; // Red for error, Green for success
    }

    // Hide the notification after 3 seconds
    setTimeout(function() {
        notification.style.display = 'none';
    }, 3000);
}

// Function to approve a task
function approveTask(taskId) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'vptask.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    xhr.onload = function () {
        if (xhr.status === 200) {
            var response = JSON.parse(xhr.responseText);
            if (response.status === 'success') {
                // Update the task row status
                var taskRow = document.getElementById('task-row-' + taskId);
                var statusCell = taskRow.querySelector('td:nth-child(5)');
                statusCell.innerHTML = '<span class="signed">Signed</span>'; // Update to 'Signed' on approval

                // Show success notification
                showNotification('Task approved successfully!');
            } else {
                showNotification('An error occurred: ' + response.error, true);
            }
        } else {
            showNotification('An error occurred while approving the task.', true);
        }
    };

    xhr.send('task_id=' + encodeURIComponent(taskId) + '&action=approve');
}

// Function to set task as pending
function setPending(taskId) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'vptask.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    xhr.onload = function () {
        if (xhr.status === 200) {
            var response = JSON.parse(xhr.responseText);
            if (response.status === 'success') {
                // Update the task row status
                var taskRow = document.getElementById('task-row-' + taskId);
                var statusCell = taskRow.querySelector('td:nth-child(5)');
                statusCell.innerHTML = '<span class="pending">Pending</span>'; // Update to 'Pending'

                // Show success notification with blue background for pending
                showNotification('Task set to pending successfully!', false, true); // Set isPending to true
            } else {
                showNotification('An error occurred: ' + response.error, true);
            }
        } else {
            showNotification('An error occurred while setting the task to pending.', true);
        }
    };

    xhr.send('task_id=' + encodeURIComponent(taskId) + '&action=pending');
}


// Function to approve final task
function approveFinal(taskId) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'vptask.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    xhr.onload = function () {
        if (xhr.status === 200) {
            var response = JSON.parse(xhr.responseText);
            if (response.status === 'success') {
                // Update the task row status for final approval
                var taskRow = document.getElementById('task-row-' + taskId);
                var statusCell = taskRow.querySelector('td:nth-child(6)'); // Adjust index if necessary
                statusCell.innerHTML = '<span class="signed">Signed</span>'; // Update to 'Final Signed'

                // Show success notification
                showNotification('Final task approved successfully!');
            } else {
                showNotification('An error occurred: ' + response.error, true);
            }
        } else {
            showNotification('An error occurred while approving the final task.', true);
        }
    };

    xhr.send('task_id=' + encodeURIComponent(taskId) + '&action=approve_final');
}

// Function to set final task as pending
function setFinalPending(taskId) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'vptask.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    xhr.onload = function () {
        if (xhr.status === 200) {
            var response = JSON.parse(xhr.responseText);
            if (response.status === 'success') {
                // Update the task row status for final pending
                var taskRow = document.getElementById('task-row-' + taskId);
                var statusCell = taskRow.querySelector('td:nth-child(6)'); // Adjust index if necessary
                statusCell.innerHTML = '<span class="pending">Pending</span>'; // Update to 'Final Pending'

                // Show success notification with blue background for pending
                showNotification('Final task set to pending successfully!', false, true); // Set isPending to true
            } else {
                showNotification('An error occurred: ' + response.error, true);
            }
        } else {
            showNotification('An error occurred while setting the final task to pending.', true);
        }
    };

    xhr.send('task_id=' + encodeURIComponent(taskId) + '&action=final_pending');
}

    </script>

</body>
</html>

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

    // Fetch the office_head_id, semester_name, and college for the task
    $task_stmt = $conn->prepare("SELECT office_head_id, semester_name, college FROM semester_tasks WHERE semester_id = ?");
    $task_stmt->bind_param("i", $task_id);
    $task_stmt->execute();
    $task_stmt->bind_result($office_head_id, $semester_name, $college);
    $task_stmt->fetch();
    $task_stmt->close();



    // Set approval values based on the action
    if ($action === 'approve') {
        $vpapproval = 1; // Approve
        $status = '1';
        $vp_first_created_at = date('Y-m-d H:i:s'); // Current date and time in PHP timezone

        $update_stmt = $conn->prepare("UPDATE semester_tasks SET vpapproval = ?, vpcomment = ?, vp_first_created_at = ? WHERE semester_id = ?");
        $update_stmt->bind_param("issi", $vpapproval, $vpcomment, $vp_first_created_at, $task_id);
        $update_stmt->execute();
        $update_stmt->close();

        $message = "Vice President Gived Intitial Approval for Semester Task : ";

    } elseif ($action === 'pending') {
        $vpapproval = null; // Set to null for pending
        $status = null;
        $vp_first_created_at = null; // No date for pending

        $update_stmt = $conn->prepare("UPDATE semester_tasks SET vpapproval = ?, vpcomment = ?, vp_first_created_at = ? WHERE semester_id = ?");
        $update_stmt->bind_param("issi", $vpapproval, $vpcomment, $vp_first_created_at, $task_id);
        $update_stmt->execute();
        $update_stmt->close();

        $message = "Vice President Revoked Intitial Approval for Semester Task : ";

    } elseif ($action === 'approve_final') {
        $final_approval = 1; // Approve final
        $vp_final_created_at = date('Y-m-d H:i:s'); // Current date and time in PHP timezone
        $status = 1; // Set status to approved
    
        // Update the semester task
        $update_stmt = $conn->prepare("UPDATE semester_tasks SET final_approval_vpaa = ?, vp_final_created_at = ? WHERE semester_id = ?");
        $update_stmt->bind_param("isi", $final_approval, $vp_final_created_at, $task_id);
        $update_stmt->execute();
        $update_stmt->close();
    
        // Insert a record into the notifications table
        $notification_stmt = $conn->prepare("INSERT INTO task_notification (task_id, status) VALUES (?, ?)
            ON DUPLICATE KEY UPDATE status = ?");
        $notification_stmt->bind_param("iii", $task_id, $status, $status);
        $notification_stmt->execute();
        $notification_stmt->close();

        $message = "Vice President Gived Final Approval for Semester Task : ";
    
        echo json_encode([
            'status' => 'success',
            'office_head_id' => $office_head_id,
            'message' => $message . "\"" . $semester_name . "\"",
        ]);
        exit();
    } elseif ($action === 'final_pending') {
        $final_approval = 0; // Set final to pending
        $vp_final_created_at = null; // No date for pending
        $status = null; // Set status to pending (null)
    
        // Update the semester task
        $update_stmt = $conn->prepare("UPDATE semester_tasks SET final_approval_vpaa = ?, vp_final_created_at = ? WHERE semester_id = ?");
        $update_stmt->bind_param("isi", $final_approval, $vp_final_created_at, $task_id);
        $update_stmt->execute();
        $update_stmt->close();
    
        // Insert a record into the notifications table
        $notification_stmt = $conn->prepare("INSERT INTO task_notification (task_id, status) VALUES (?, ?)
            ON DUPLICATE KEY UPDATE status = ?");
        $notification_stmt->bind_param("iii", $task_id, $status, $status);
        $notification_stmt->execute();
        $notification_stmt->close();

        $message = "Vice President Revoked Final Approval for Semester Task : ";
    
        echo json_encode([
            'status' => 'success',
            'office_head_id' => $office_head_id,
            'message' => $message . "\"" . $semester_name . "\"",
        ]);
        exit();
    }
    

    // Insert a record into the notifications table
    $notification_stmt = $conn->prepare("INSERT INTO task_notification (task_id, semester_name, officehead_id_number, college, status) VALUES (?, ?, ?, ?, ?)");
    $notification_stmt->bind_param("isssi", $task_id, $semester_name, $office_head_id, $college, $status);
    $notification_stmt->execute();
    $notification_stmt->close();

    // Output success message or status
    echo json_encode([
        'status' => 'success',
        'office_head_id' => $office_head_id,
        'message' => $message . "\"" . $semester_name . "\"",
    ]);
    exit();

    // Example of how to access the values
    foreach ($semesters as $semester) {
        $semester_id = $semester['semester_id'];
        $office_head_id = $semester['office_head_id'];
        $semester_name = $semester['semester_name'];
        $college = $semester['college'];
        // You can now use $office_head_id and $college as needed
    }
}



// Fetch semester tasks from the main database
$semester_stmt = $conn->prepare("SELECT * FROM semester_tasks ORDER BY created_at DESC");
$semester_stmt->execute();
$semester_result = $semester_stmt->get_result();
$semesters = $semester_result->fetch_all(MYSQLI_ASSOC);
$semester_stmt->close();

// Filter semesters to include only those with users_final_approval equal to 1
$filtered_semesters = array_filter($semesters, function($semester) {
    return $semester['users_final_approval'] == 1;
});

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
    background-color:#28a745; /* Blue for final approval */
    color: white;
}

.final-pending-btn {
    background-color: blue; /* Orange for final pending */
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
                            <?php if ($semester['users_final_approval'] == 0): ?>
                                <button type="button" class="btn approve-btn" onclick="approveTask('<?php echo htmlspecialchars($semester['semester_id']); ?>')">First Approval</button>
                                <button type="button" class="btn pending-btn" onclick="setPending('<?php echo htmlspecialchars($semester['semester_id']); ?>')">Remove Approval</button>
                            <?php endif; ?>
                            <?php if ($semester['users_final_approval'] == 1): ?>
                                <button type="button" class="btn final-approve-btn" onclick="approveFinal('<?php echo htmlspecialchars($semester['semester_id']); ?>')">Final Approval</button>
                                <button type="button" class="btn final-pending-btn" onclick="setFinalPending('<?php echo htmlspecialchars($semester['semester_id']); ?>')">Remove Approval</button>
                            <?php endif; ?>

                            <button class="btn view-details-btn" onclick="openModal('<?php echo htmlspecialchars($semester['semester_id']); ?>', '<?php echo htmlspecialchars($semester['office_head_id']); ?>', '<?php echo htmlspecialchars($semester['college']); ?>')">View DPCR Form</button>
                            <button onclick="viewipcrtask('<?php echo htmlspecialchars($semester['semester_id']); ?>')">View IPCR form</button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>

<!-- Modal Structure -->
<div id="myModal" class="modal">
    <div class="modal-content">
    <span class="close" style="font-size: 40px; position: absolute; right: 15px; top: 5px; cursor: pointer;" onclick="closeModal()">&times;</span>
        <iframe id="modalIframe" src="" width="100%" height="600px" frameborder="0"></iframe>
    </div>
</div>
    <style>
.modal {
    display: none; /* Hidden by default */
    position: fixed; /* Stay in place */
    z-index: 1000; /* Sit on top */
    left: 50%; /* Center horizontally */
    top: 50%; /* Center vertically */
    transform: translate(-50%, -50%); /* Adjust position back up by 50% of the modal height */
    width: 1200px; /* Full width, or adjust as needed */
    max-width: 1200px; /* Set a maximum width for larger screens */
    height: auto; /* Auto height to fit content */
    background-color: rgba(0, 0, 0, 0.4); /* Black background with opacity */
    overflow: auto; /* Enable scroll if needed */
}

.modal-content {
    background-color: #fefefe;
    padding: 20px;
    border: 1px solid #888;
    width: 1200px; /* Make sure modal content takes full width */
    box-sizing: border-box; /* Include padding and border in the element's total width */
}
    </style>
<script>
    document.addEventListener('DOMContentLoaded', function () {
    const myModal = document.getElementById('myModal');
    const closeMyModal = document.querySelector('.modal .close');
    const modalIframe = document.getElementById('modalIframe');

    // Close the modal when the close button is clicked
    closeMyModal.addEventListener('click', function () {
        closeModal();
    });

    // Close the modal when clicking outside of the modal content
    window.addEventListener('click', function (event) {
        if (event.target === myModal) {
            closeModal();
        }
    });
});

// Function to open modal and set iframe source
document.addEventListener('DOMContentLoaded', function () {
    const myModal = document.getElementById('myModal');
    const closeMyModal = document.querySelector('.modal .close');
    const modalIframe = document.getElementById('modalIframe');

    // Close the modal when the close button is clicked
    closeMyModal.addEventListener('click', function () {
        closeModal();
    });

    // Close the modal when clicking outside of the modal content
    window.addEventListener('click', function (event) {
        if (event.target === myModal) {
            closeModal();
        }
    });
});

// Function to open modal and set iframe source
function openModal(semesterId, officeHeadId, college) {
    const modalIframe = document.getElementById('modalIframe');

    // Set the iframe source with the semester_id, office_head_id, and college as parameters
    modalIframe.src = `dpcr_forms.php?semester_id=${semesterId}&office_head_id=${officeHeadId}&college=${college}`;
    
    // Show the modal
    const myModal = document.getElementById('myModal');
    myModal.style.display = 'block';
}

// Function to close modals
function closeModal() {
    const modal = document.getElementById('myModal');
    modal.style.display = 'none';
    
    // Clear the iframe source when closing the modal
    const modalIframe = document.getElementById('modalIframe');
    modalIframe.src = ''; // This prevents the previous content from being displayed when reopened
}
</script>


    <script>
function viewTaskDetails(semesterId, officeHeadId, college) {
    // Open a new tab with detailed view, passing the additional parameters
    window.open('dpcr_forms.php?semester_id=' + semesterId + '&office_head_id=' + officeHeadId + '&college=' + college, '_blank');
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
                
                    console.log('approveTask ' + xhr.responseText);
                    // response = JSON.parse(xhr.responseText);
                    console.log(response.message);

                    // Rex send email
                    // Using the fetch API to call PHP script
                    fetch('../feature_experiment/notify_users/includes/send_email_async.php', {
                        method: 'POST', // or 'POST' if you're sending data
                        body: JSON.stringify({ message: response.message, user_id: response.office_head_id }),
                    }) 
                    .then(response => response.text())
                    .then(data => { 
                        console.log(data);
                    })

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

                console.log('setPending ' + xhr.responseText);
                // response = JSON.parse(xhr.responseText);
                console.log(response.message);

                // Rex send email
                // Using the fetch API to call PHP script
                fetch('../feature_experiment/notify_users/includes/send_email_async.php', {
                    method: 'POST', // or 'POST' if you're sending data
                    body: JSON.stringify({ message: response.message, user_id: response.office_head_id }),
                }) 
                .then(response => response.text())
                .then(data => { 
                    console.log(data);
                })

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

                console.log('approveFinal ' + xhr.responseText);
                // response = JSON.parse(xhr.responseText);
                console.log(response.message);

                // Rex send email
                // Using the fetch API to call PHP script
                fetch('../feature_experiment/notify_users/includes/send_email_async.php', {
                    method: 'POST', // or 'POST' if you're sending data
                    body: JSON.stringify({ message: response.message, user_id: response.office_head_id }),
                }) 
                .then(response => response.text())
                .then(data => { 
                    console.log(data);
                })

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

                console.log('setFinalPending ' + xhr.responseText);
                // response = JSON.parse(xhr.responseText);
                console.log(response.message);

                // Rex send email
                // Using the fetch API to call PHP script
                fetch('../feature_experiment/notify_users/includes/send_email_async.php', {
                    method: 'POST', // or 'POST' if you're sending data
                    body: JSON.stringify({ message: response.message, user_id: response.office_head_id }),
                }) 
                .then(response => response.text())
                .then(data => { 
                    console.log(data);
                })

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
<script>
            function viewipcrtask(semesterId) {
            var form = document.createElement('form');
            form.method = 'post';
            form.action = 'view_ipcr_tasks_for_vpaa.php';
            form.target = '_blank';

            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'semester_id';
            input.value = semesterId;
            form.appendChild(input);

            document.body.appendChild(form);
            form.submit();
        }

        // Close dropdown when clicking outside of it
        document.addEventListener('click', function(event) {
            var dropdowns = document.querySelectorAll('.dropdown');
            dropdowns.forEach(function(dropdown) {
                if (!dropdown.contains(event.target) && !event.target.closest('.kebab-menu')) {
                    dropdown.style.display = 'none';
                }
            });
        });
</script>
</body>
</html>

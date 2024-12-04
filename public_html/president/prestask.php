<?php
session_start();

// Include the database connection file
include '../dbconnections/config.php'; // Replace with the correct path to your database connection file

// Retrieve office head idnumber from session
$idnumber = $_SESSION['idnumber']; // Assuming this is correctly set in your login process

if (empty($idnumber) || $idnumber == '0') {
    die("Error: Office head ID is not set properly.");
}

// Fetch semester tasks that are approved by VP (vpapproval = 1)
$semester_stmt = $conn->prepare("SELECT * FROM semester_tasks WHERE vpapproval = 1 ORDER BY created_at DESC");
$semester_stmt->execute();
$semester_result = $semester_stmt->get_result();
$semesters = $semester_result->fetch_all(MYSQLI_ASSOC);
$semester_stmt->close();

// Function to update president approval and comment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task_id']) && isset($_POST['action'])) {
    $task_id = $_POST['task_id'];
    $action = $_POST['action'];
    
    // Fetch the office_head_id and semester_name for the task
    $task_stmt = $conn->prepare("SELECT office_head_id, semester_name, college FROM semester_tasks WHERE semester_id = ?");
    $task_stmt->bind_param("i", $task_id);
    $task_stmt->execute();
    $task_stmt->bind_result($office_head_id, $semester_name, $college);
    $task_stmt->fetch();
    $task_stmt->close();

    if ($action === 'approve') {
        $presidentapproval = 1; // Approve
        $press_first_created_at = date('Y-m-d H:i:s', strtotime('+8 hours')); // Current time in Philippines timezone
        $status = 1; // Status for notifications

        $message = "President Gived Intitial Approval for Semester Task : ";

    } elseif ($action === 'pending') {
        $presidentapproval = null; // Store NULL for disapproval
        $press_first_created_at = null; // Set to NULL
        $status = null; // Status for notifications

        $message = "President Revoked Intitial Approval for Semester Task : ";

    } elseif ($action === 'approve_final') {
        $final_approval = 1; // Approve final
        $press_final_created_at = date('Y-m-d H:i:s', strtotime('+8 hours')); // Current time in Philippines timezone
        $update_stmt = $conn->prepare("UPDATE semester_tasks SET final_approval_press = ?, press_final_created_at = ? WHERE semester_id = ?");
        $update_stmt->bind_param("ssi", $final_approval, $press_final_created_at, $task_id);
        $update_stmt->execute();
        $update_stmt->close();

        $message = "President Gived Final Approval for Semester Task : ";

        echo json_encode([
            'status' => 'success',
            'office_head_id' => $office_head_id,
            'message' => $message . "\"" . $semester_name . "\"",
        ]);
        exit();
    } elseif ($action === 'final_pending') {
        $final_approval = 0; // Set final to pending
        $press_final_created_at = null; // Set to NULL
        $update_stmt = $conn->prepare("UPDATE semester_tasks SET final_approval_press = ?, press_final_created_at = ? WHERE semester_id = ?");
        $update_stmt->bind_param("ssi", $final_approval, $press_final_created_at, $task_id);
        $update_stmt->execute();
        $update_stmt->close();

        $message = "President Revoked Final Approval for Semester Task : ";

        echo json_encode([
            'status' => 'success',
            'office_head_id' => $office_head_id,
            'message' => $message . "\"" . $semester_name . "\"",
        ]);
        exit();
    }

    // Update the task approval status in the main database
    $update_stmt = $conn->prepare("UPDATE semester_tasks SET presidentapproval = ?, press_first_created_at = ? WHERE semester_id = ?");
    $update_stmt->bind_param("ssi", $presidentapproval, $press_first_created_at, $task_id);
    $update_stmt->execute();
    $update_stmt->close();

    // // Fetch the office_head_id and semester_name for the task
    // $task_stmt = $conn->prepare("SELECT office_head_id, semester_name, college FROM semester_tasks WHERE semester_id = ?");
    // $task_stmt->bind_param("i", $task_id);
    // $task_stmt->execute();
    // $task_stmt->bind_result($office_head_id, $semester_name, $college);
    // $task_stmt->fetch();
    // $task_stmt->close();

    // Insert a record into the notifications table
    $notification_stmt = $conn->prepare("INSERT INTO presapproval (task_id, semester_name, officehead_id_number, college, status) VALUES (?, ?, ?, ?, ?)");
    $notification_stmt->bind_param("isssi", $task_id, $semester_name, $office_head_id, $college, $status);
    $notification_stmt->execute();
    $notification_stmt->close();
    
    echo json_encode([
        'status' => 'success notification', 
        'office_head_id' => $office_head_id,
        'message' => $message . "\"" . $semester_name . "\"",
    ]);
    exit();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>President Tasks</title>
    <style>
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
            background-color: #f4f4f4;
        }
        .pending {
            color: blue;
        }
        .approve {
            color: rgb(32, 244, 32);
        }
        .disapprove {
            color: red;
        }
        .button-container {
    display: flex;
    flex-wrap: wrap; /* Allows buttons to wrap if the screen is narrow */
    gap: 10px; /* Adds space between buttons */
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
    background-color:  #28a745; /* Blue for final approval */
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

    <h2>Approved Semester Tasks</h2>

    <table>
        <thead>
            <tr>
                <th>Semester Name</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>College</th>
                <th>First Approval</th>
                <th>Final Approval</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($semesters as $semester): ?>
                <tr id="task-row-<?php echo htmlspecialchars($semester['semester_id']); ?>">
                    <td><?php echo htmlspecialchars($semester['semester_name']); ?></td>
                    <td><?php echo date('F d, Y', strtotime($semester['start_date'])); ?></td>
                    <td><?php echo date('F d, Y', strtotime($semester['end_date'])); ?></td>
                    <td><?php echo htmlspecialchars($semester['college']); ?></td>
                    <td>
                        <?php 
                        $president_approval = $semester['presidentapproval'];
                        if ($president_approval === null || $president_approval === '') {
                            echo '<span class="pending">Pending</span>';
                        } elseif ($president_approval == '0') {
                            echo '<span class="pending">Pending</span>'; // Change to 'Pending'
                        } else {
                            echo '<span class="approve">Approved</span>';
                        }
                        ?>
                    </td>
                    <td>
                        <?php 
                        $final_approval = $semester['final_approval_press'];
                        if ($final_approval === null || $final_approval === '') {
                            echo '<span class="pending">Pending</span>';
                        } elseif ($final_approval == '0') {
                            echo '<span class="pending">Pending</span>'; // Change to 'Pending'
                        } else {
                            echo '<span class="approve">Approved</span>';
                        }
                        ?>
                    </td>
                    <td class="button-group">
                        <div class="button-container">
                            <?php if ($semester['users_final_approval'] != 1): ?>
                                <!-- Only show the form and button if users_final_approval is not 1 -->
                                <form method="post" id="approve-form-<?php echo htmlspecialchars($semester['semester_id']); ?>" style="display:inline;">
                                    <input type="hidden" name="task_id" value="<?php echo htmlspecialchars($semester['semester_id']); ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="btn approve-btn">First Approval</button>
                                </form>
                                <button type="button" class="btn pending-btn" onclick="pending('<?php echo htmlspecialchars($semester['semester_id']); ?>')">Remove Approval</button>
                            <?php endif; ?>

                            <?php if ($semester['final_approval_vpaa'] == 1): // Check if vp_final_approval is 1 ?>
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
        function viewTaskDetails(semesterId) {
            // Open a new tab with detailed view
            window.open('viewsemestertasks.php?semester_id=' + semesterId, '_blank');
        }

        function showNotification(message, isSuccess = true, isPending = false) {
            var notification = document.getElementById('notification');
            notification.innerHTML = message;

            // Set background color based on the notification type
            if (isPending) {
                notification.style.backgroundColor = 'blue'; // Blue for pending
            } else {
                notification.style.backgroundColor = isSuccess ? '#28a745' : '#dc3545'; // Green for success, Red for error
            }

            notification.style.display = 'block';

            // Hide after 3 seconds
            setTimeout(function() {
                notification.style.display = 'none';
            }, 3000);
        }

        function pending(taskId) {
            // Create form data to send
            var formData = new FormData();
            formData.append('task_id', taskId);
            formData.append('action', 'pending'); // Change 'disapprove' to 'pending' if needed

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'prestask.php', true);

            xhr.onload = function() {
                if (xhr.status === 200) {
                    showNotification('Task marked as pending successfully!', false , true); // Set isPending to true

                    console.log('pending ' + xhr.responseText);
                    response = JSON.parse(xhr.responseText);
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

                    var taskRow = document.getElementById('task-row-' + taskId);
                    var statusCell = taskRow.querySelector('td:nth-child(5)'); // Adjust this based on your table structure

                    statusCell.innerHTML = '<span class="pending">Pending</span>';
                } else {
                    showNotification('An error occurred while disapproving the task.', false);
                }
            };

            xhr.send(formData);
        }

        // Handle approve form submission via AJAX
        document.addEventListener('submit', function(event) {
            if (event.target && event.target.matches('[id^="approve-form-"]')) {
                event.preventDefault(); // Prevent the form from submitting the default way

                var formData = new FormData(event.target);

                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'prestask.php', true);

                xhr.onload = function() {
                    if (xhr.status === 200) {
                        showNotification('Task signed successfully!', true);

                        console.log('submit ' + xhr.responseText);
                        response = JSON.parse(xhr.responseText);
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

                        var taskId = formData.get('task_id');
                        var taskRow = document.getElementById('task-row-' + taskId);
                        var statusCell = taskRow.querySelector('td:nth-child(5)'); // Adjust this based on your table structure

                        statusCell.innerHTML = '<span class="approve">Approved</span>';
                    } else {
                        showNotification('An error occurred while approving the task.', false);
                    }
                };

                xhr.send(formData);
            }
        });

        function approveFinal(taskId) {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'prestask.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

            xhr.onload = function () {
                if (xhr.status === 200) {
                    var response = JSON.parse(xhr.responseText);
                    if (response.status === 'success') {

                        console.log('approveFinal ' + xhr.responseText);
                        response = JSON.parse(xhr.responseText);
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

                        // Update the task row status for final approval
                        var taskRow = document.getElementById('task-row-' + taskId);
                        var statusCell = taskRow.querySelector('td:nth-child(6)'); // Adjust index if necessary
                        statusCell.innerHTML = '<span class="approve">Approved</span>'; // Update to 'Final Approved'

                        // Show success notification
                        showNotification('Final task approved successfully!');
                    } else {
                        showNotification('An error occurred: ' + response.error, false);
                    }
                } else {
                    showNotification('An error occurred while approving the final task.', false);
                }
            };

            xhr.send('task_id=' + encodeURIComponent(taskId) + '&action=approve_final');
        }

        function setFinalPending(taskId) {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'prestask.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

            xhr.onload = function () {
                if (xhr.status === 200) {
                    var response = JSON.parse(xhr.responseText);
                    if (response.status === 'success') {

                        console.log('setFinalPending ' + xhr.responseText);
                        response = JSON.parse(xhr.responseText);
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
                        
                        // Update the task row status for final pending
                        var taskRow = document.getElementById('task-row-' + taskId);
                        var statusCell = taskRow.querySelector('td:nth-child(6)'); // Adjust index if necessary
                        statusCell.innerHTML = '<span class="pending">Pending</span>'; // Update to 'Final Pending'

                        // Show success notification with blue background for pending
                        showNotification('Final task set to pending successfully!', false, true); // Set isPending to true
                    } else {
                        showNotification('An error occurred: ' + response.error, false);
                    }
                } else {
                    showNotification('An error occurred while setting the final task to pending.', false);
                }
            };

            xhr.send('task_id=' + encodeURIComponent(taskId) + '&action=final_pending');
        }
    </script>
    <script>
            function viewipcrtask(semesterId) {
            var form = document.createElement('form');
            form.method = 'post';
            form.action = 'view_ipcr_tasks_for_president.php';
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
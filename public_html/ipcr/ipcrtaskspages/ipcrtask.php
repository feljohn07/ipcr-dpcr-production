<?php
session_start();
include '../../dbconnections/config.php'; // Database connection

// Initialize message variable
$message = '';

// Check if designation is null or empty
if (empty($_SESSION['designation']) && empty($_SESSION['position'])) {
    $message = "Set your Designation and Academic Rank on the Profile First."; // Updated message
} elseif (empty($_SESSION['designation'])) {
    $message = "Set your Designation on the Profile First.";
} elseif (empty($_SESSION['position'])) {
    $message = "Set your Academic Rank on the Profile First."; // This line is already correct
}

// If a message is set, display it and exit
if (!empty($message)) {
    echo "<div style='text-align: center; margin-top: 50px;'>
            <h2>$message</h2>
          </div>";
    exit; // Stop further execution of the script
}

// Retrieve logged-in user's idnumber
$current_user_idnumber = $_SESSION['idnumber'];

// Fetch assigned tasks
$assigned_stmt = $conn->prepare("
    SELECT 
        ta.id,
        ta.task_type,
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
        ta.target,  -- Fetch target directly from task_assignments
        ta.status,
        ta.message,
        ta.created_at,
        ta.due_date,  -- Fetch due_date from task_assignments
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
        ta.assignuser = ? AND ta.status = 'pending'  
    ORDER BY 
        ta.created_at DESC  -- Order by created_at in descending order
");
$assigned_stmt->bind_param("s", $current_user_idnumber);
$assigned_stmt->execute();
$assigned_result = $assigned_stmt->get_result();
$assigned_tasks = $assigned_result->fetch_all(MYSQLI_ASSOC);
$assigned_stmt->close();


$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assigned Tasks</title>
    <style>
        .tabledata {
            margin: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            background-color: #fff;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }

        table th, table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            border-right: 1px solid #ddd;
            word-wrap: break-word; /* Ensures long words break */
            max-width: 300px; /* Adjust as needed */
        }

        table th {
            background-color: #4CAF50;
            color: white;
            border-right: 1px solid #ddd;
            text-align: center;
        }

        table tbody tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        table tbody tr:hover {
            background-color: #ddd;
        }
    </style>
</head>
<body>
    <div class="headwer">
        <h2>Assigned Tasks</h2>
    </div>

    <div class="tabledata">
        <h3>Your Tasks:</h3>
        <?php if (!empty($assigned_tasks)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Outputs</th>
                        <th>Success Indicator(Target + Measures)</th>
                        <th>Target</th>
                        <th>Due date</th> <!-- New column for Deadline -->
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($assigned_tasks as $task): ?>
                        <tr>
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
                            <td><?php echo htmlspecialchars($task['target']); ?></td>
                            <td><?php echo date('F d, Y', strtotime($task['due_date'])); ?></td>

                            <td>
                                <?php if ($task['status'] === 'pending'): ?>
                                    <button type="button" onclick="approveTask(<?php echo $task['id']; ?>)">Approve</button>
                                    <button type="button" onclick="showDeclineModal(<?php echo $task['id']; ?>)">Decline</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No tasks assigned.</p>
        <?php endif; ?>
    </div>

    <div id="declineModal" style="display:none; position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); background-color:white; padding:20px; box-shadow:0 0 10px rgba(0,0,0,0.5); z-index:1000;">
        <form id="declineForm">
            <input type="hidden" name="task_id" id="modalTaskId">
            <input type="hidden" name="action" value="decline">
            <label for="declineMessage">Message:</label>
            <textarea name="message" id="declineMessage" rows="4" cols="50"></textarea>
            <br>
            <button type="button" onclick="submitDeclineForm()">Submit</button>
            <button type="button" onclick="closeDeclineModal()">Cancel</button>
        </form>
    </div>
    
<!-- Notification Container -->
<?php include '../../notiftext/message.php'; // Database connection ?>

<!-- ipcrconfirmation-model -->
<?php include '../../notiftext/ipcrtaskconfirmodel.php'; // Database connection ?>

      <script>
        function showDeclineModal(taskId) {
    document.getElementById('modalTaskId').value = taskId;
    document.getElementById('declineModal').style.display = 'block';
}

function closeDeclineModal() {
    document.getElementById('declineModal').style.display = 'none';
}

function submitDeclineForm() {
    var formData = new FormData(document.getElementById('declineForm'));
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'ipcrtaskspages/update_task_status.php', true);
    xhr.onload = function () {
        var notification = document.getElementById('notification');
        if (xhr.status === 200) {
            localStorage.setItem('notificationMessage', 'Task declined successfully.');
            localStorage.setItem('notificationError', 'false'); // No error
            location.reload(); // Reload the page to reflect changes
        } else {
            localStorage.setItem('notificationMessage', 'An error occurred while declining the task.');
            localStorage.setItem('notificationError', 'true'); // Error occurred
            location.reload(); // Reload the page to reflect changes
        }
    };
    xhr.send(formData);
}

// Check if the page should show the notification
var notification = document.getElementById('notification');
if (localStorage.getItem('notificationMessage')) {
    notification.textContent = localStorage.getItem('notificationMessage');
    if (localStorage.getItem('notificationError') === 'true') {
        notification.style.backgroundColor = '#dc3545'; // Red background
    } else {
        notification.style.backgroundColor = '#28a745'; // Green background
    }
    notification.style.color = 'white';
    notification.style.display = 'block'; // Show the notification

    // Hide notification after 3 seconds
    setTimeout(function () {
        notification.style.display = 'none';
    }, 3000);

    // Remove the notification message and error flag
    localStorage.removeItem('notificationMessage');
    localStorage.removeItem('notificationError');
}

function approveTask(taskId) {
    var ipcrconfirmationModel = document.getElementById('ipcrconfirmation-model');
    ipcrconfirmationModel.style.display = 'block'; // Show the modal

    var ipcrOkButton = document.getElementById('ipcr-ok-button');
    ipcrOkButton.onclick = function() {
        var formData = new FormData();
        formData.append('task_id', taskId);
        formData.append('action', 'approve');
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'ipcrtaskspages/update_task_status.php', true);
        xhr.onload = function () {
            if (xhr.status === 200) {
                localStorage.setItem('notificationMessage', 'Task approved successfully.');
                localStorage.setItem('notificationError', 'false'); // No error
            } else {
                localStorage.setItem('notificationMessage', 'An error occurred while approving the task.');
                localStorage.setItem('notificationError', 'true'); // Error occurred
            }
            location.reload(); // Reload the page to reflect changes
        };
        xhr.send(formData);
        ipcrconfirmationModel.style.display = 'none'; // Hide the modal
    };

    var ipcrCancelButton = document.getElementById('ipcr-cancel-button');
    ipcrCancelButton.onclick = function() {
        ipcrconfirmationModel.style.display = 'none'; // Hide the modal
    };
}

// Check if the page should show the notification
var notification = document.getElementById('notification');
if (localStorage.getItem('notificationMessage')) {
    notification.textContent = localStorage.getItem('notificationMessage');
    if (localStorage.getItem('notificationError') === 'true') {
        notification.style.backgroundColor = '#dc3545'; // Red background
    } else {
        notification.style.backgroundColor = '#28a745'; // Green background
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

</body>
</html>

<?php
// Include the database connection file
include '../../dbconnections/config.php';

// Start the session
session_start();
$idnumber = $_SESSION['idnumber'];
$college = $_SESSION['college'];

// Function to fetch notifications from the task_notification table
function fetchNotificationsFromTaskNotification($conn, $idnumber) {
    $sql = "SELECT 'task_notification' AS source_table, id, semester_name, status, created_at, is_read 
            FROM task_notification 
            WHERE officehead_id_number = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $idnumber);
    $stmt->execute();
    return $stmt->get_result();
}

// Function to fetch notifications from the presapproval table
function fetchNotificationsFromPresapproval($conn, $idnumber) {
    $sql = "SELECT 'presapproval' AS source_table, id, semester_name, status, created_at, is_read 
            FROM presapproval 
            WHERE officehead_id_number = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $idnumber);
    $stmt->execute();
    return $stmt->get_result();
}

// Revised function to fetch notifications from the for_ipcrtask_noty table based on college
function fetchNotificationsFromForIpcrtaskNoty($conn, $college) {
    $sql = "SELECT 'for_ipcrtask_noty' AS source_table, semester_id, task_name, task_type, firstname, lastname, status, created_at, is_read, NULL AS semester_name 
            FROM for_ipcrtask_noty 
            WHERE college = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $college); // Bind the college parameter
    $stmt->execute();
    return $stmt->get_result();
}

// New function to fetch notifications from the task_assignment table
function fetchNotificationsFromTaskAssignment($conn, $idnumber) {
    // Fetch the college of the logged-in user
    $sql = "SELECT college FROM usersinfo WHERE idnumber = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $idnumber);
    $stmt->execute();
    $result = $stmt->get_result();
    $college = $result->fetch_assoc()['college'];

    // Fetch notifications for task assignments based on the user's college
    $sql = "SELECT 'task_assignments' AS source_table, ta.id, ta.target, ta.num_file, ta.assignuser, ui.firstname, ui.lastname, ta.is_read, ta.task_name, ta.task_type, ta.created_at_for_upfiles AS created_at 
            FROM task_assignments ta 
            JOIN usersinfo ui ON ta.assignuser = ui.idnumber 
            WHERE ui.college = ? AND ta.num_file != 0"; // Added condition for num_file
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $college);
    $stmt->execute();
    return $stmt->get_result();
}


// New function to fetch notifications from the ipcrsubmittedtask table based on user's college
function fetchNotificationsFromIpcrSubmittedTask($conn, $idnumber) {
    // Fetch the college of the logged-in user
    $sql = "SELECT college FROM usersinfo WHERE idnumber = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $idnumber);
    $stmt->execute();
    $result = $stmt->get_result();
    $college = $result->fetch_assoc()['college'];

    // Fetch notifications from ipcrsubmittedtask based on the user's college
    $sql = "SELECT 'ipcrsubmittedtask' AS source_table, id_of_semester AS semester_id, name_of_semester AS semester_name, firstname, lastname, is_read, created_at 
            FROM ipcrsubmittedtask 
            WHERE college = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $college);
    $stmt->execute();
    return $stmt->get_result();
}

// Fetch notifications from all tables
$taskNotificationsResult = fetchNotificationsFromTaskNotification($conn, $idnumber);
$presapprovalNotificationsResult = fetchNotificationsFromPresapproval($conn, $idnumber);
$forIpcrtaskNotificationsResult = fetchNotificationsFromForIpcrtaskNoty($conn, $college);
$taskAssignmentNotificationsResult = fetchNotificationsFromTaskAssignment($conn, $idnumber);
$ipcrSubmittedTaskNotificationsResult = fetchNotificationsFromIpcrSubmittedTask($conn, $idnumber);

// Combine the results
$allNotifications = [];

// Store notifications from each table
while ($row = $taskNotificationsResult->fetch_assoc()) $allNotifications[] = $row;
while ($row = $presapprovalNotificationsResult->fetch_assoc()) $allNotifications[] = $row;
while ($row = $forIpcrtaskNotificationsResult->fetch_assoc()) $allNotifications[] = $row;
while ($row = $taskAssignmentNotificationsResult->fetch_assoc()) $allNotifications[] = $row;
while ($row = $ipcrSubmittedTaskNotificationsResult->fetch_assoc()) $allNotifications[] = $row;

$uniqueNotifications = [];
foreach ($allNotifications as $notification) {
    if ($notification['source_table'] === 'ipcrsubmittedtask') {
        $key = $notification['semester_id'] . '_' . $notification['firstname'] . '_' . $notification['lastname'];
        if (!isset($uniqueNotifications[$key])) {
            $uniqueNotifications[$key] = $notification; // Store unique notification
        }
    } else {
        $uniqueNotifications[] = $notification; // Store other notifications directly
    }
}

// Sort notifications by created_at
// Sort notifications by created_at before displaying
usort($uniqueNotifications, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification Page</title>
    <style>
        .notification-container {
            width: 100%;
            max-width: 1000px;
            margin: 0 auto;
        }

        .notification {
            background-color: #fff;
            padding: 15px;
            margin-bottom: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 4px;
            border-left: 5px solid; /* Border color will be set dynamically */
            position: relative; /* For positioning the date */
        }

        .notification p {
            font-size: 0.9em;
            color: #555;
            margin: 0;
            line-height: 1.5;
            font-size: 13px;
        }

        .notification p span {
            font-weight: bold;
        }

        .notification .date {
            position: absolute;
            right: 15px;
            bottom: 15px;
            font-size: 0.8em;
            color: #777;
        }

        .status-approved {
            color: green;
        }

        .status-declined {
            color: red; /* Color for declined status */
        }

        .status-pending {
            color: blue; /* Optional: Color for pending status */
        }
    </style>
</head>
<body>
<div class="notification-container">
<?php if (count($uniqueNotifications) > 0): ?>
    <?php foreach ($uniqueNotifications as $row): ?>
            <?php
            $borderColor = '#ccc'; // Default border color
            $statusLabel = ''; // Default status label
            $statusClass = ''; // Default status class

            if ($row['source_table'] === 'for_ipcrtask_noty') {
                if (strcasecmp($row['status'], 'approved') == 0) $borderColor = '#4CAF50';
                elseif (strcasecmp($row['status'], 'declined') == 0) $borderColor = 'red';
            } elseif ($row['source_table'] === 'task_notification' || $row['source_table'] === 'presapproval') {
                if ($row['status'] === 1) {
                    $borderColor = '#4CAF50';
                    $statusLabel = 'signed';
                    $statusClass = 'status-approved';
                } elseif ($row['status'] === NULL) {
                    $borderColor = 'blue';
                    $statusLabel = 'unsigned';
                    $statusClass = 'status-pending';
                }
            } elseif ($row['source_table'] === 'task_assignments') {
                $borderColor = '#aaa';
            }

            $backgroundColor = ($row['is_read'] == 0) ? '#e9ecef' : '#fff';
            ?>
            <div class="notification" style="border-left-color: <?php echo htmlspecialchars($borderColor); ?>; background-color: <?php echo htmlspecialchars($backgroundColor); ?>;">
            <?php
                // Inside the foreach loop for displaying notifications
                if ($row['source_table'] === 'for_ipcrtask_noty') {
                    // Get the task name and limit it to 5 words
                    $taskName = htmlspecialchars($row['task_name']);
                    $taskNameWords = explode(' ', $taskName); // Split the task name into words
                    $limitedTaskName = implode(' ', array_slice($taskNameWords, 0, 5)); // Get the first 5 words

                    // Check if the task name was truncated and add ellipsis if needed
                    if (count($taskNameWords) > 5) {
                        $limitedTaskName .= '...'; // Add ellipsis if it exceeds the limit
                    }

                    echo '<p>' . htmlspecialchars($row['firstname']) . ' has been ' . htmlspecialchars($row['status']) . 
                        ' for the task "' . $limitedTaskName . '" under the Area of Evaluation of ' . 
                        htmlspecialchars($row['task_type']) . '.</p>';

                } elseif ($row['source_table'] === 'task_assignments') {
                    $target = $row['target'];
                    $num_file = $row['num_file'];

                    // Calculate the progress percentage as a whole number
                    $progressPercentage = $target > 0 ? (int)(($num_file / $target) * 100) : 0; // Avoid division by zero

                    // Get the task name and limit it to 5 words
                    $taskName = htmlspecialchars($row['task_name']);
                    $taskNameWords = explode(' ', $taskName); // Split the task name into words
                    $limitedTaskName = implode(' ', array_slice($taskNameWords, 0, 5)); // Get the first 5 words

                    // Check if the task name was truncated and add ellipsis if needed
                    if (count($taskNameWords) > 5) {
                        $limitedTaskName .= '...'; // Add ellipsis if it exceeds the limit
                    }

                    // Generate the message
                    echo '<p>The Task: "' . $limitedTaskName . '" has been assigned to ' . 
                        htmlspecialchars($row['firstname']) . ' ' . htmlspecialchars($row['lastname']) . 
                        ' and is now at ' . htmlspecialchars($progressPercentage) . '% progress (' . 
                        htmlspecialchars($num_file) . ' out of ' . htmlspecialchars($target) . ').</p>';

                } elseif ($row['source_table'] === 'task_notification') {
                    echo '<p>The Semester Tasks you submitted <span>' . htmlspecialchars($row['semester_name']) . 
                        '</span> have been <span class="' . htmlspecialchars($statusClass) . '">' . 
                        htmlspecialchars($statusLabel) . '</span><a> by the Immediate Supervisor</a></p>';

                } elseif ($row['source_table'] === 'presapproval') {
                    echo '<p>The Semester Tasks you submitted <span>' . htmlspecialchars($row['semester_name']) . 
                        '</span> have been <span class="' . htmlspecialchars($statusClass) . '">' . 
                        htmlspecialchars($statusLabel) . '</span><a> by the College President</a></p>';

                } elseif ($row['source_table'] === 'ipcrsubmittedtask') {
                    echo '<p>' . htmlspecialchars($row['firstname']) . ' ' . htmlspecialchars($row['lastname']) . 
                        ' submitted task a for the ' . htmlspecialchars($row['semester_name']) . '.</p>';
                }
                ?>

                    <p class="date"><?php echo date('F d, Y, h:i A', strtotime($row['created_at'])); ?></p>
                    </div>
                    <?php endforeach; ?>
                        <?php else: ?>
                <p style="text-align:center ;">No notifications found.</p>
            <?php endif; ?>
        </div>
    </body>
</html>

<?php
// Close the database connection
$conn->close();
?>

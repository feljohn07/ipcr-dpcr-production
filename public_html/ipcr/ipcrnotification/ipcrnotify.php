<?php
// Include the database connection file
include '../../dbconnections/config.php';
include __DIR__ . '/../../feature_experiment/notify_users/includes/notify_user_for_changes.php';


// Assuming the logged-in user's idnumber is stored in a session variable
session_start();
$idnumber = $_SESSION['idnumber']; // Example: '12345'

// Fetch notifications from both task_assignments and deans messages
$sql = "
    SELECT 'task_assignment' AS source_table, id, task_name, status, created_at AS notification_date, target, NULL AS deansmessage, NULL AS deansmessage_created_at, assignment_is_read, NULL AS deansnote_is_read 
    FROM task_assignments 
    WHERE assignuser = ?
    
    UNION ALL
    
    SELECT 'deans_message' AS source_table, id, task_name, NULL AS status, deansmessage_created_at AS notification_date, NULL AS target, deansmessage, deansmessage_created_at, NULL AS assignment_is_read, deansnote_is_read 
    FROM task_assignments 
    WHERE assignuser = ? AND deansmessage IS NOT NULL AND deansmessage != ''
    
UNION ALL

    SELECT 'ipcr_submitted_task' AS source_table, task_id AS id, task_name, NULL AS status, note_created_at AS notification_date, NULL AS target, note_feedback AS deansmessage, note_created_at AS deansmessage_created_at, NULL AS assignment_is_read, note_is_read AS deansnote_is_read 
    FROM ipcrsubmittedtask 
    WHERE idnumber = ? AND note_feedback IS NOT NULL AND note_feedback != ''
    
    ORDER BY notification_date DESC"; // Order by the combined notification_date

$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $idnumber, $idnumber, $idnumber); // Bind the idnumber for all three queries
$stmt->execute();
$result = $stmt->get_result();

function truncate_string($string, $min_limit, $max_limit) {
    $words = explode(' ', $string);
    $word_count = count($words);
    
    if ($word_count > $max_limit) {
        return implode(' ', array_slice($words, 0, $max_limit)) . ' .......';
    } elseif ($word_count > $min_limit) {
        return implode(' ', array_slice($words, 0, $min_limit)) . ' .......';
    }
    
    return $string;
}


function get_all_notifications($user_id, $limit = 5) {

    global $conn;

    // If a user ID is provided, use it in the query
    $query = $limit == null ? "SELECT * FROM dean_rate_and_override_notification WHERE user_id = ? ORDER BY created_at DESC" : "SELECT * FROM dean_rate_and_override_notification WHERE user_id = ? ORDER BY created_at DESC LIMIT " . $limit;
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $user_id); // Bind user_id as an integer
    
    // Execute the query
    $stmt->execute();

    // Fetch the results
    $result = $stmt->get_result();
    $notifications = $result->fetch_all(MYSQLI_ASSOC);

    // Close the statement
    $stmt->close();

    return $notifications;


}

$notifications_limited = get_all_notifications( $_SESSION['idnumber']);
$notifications_all = get_all_notifications( $_SESSION['idnumber'], null);

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
            position: relative; /* For positioning the date */
            border-left: 5px solid; /* Border color will be set dynamically */
        }

        .notification p {
            font-size: 0.9em;
            color: #555;
            margin: 0;
            line-height: 1.5;
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

        .notification-text {
            display: -webkit-box;
            -webkit-line-clamp: 1; /* Limit to 1 line */
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            margin: 0;
        }

        .notification-text.expanded {
            -webkit-line-clamp: unset; /* Remove line clamp */
            white-space: normal;
        }

        .toggle-button {
            background: none;
            border: none;
            color: blue;
            cursor: pointer;
            padding: 0;
            margin-top: 5px;
            font-size: 0.9em;
            text-decoration: underline;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
            padding-top: 50px;
        }

        .modal-content {
            background-color: #fff;
            margin: auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 600px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }

        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
        }

        .modal-body {
            max-height: 400px;
            overflow-y: auto;
            margin-top: 10px;
        }


    </style>
</head>
<body>
<div class="notification-container">


    <p>Approval and Ratings Notifications <button onclick="openModal()">View All</button></p>
    <?php foreach ($notifications_limited as $key => $notification):?>
   
    <div class="notification" style="border-left-color: green;">
        <p class="date"><?php echo date('F d, Y, h:i A', strtotime($notification['created_at'])); ?></p>
        <p class="notification-text">
            <?php echo $notification['notification']; ?>
        </p>
        <button class="toggle-button" onclick="toggleNotification(this)">Show More</button>
        <script>
            function toggleNotification(button) {
                const textElement = button.previousElementSibling;
                const isExpanded = textElement.classList.toggle('expanded');
                button.textContent = isExpanded ? 'Show Less' : 'Show More';
            }
        </script>
    </div>
    
    <?php endforeach; ?>
    
    <p>Task's Notifications</p>
    <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <?php if ($row['source_table'] == 'task_assignment'): ?>
                <?php $notificationColor = $row['assignment_is_read'] == 0 ? '#f0f0f0' : '#fff'; // Light gray if not read ?>
                <div class="notification" style="border-left-color: blue; background-color: <?php echo $notificationColor; ?>;">
                    <p class="date"><?php echo date('F d, Y, h:i A', strtotime($row['notification_date'])); ?></p>
                    <p>You have been assigned a task: <strong><?php echo htmlspecialchars(truncate_string($row['task_name'], 5, 5)); ?></strong></p>
                </div>
            <?php elseif ($row['source_table'] == 'deans_message' && !empty($row['deansmessage']) && !empty($row['deansmessage_created_at'])): ?>
                <?php $notificationColor = isset($row['deansnote_is_read']) && $row['deansnote_is_read'] == 0 ? '#f0f0f0' : '#fff'; // Light gray if not read ?>
                <div class="notification" style="border-left-color: green; background-color: <?php echo $notificationColor; ?>;">
                    <p class="date"><?php echo date('F d, Y, h:i A', strtotime($row['notification_date'])); ?></p>
                    <p>College Dean has left a note on the task <strong>"<?php echo htmlspecialchars(truncate_string($row['task_name'], 5, 5)); ?>"</strong> : <strong><?php echo htmlspecialchars(truncate_string($row['deansmessage'], 8, 10)); ?></strong></p>
                </div>
            <?php elseif ($row['source_table'] == 'ipcr_submitted_task' && !empty($row['deansmessage']) && !empty($row['deansmessage_created_at'])): ?>
                <?php $notificationColor = isset($row['deansnote_is_read']) && $row['deansnote_is_read'] == 0 ? '#f0f0f0' : '#fff'; // Light gray if not read ?>
                <div class="notification" style="border-left-color: orange; background-color: <?php echo $notificationColor; ?>;">
                    <p class="date"><?php echo date('F d, Y, h:i A', strtotime($row['notification_date'])); ?></p>
                    <p>College Dean has left a note on your IPCR task: "<strong><?php echo htmlspecialchars(truncate_string($row['task_name'], 5, 5)); ?></strong><strong>" : <?php echo htmlspecialchars(truncate_string($row['deansmessage'], 5, 5)); ?></strong></p>
                </div>
            <?php endif; ?>
        <?php endwhile; ?>
    <?php else: ?>
        <p>No notifications available.</p>
    <?php endif; ?>

    
</div>

<!-- Modal Structure -->
<div id="notificationModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>All Notifications</h2>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <div class="modal-body">
            <?php foreach ($notifications_all as $notification): ?>
                <div class="notification" style="border-left-color: green;">
                    <p class="date"><?php echo date('F d, Y, h:i A', strtotime($notification['created_at'])); ?></p>
                    <p class="notification-text">
                        <?php echo $notification['notification']; ?>
                    </p>
                    <button class="toggle-button" onclick="toggleNotification(this)">Show More</button>
                    <script>
                        function toggleNotification(button) {
                            const textElement = button.previousElementSibling;
                            const isExpanded = textElement.classList.toggle('expanded');
                            button.textContent = isExpanded ? 'Show Less' : 'Show More';
                        }
                    </script>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
    function openModal() {
        document.getElementById("notificationModal").style.display = "block";
    }

    function closeModal() {
        document.getElementById("notificationModal").style.display = "none";
    }

    // Close the modal when clicking outside of it
    window.onclick = function(event) {
        const modal = document.getElementById("notificationModal");
        if (event.target === modal) {
            modal.style.display = "none";
        }
    };
</script>
</body>
</html>
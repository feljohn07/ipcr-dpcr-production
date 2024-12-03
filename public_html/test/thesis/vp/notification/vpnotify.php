<?php
session_start(); 
// Database connection
include '../../dbconnections/config.php'; // Replace with the correct path to your database connection file

// Query to fetch data
$sql = "SELECT semester_id, semester_name, office_head_id, college, created_at, is_read FROM semester_task_logs ORDER BY created_at DESC";
$result = $conn->query($sql);

// Check if there are results
$notifications = [];
if ($result->num_rows > 0) {
    // Store data of each row
    while($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
} else {
    $notifications = []; // No notifications found
}

// Close connection
$conn->close();
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
    </style>
</head>
<body>
    <div class="notification-container">
        <?php
        // Display notifications
        if (count($notifications) > 0) {
            foreach ($notifications as $notification) {
                // Set the notification background color based on is_read value
                $bgColor = $notification["is_read"] == 0 ? '#d3d3d3' : '#fff'; // Gray for unread, white for read
                echo '<div class="notification" style="background-color: ' . $bgColor . '; border-color: #007bff;">'; 
                // You can set a different color for each notification if needed
                echo '<p>The ' . htmlspecialchars($notification["college"]) . ' submitted a Semester Task titled "'. htmlspecialchars($notification["semester_name"]) .'"</p>';
                echo '<p class="date">' . htmlspecialchars(date('F d, Y, h:i A', strtotime($notification["created_at"])));
                echo '</div>';
            }
        } else {
            echo '<p>No notifications found.</p>';
        }
        ?>
    </div>
</body>
</html>

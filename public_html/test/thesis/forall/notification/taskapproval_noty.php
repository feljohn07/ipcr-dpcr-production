<?php
session_start();
include '../../dbconnections/config.php'; // Replace with the correct path to your database connection file

// Retrieve office head idnumber from session
$office_head_id = $_SESSION['idnumber']; // Assuming this is correctly set in your login process

if (empty($office_head_id) || $office_head_id == '0') {
    die("Error: Office head ID is not set properly.");
}

// Fetch notifications for the office head
$notification_stmt = $conn->prepare("SELECT * FROM task_notifications WHERE office_head_id = ? ORDER BY created_at DESC");
$notification_stmt->bind_param("s", $office_head_id);
$notification_stmt->execute();
$notification_result = $notification_stmt->get_result();
$notifications = $notification_result->fetch_all(MYSQLI_ASSOC);
$notification_stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications</title>
    <style>
        .notification {
            border: 1px solid #ccc;
            padding: 10px;
            margin-bottom: 10px;
            background-color: #f4f4f4;
        }
        .unread {
            font-weight: bold;
        }
        .read {
            font-weight: normal;
        }
        .timestamp {
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <h2>Your Notifications</h2>

    <?php if (count($notifications) > 0): ?>
        <?php foreach ($notifications as $notification): ?>
            <div class="notification <?php echo htmlspecialchars($notification['status']); ?>">
                <p><?php echo htmlspecialchars($notification['notification_text']); ?></p>
                <p class="timestamp"><?php echo date('F d, Y h:i A', strtotime($notification['created_at'])); ?></p>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No notifications at this time.</p>
    <?php endif; ?>
</body>
</html>

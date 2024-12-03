<?php
// Assuming you have a connection to the database stored in $conn

// Function to count unread notifications
function countUnreadNotifications($conn) {
    // Prepare SQL to count all unread notifications from semester_task_logs
    $sql = "SELECT COUNT(*) as unread_count FROM semester_task_logs WHERE pres_is_read = 0";
    $stmt = $conn->prepare($sql); // Prepare the statement
    $stmt->execute(); // Execute the statement
    $result = $stmt->get_result(); // Get the result

    // Fetch the unread count
    $unreadCount = $result->fetch_assoc()['unread_count'];

    return $unreadCount; // Return the total unread count
}

// Get total unread notifications
$totalUnreadNotifications = countUnreadNotifications($conn);

// Close the connection after all operations are done
$conn->close();
?>
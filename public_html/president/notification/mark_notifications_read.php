<?php
// Include the database connection file
include '../../dbconnections/config.php';

// Start the session
session_start();
$idnumber = $_SESSION['idnumber']; // Example: '12345'

// Function to mark all notifications as read
function markNotificationsRead($conn) {
    // Prepare SQL to update is_read status
    $sql = "UPDATE semester_task_logs SET pres_is_read = 1 WHERE pres_is_read = 0";
    
    // Prepare and execute the statement
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->execute(); // Execute the update query
        $stmt->close(); // Close the statement
    } else {
        // Handle error if needed
        echo "Error: " . $conn->error;
    }
}

// Call the function to mark notifications as read
markNotificationsRead($conn);

// Close the database connection
$conn->close();
?>

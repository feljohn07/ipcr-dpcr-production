<?php 
require __DIR__ . '/../../../dbconnections/config.php';

function get_all_notificationss($user_id) {

    global $conn;

        // If a user ID is provided, use it in the query
    if ($user_id) {
        $query = "SELECT * FROM dean_rate_and_override_notification WHERE user_id = ? LIMIT 5";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id); // Bind user_id as an integer
    } else {
        $query = "SELECT * FROM dean_rate_and_override_notification ";
        $stmt = $conn->prepare($query);
    }
    
    // Execute the query
    $stmt->execute();

    // Fetch the results
    $result = $stmt->get_result();
    $notifications = $result->fetch_all(MYSQLI_ASSOC);

    // Close the statement
    $stmt->close();

    return $notifications;


}

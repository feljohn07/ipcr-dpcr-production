<?php
// Get the user ID number from the session
$idnumber = $_SESSION['idnumber'];

// Count unread notifications
function countUnreadNotifications($conn, $idnumber) {
    $unreadCount = 0;

    // Count from task_notification
    $sql = "SELECT COUNT(*) as unread_count FROM task_notification WHERE officehead_id_number = ? AND is_read = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $idnumber);
    $stmt->execute();
    $result = $stmt->get_result();
    $unreadCount += $result->fetch_assoc()['unread_count'];

    // Count from presapproval
    $sql = "SELECT COUNT(*) as unread_count FROM presapproval WHERE officehead_id_number = ? AND is_read = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $idnumber);
    $stmt->execute();
    $result = $stmt->get_result();
    $unreadCount += $result->fetch_assoc()['unread_count'];

    // Count from for_ipcrtask_noty
    $sql = "SELECT COUNT(*) as unread_count FROM for_ipcrtask_noty WHERE is_read = 0";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    $unreadCount += $result->fetch_assoc()['unread_count'];

    // Count from task_assignments for users from the same college
    $sql = "SELECT COUNT(*) as unread_count 
            FROM task_assignments ta 
            JOIN usersinfo ui ON ta.assignuser = ui.idnumber 
            WHERE ta.is_read = 0 
            AND ui.college = (SELECT college FROM usersinfo WHERE idnumber = ?) 
            AND ta.num_file != 0"; // Exclude where num_file is 0

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $idnumber); // Bind the user's ID number
    $stmt->execute(); // Execute the prepared statement
    $result = $stmt->get_result(); // Get the result set
    $unreadCount += $result->fetch_assoc()['unread_count']; // Increment the unread count

        // Count from ipcrsubmittedtask for users from the same college
        $sql = "SELECT COUNT(DISTINCT it.idnumber, it.id_of_semester) as unread_count 
                FROM ipcrsubmittedtask it 
                JOIN usersinfo ui ON it.college = ui.college 
                WHERE it.is_read = 0 
                AND ui.idnumber = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $idnumber); // Bind the user's ID number
        $stmt->execute(); // Execute the prepared statement
        $result = $stmt->get_result(); // Get the result set
        $unreadCount += $result->fetch_assoc()['unread_count']; // Increment the unread count


    return $unreadCount; // Return the total unread count

    }

// Get total unread notifications
$totalUnreadNotifications = countUnreadNotifications($conn, $idnumber);
?>

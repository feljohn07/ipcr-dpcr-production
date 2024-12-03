<?php
// Get the user ID number from the session
$idnumber = $_SESSION['idnumber']; // Example: '12345'

// Count unread notifications
function countUnreadNotifications($conn, $idnumber) {
    $unreadCount = 0;

    // Count from task_assignments where assignment_is_read is 0 and status is 'pending'
    $sql = "SELECT COUNT(*) as unread_count 
            FROM task_assignments 
            WHERE assignuser = ? AND assignment_is_read = 0 AND status = 'pending'"; // Added status condition
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $idnumber);
    $stmt->execute();
    $result = $stmt->get_result();
    $unreadCount += $result->fetch_assoc()['unread_count'];

    // Count from dean's messages where deansmessage is not NULL or empty
    $sql = "SELECT COUNT(*) as unread_count 
            FROM task_assignments 
            WHERE assignuser = ? AND deansmessage IS NOT NULL AND deansmessage != '' AND deansnote_is_read = 0"; // Check for non-empty deansmessage
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $idnumber);
    $stmt->execute();
    $result = $stmt->get_result();
    $unreadCount += $result->fetch_assoc()['unread_count'];

    // Count from ipcrsubmittedtask where note_is_read is 0
    $sql = "SELECT COUNT(*) as unread_count 
            FROM ipcrsubmittedtask 
            WHERE idnumber = ? AND note_is_read = 0"; // Check for unread notes
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $idnumber);
    $stmt->execute();
    $result = $stmt->get_result();
    $unreadCount += $result->fetch_assoc()['unread_count'];

    return $unreadCount; // Return the total unread count
}


// Get total unread notifications
$totalUnreadNotifications = countUnreadNotifications($conn, $idnumber);
?>
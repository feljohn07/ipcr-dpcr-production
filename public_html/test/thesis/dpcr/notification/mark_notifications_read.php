<?php
// Include the database connection file
include '../../dbconnections/config.php';

// Start the session
session_start();
$idnumber = $_SESSION['idnumber']; // Example: '12345'
$college = $_SESSION['college']; // Example: 'College of Science'

// Function to mark notifications as read
function markNotificationsAsRead($conn, $idnumber, $college) {
    // Update task_notification
    $sql = "UPDATE task_notification SET is_read = 1 
            WHERE officehead_id_number = ? AND college = ? AND is_read = 0"; 
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $idnumber, $college);
    $stmt->execute();

    // Update presapproval
    $sql = "UPDATE presapproval SET is_read = 1 
            WHERE officehead_id_number = ? AND college = ? AND is_read = 0"; 
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $idnumber, $college);
    $stmt->execute();

    // Update for_ipcrtask_noty
    $sql = "UPDATE for_ipcrtask_noty SET is_read = 1 
            WHERE college = ? AND is_read = 0"; 
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $college);
    $stmt->execute();

  // Update task_assignments for users from the same college
    $sql = "UPDATE task_assignments ta 
  JOIN usersinfo ui ON ta.assignuser = ui.idnumber 
  SET ta.is_read = 1 
  WHERE ta.is_read = 0 
  AND ui.college = (SELECT college FROM usersinfo WHERE idnumber = ?)"; 

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $idnumber); // Use the user's ID number for filtering
$stmt->execute(); // Execute the prepared statement

    // Update ipcrsubmittedtask for users from the same college
    $sql = "UPDATE ipcrsubmittedtask it 
            JOIN usersinfo ui ON it.college = ui.college 
            SET it.is_read = 1 
            WHERE it.is_read = 0 
            AND ui.idnumber = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $idnumber); // Use the user's ID number for filtering
    $stmt->execute(); // Execute the prepared statement

}

// Call the function to mark notifications as read
markNotificationsAsRead($conn, $idnumber, $college);

// Close the database connection
$conn->close();
?>
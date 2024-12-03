<?php
// Include the database connection file
include '../../dbconnections/config.php';

    // Start the session
    session_start();
    $idnumber = $_SESSION['idnumber']; // Example: '12345'

    // Function to mark specific notifications as read based on user ID
    function  markNotificationsRead($conn, $idnumber) {
        // Update assignment_is_read in task_assignments where assignuser matches the user's ID number
        $sql = "UPDATE task_assignments 
                SET assignment_is_read = 1 
                WHERE assignuser = ? AND assignment_is_read = 0";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $idnumber);
        $stmt->execute();

        // Update deansnote_is_read in task_assignments
        // Update deansnote_is_read in task_assignments
        $sql = "UPDATE task_assignments 
                SET deansnote_is_read = 1 
                WHERE assignuser = ? AND deansmessage IS NOT NULL AND deansmessage != '' AND deansnote_is_read = 0";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $idnumber);
        $stmt->execute();

            // Update note_is_read in ipcrsubmittedtask where assignuser matches the user's ID number
            $sql = "UPDATE ipcrsubmittedtask 
            SET note_is_read = 1 
            WHERE idnumber = ? AND note_is_read = 0";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $idnumber);
        $stmt->execute();

        }

        // Call the function to mark notifications as read
    markNotificationsRead($conn, $idnumber);

// Close the database connection
$conn->close();
?>
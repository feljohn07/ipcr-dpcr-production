<?php
// Include the database connection file
include '../../../dbconnections/config.php';

// Check if the semester ID is set
if (isset($_POST['semester_id'])) {
    $semester_id = $_POST['semester_id'];

    // Update the status column to "done" for the specified semester ID
    $update_stmt = $conn->prepare("UPDATE semester_tasks SET status = 'done' WHERE semester_id = ?");
    $update_stmt->bind_param("i", $semester_id);
    $update_stmt->execute();

    // Check if the update was successful
    if ($update_stmt->affected_rows > 0) {
        echo 'Semester marked as done successfully.';
    } else {
        echo 'Error marking semester as done.';
    }
} else {
    echo 'Error: Semester ID is not set.';
}

// Close the database connection
$conn->close();
?>
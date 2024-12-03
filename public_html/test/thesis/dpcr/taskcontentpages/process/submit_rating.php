<?php
session_start();
include '../../../dbconnections/config.php'; // Include your database connection

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Capture the task ID and ratings from the POST request
    $task_id = isset($_POST['task_id']) ? $_POST['task_id'] : null;
    $quality = isset($_POST['quality']) ? $_POST['quality'] : null;
    $efficiency = isset($_POST['efficiency']) ? $_POST['efficiency'] : null;
    $timeliness = isset($_POST['timeliness']) ? $_POST['timeliness'] : null;

    // Validate and sanitize the inputs
    if ($task_id && is_numeric($quality) && is_numeric($efficiency) && is_numeric($timeliness)) {
        $task_id = htmlspecialchars($task_id);
        $quality = htmlspecialchars($quality);
        $efficiency = htmlspecialchars($efficiency);
        $timeliness = htmlspecialchars($timeliness);

        // Prepare the update query
        $query = "UPDATE ipcrsubmittedtask SET quality = ?, efficiency = ?, timeliness = ? WHERE task_id = ?";

        // Prepare and execute the statement
        if ($stmt = $conn->prepare($query)) {
            $stmt->bind_param("ddds", $quality, $efficiency, $timeliness, $task_id); // Bind the ratings and task_id to the query
            if ($stmt->execute()) {
                echo "Rating submitted successfully.";
            } else {
                echo "Error updating rating: " . $stmt->error;
            }
            $stmt->close();
        } else {
            echo "Error preparing statement: " . $conn->error;
        }
    } else {
        echo "Invalid input.";
    }
}
?>
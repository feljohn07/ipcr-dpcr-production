<?php
session_start();
include '../../../dbconnections/config.php'; // Include your database connection

// Check if the request is an AJAX POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Capture the task ID, note, and semester ID from the POST request
    $task_id = isset($_POST['task_id']) ? $_POST['task_id'] : null;
    $note = isset($_POST['note']) ? $_POST['note'] : null;
    $semester_id = isset($_POST['semester_id']) ? $_POST['semester_id'] : null;

    // Validate and sanitize input
    $task_id = htmlspecialchars($task_id);
    $note = htmlspecialchars($note);
    $semester_id = htmlspecialchars($semester_id);

    // Check if all required fields are provided
    if ($task_id && $note && $semester_id) {
        // Set the timezone to the Philippines
        date_default_timezone_set('Asia/Manila');
        $currentDateTime = date('Y-m-d H:i:s'); // Get current date and time

        // Prepare the SQL statement to update the note_feedback column, increment times_of_return, update note_created_at, and set note_is_read to 0
        $query = "UPDATE ipcrsubmittedtask 
                  SET note_feedback = ?, 
                      times_of_return = times_of_return + 1, 
                      note_created_at = ?, 
                      note_is_read = 0 
                  WHERE task_id = ? AND id_of_semester = ?";
        
        if ($stmt = $conn->prepare($query)) {
            // Bind parameters
            $stmt->bind_param("ssii", $note, $currentDateTime, $task_id, $semester_id); // Assuming semester_id is an integer
            
            // Execute the statement
            if ($stmt->execute()) {
                echo "Note submitted successfully!";
            } else {
                echo "Error updating note: " . $stmt->error;
            }

            // Close the statement
            $stmt->close();
        } else {
            echo "Error preparing statement: " . $conn->error;
        }
    } else {
        echo "Invalid input. Please provide valid task ID, note, and semester ID.";
    }
} else {
    echo "Invalid request method.";
}

// Close the database connection
$conn->close();
?>
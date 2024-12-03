<?php
// Include your database connection file
include '../../dbconnections/config.php'; // Updated relative path

// Check if the AJAX request was sent
if (isset($_POST['anotherFunction'])) {
    $semester_id = $_POST['semester_id'];

    // Query to check the current value of users_final_approval
    $query = "SELECT users_final_approval FROM semester_tasks WHERE semester_id = ?";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("i", $semester_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $current_approval = $row['users_final_approval'];
    $stmt->close();

    // Determine the new value for users_final_approval
    $new_approval = ($current_approval == 1) ? 0 : 1; // Toggle between 1 and 0

    // Prepare the update query
    if ($new_approval == 0) {
        // If new approval is 0, set dean_final_approval_created_at to NULL
        $update_query = "UPDATE semester_tasks SET users_final_approval = ?, dean_final_approval_created_at = NULL WHERE semester_id = ?";
        $update_stmt = $conn->prepare($update_query);
        if (!$update_stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $update_stmt->bind_param("ii", $new_approval, $semester_id); // Bind parameters
    } else {
        // If new approval is 1, set the current date
        $current_date = new DateTime("now", new DateTimeZone('Asia/Manila')); // Set timezone to Philippines
        $formatted_date = $current_date->format('Y-m-d H:i:s'); // Format date

        $update_query = "UPDATE semester_tasks SET users_final_approval = ?, dean_final_approval_created_at = ? WHERE semester_id = ?";
        $update_stmt = $conn->prepare($update_query);
        if (!$update_stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $update_stmt->bind_param("ssi", $new_approval, $formatted_date, $semester_id); // Bind parameters
    }

    // Execute the update
    if ($update_stmt->execute()) {
        // Optionally, you can return a success message or perform additional actions here
        echo "Update successful.";
    } else {
        echo "Error updating record: " . $update_stmt->error;
    }
    $update_stmt->close();
}
?>
<?php
// Include your database connection file
include '../../dbconnections/config.php'; // Updated relative path

// Check if the AJAX request was sent
if (isset($_POST['toggle_approval'])) {
    $semester_id = $_POST['semester_id'];

    // Query to check the current value of userapproval
    $query = "SELECT userapproval FROM semester_tasks WHERE semester_id = ?";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("i", $semester_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $current_approval = $row['userapproval'];
    $stmt->close();

    // Determine the new value for userapproval and the current date
    $new_approval = ($current_approval === null) ? 1 : null;
    $current_date = new DateTime("now", new DateTimeZone('Asia/Manila')); // Set timezone to Philippines
    $formatted_date = $current_date->format('Y-m-d H:i:s'); // Format date

    // Update the userapproval and deans_final_approval_created_at values in the database
    $update_query = "UPDATE semester_tasks SET userapproval = ?, dean_first_approval_created_at = ? WHERE semester_id = ?";
    $update_stmt = $conn->prepare($update_query);
    if (!$update_stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $update_stmt->bind_param("ssi", $new_approval, $formatted_date, $semester_id); // Bind parameters
    $update_stmt->execute();
    $update_stmt->close();
}
?>
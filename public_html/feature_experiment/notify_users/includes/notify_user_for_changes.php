<?php
// Database connection
include __DIR__ . '/../../../dbconnections/config.php';
include __DIR__ . '/send_email.php';

function notify_user_for_changes($users, $notification) {
    global $conn;

    // Prepare the SQL query once
    $sql = "INSERT INTO dean_rate_and_override_notification (
        user_id, 
        notification
    ) VALUES (
        ?, 
        ?
    )";

    // Prepare the statement
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        "Error preparing statement: " . $conn->error;
        return;
    }

    // Loop through the users array and insert for each user
    foreach ($users as $user_id) {
        // Bind parameters
        $stmt->bind_param("ss", $user_id, $notification);
        $stmt->execute();
        
        // Perform select statement to get the email of the user...

        // sendEmail('feljohn.loe.bangasan@gmail.com', $notification);

        // // Instead of calling sendEmail directly, run it asynchronously
        // $email = 'feljohn.loe.bangasan@gmail.com';  // Example email
        // $command = "php send_email_async.php '$email' '$notification' > /dev/null 2>&1 &";  // Run in the background
        // exec($command);
    }

    // Close the statement
    $stmt->close();
}


// Update quality
// $query = "
//     UPDATE task_assignments 
//     SET 
//         quality = ? 
//     WHERE 
//         assignuser = ? 
//         AND idoftask = ? 
//         AND semester_id = ? 
//         AND task_type = ?
// ";

// switch ($task_type) {
//     case 'strategic_tasks':
//         $query = "UPDATE strategic_tasks SET 
//                     quality = ?, 
//                     efficiency = ?, 
//                     timeliness = ?, 
//                     average = (quality + efficiency + timeliness) / 3 
//                   WHERE task_id = ?";
//         break;
//     case 'core_tasks':
//         $query = "UPDATE core_tasks SET 
//                     quality = ?, 
//                     efficiency = ?, 
//                     timeliness = ?, 
//                     average = (quality + efficiency + timeliness) / 3 
//                   WHERE task_id = ?";
//         break;
//     case 'support_tasks':
//         $query = "UPDATE support_tasks SET 
//                     quality = ?, 
//                     efficiency = ?, 
//                     timeliness = ?, 
//                     average = (quality + efficiency + timeliness) / 3 
//                   WHERE task_id = ?";
//         break;
//     default:
//         echo json_encode(['success' => false, 'message' => 'Invalid task type']);
//         exit;
// }


// parameters, deanID, task_id, changes.

// table - dean_rate_and_override_notification
// id
// user_id
// notification
// created_at

// INSERT QUERY
// INSERT INTO dean_rate_and_override_notification (
//     user_id, 
//     notification, 
// ) 
// VALUES (
//     101, 
//     'Your notification message here', 
// );
?>
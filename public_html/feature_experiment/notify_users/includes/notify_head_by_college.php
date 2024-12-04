<?php
// Database connection
// include __DIR__ . '/../../../dbconnections/config.php';
// include __DIR__ . '/send_email.php';

// function notify_college_head_for_changes($users, $notification) {
//     global $conn;

//     // Prepare the SQL query once
//     $sql = "INSERT INTO dean_rate_and_override_notification (
//         user_id, 
//         notification
//     ) VALUES (
//         ?, 
//         ?
//     )";

//     // Prepare the statement
//     $stmt = $conn->prepare($sql);

//     if (!$stmt) {
//         "Error preparing statement: " . $conn->error;
//         return;
//     }

//     // Loop through the users array and insert for each user
//     foreach ($users as $user_id) {
//         // Bind parameters
//         $stmt->bind_param("ss", $user_id, $notification);
//         $stmt->execute();
        
//         // Perform select statement to get the email of the user...

//         // sendEmail('feljohn.loe.bangasan@gmail.com', $notification);

//         // // Instead of calling sendEmail directly, run it asynchronously
//         // $email = 'feljohn.loe.bangasan@gmail.com';  // Example email
//         // $command = "php send_email_async.php '$email' '$notification' > /dev/null 2>&1 &";  // Run in the background
//         // exec($command);
//     }

//     // Close the statement
//     $stmt->close();
// }

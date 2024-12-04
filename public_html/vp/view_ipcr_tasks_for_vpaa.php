<?php
session_start();
include '../dbconnections/config.php'; // Include your database connection

// Capture the semester_id from the POST request
// Capture the semester_id from the POST request
// Capture the semester_id from the POST request
$semester_id = isset($_POST['semester_id']) ? $_POST['semester_id'] : null;
$tasks = [];
$uniqueUsers = [];

// Check if semester_id is provided
if ($semester_id) {
    // Validate and sanitize the semester_id
    $semester_id = htmlspecialchars($semester_id);
    
    // Query the database to get tasks based on semester_id
// Query the database to get tasks based on semester_id
$query = "SELECT t1.*, t2.file_name, t2.file_type, t2.file_content, 
                u.firstname, u.lastname, u.designation, 
                t1.quality, t1.efficiency, t1.timeliness, t1.average, t1.note_feedback,
                fs.idnumber AS has_signature
          FROM ipcrsubmittedtask t1 
          LEFT JOIN ipcr_file_submitted t2 
          ON t1.task_id = t2.task_id AND t1.group_task_id = t2.group_task_id 
          LEFT JOIN usersinfo u ON t1.idnumber = u.idnumber
          LEFT JOIN for_ipcr_final_signature fs ON t1.idnumber = fs.idnumber AND fs.semester_id = ?
          WHERE t1.id_of_semester = ?";

if ($stmt = $conn->prepare($query)) {
    $stmt->bind_param("ii", $semester_id, $semester_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $group_task_id = $row['group_task_id'];

        // Only add non-Dean users if they have a signature
        if (strcasecmp($row['designation'], 'Dean') === 0 || $row['has_signature']) {
            $tasks[$group_task_id][] = $row;
            $uniqueUsers[$row['idnumber']] = $row; // Store the entire row for later use
        }
    }
    $stmt->close();
} else {
    echo "Error preparing statement: " . $conn->error;
}
}

// Initialize an array to keep track of users to display
$usersToDisplay = [];

// Check if signature exists for each user and semester
foreach ($uniqueUsers as $idnumber => $user) {
    // Check if the user is a Dean
    if (strcasecmp($user['designation'], 'Dean') === 0) {
        // Add Dean users to display regardless of signature
        $usersToDisplay[$idnumber] = $user;
    } else {
        // Check for existing signatures for non-Dean users
        $signatureCheckQuery = "SELECT * FROM to_ipcr_signature WHERE idnumber = ? AND semester_id = ?";
        $signatureCheckStmt = $conn->prepare($signatureCheckQuery);
        $signatureCheckStmt->bind_param("si", $idnumber, $semester_id);
        $signatureCheckStmt->execute();
        $signatureCheckResult = $signatureCheckStmt->get_result();
        
        if ($signatureCheckResult->num_rows > 0) {
            // Add users with signatures
            $usersToDisplay[$idnumber] = $user;
        }
        $signatureCheckStmt->close();
    }
}

// Now you can use $usersToDisplay to show users in your HTML

$totalUsers = count($uniqueUsers);
$signatureExists = [];

// Check if signature exists for each user and semester
foreach ($tasks as $group_task_id => $taskGroup) {
    foreach ($taskGroup as $task) {
        $idnumber = $task['idnumber'];

        // Check for existing signatures
        $signatureCheckQuery = "SELECT * FROM to_ipcr_signature WHERE idnumber = ? AND semester_id = ?";
        $signatureCheckStmt = $conn->prepare($signatureCheckQuery);
        $signatureCheckStmt->bind_param("si", $idnumber, $semester_id);
        $signatureCheckStmt->execute();
        $signatureCheckResult = $signatureCheckStmt->get_result();
        $signatureExists[$idnumber] = $signatureCheckResult->num_rows > 0;
        $signatureCheckStmt->close();
    }
}

// Initialize an array to keep track of the Dean's first signatures
$for_deans_first_signature = [];

// Check for existing first signatures for Deans
foreach ($tasks as $group_task_id => $taskGroup) {
    foreach ($taskGroup as $task) {
        $idnumber = $task['idnumber']; // Get the idnumber of the user

        // Check for existing records in the first_vpaa_to_ipcr_ofdeansignature table
        $vpaa_first_signature_to_dean_query = "SELECT * FROM first_vpaa_to_ipcr_ofdeansignature WHERE idnumber = ? AND semester_id = ?";
        $vpaa_first_signature_to_dean_stmt = $conn->prepare($vpaa_first_signature_to_dean_query);
        $vpaa_first_signature_to_dean_stmt->bind_param("si", $idnumber, $semester_id);
        $vpaa_first_signature_to_dean_stmt->execute();
        $vpaa_first_signature_to_dean_result = $vpaa_first_signature_to_dean_stmt->get_result();

        // Determine the status of the Dean's first signature
        if ($vpaa_first_signature_to_dean_result->num_rows > 0) {
            $for_deans_first_signature[$idnumber] = 'blue'; // First signature exists for Dean
        } else {
            $for_deans_first_signature[$idnumber] = 'gray'; // No signature exists
        }

        // Close the statement
        $vpaa_first_signature_to_dean_stmt->close();
    }
}

// Initialize an array to keep track of final signatures
$finalSignatureExists = [];

// Check for existing final signatures
foreach ($tasks as $group_task_id => $taskGroup) {
    foreach ($taskGroup as $task) {
        $idnumber = $task['idnumber']; // Corrected line without the formatting error
        
        // Check for existing records in the user_semesters table
        $finalSignatureCheckQuery1 = "SELECT * FROM for_ipcr_final_signature WHERE idnumber = ? AND semester_id = ?";
        $finalSignatureCheckStmt1 = $conn->prepare($finalSignatureCheckQuery1);
        $finalSignatureCheckStmt1->bind_param("si", $idnumber, $semester_id);
        $finalSignatureCheckStmt1->execute();
        $finalSignatureCheckResult1 = $finalSignatureCheckStmt1->get_result();

        // Check for existing records in the for_ipcr_final_signature table
        $finalSignatureCheckQuery2 = "SELECT * FROM vpaa_to_ipcr_finalsign WHERE idnumber = ? AND semester_id = ?";
        $finalSignatureCheckStmt2 = $conn->prepare($finalSignatureCheckQuery2);
        $finalSignatureCheckStmt2->bind_param("si", $idnumber, $semester_id);
        $finalSignatureCheckStmt2->execute();
        $finalSignatureCheckResult2 = $finalSignatureCheckStmt2->get_result();

        // Determine the status of final signature
        if ($finalSignatureCheckResult2->num_rows > 0) {
            $finalSignatureExists[$idnumber] = 'blue'; // Final signature exists
        } elseif ($finalSignatureCheckResult1->num_rows > 0) {
            $finalSignatureExists[$idnumber] = 'gray'; // Signature exists in user_semesters but not final_signature
        } else {
            $finalSignatureExists[$idnumber] = 'gray'; // No signature exists
        }

        // Close the statements
        $finalSignatureCheckStmt1->close();
        $finalSignatureCheckStmt2->close();
    }
}

// Step 1: Get the office_head_id from semester_tasks table
$officeHeadId = null;
$semesterTaskQuery = "SELECT office_head_id FROM semester_tasks WHERE semester_id = ?";
$semesterTaskStmt = $conn->prepare($semesterTaskQuery);
$semesterTaskStmt->bind_param("i", $semester_id);
$semesterTaskStmt->execute();
$semesterTaskResult = $semesterTaskStmt->get_result();

if ($semesterTaskResult->num_rows > 0) {
    $semesterTaskRow = $semesterTaskResult->fetch_assoc();
    $officeHeadId = $semesterTaskRow['office_head_id'];
}

$semesterTaskStmt->close(); // Close the statement

// Step 2: Get the college of the office head
$college = "";
if ($officeHeadId) {
    $collegeQuery = "SELECT college FROM usersinfo WHERE idnumber = ?";
    $collegeStmt = $conn->prepare($collegeQuery);
    $collegeStmt->bind_param("s", $officeHeadId);
    $collegeStmt->execute();
    $collegeResult = $collegeStmt->get_result();

    if ($collegeResult->num_rows > 0) {
        $collegeRow = $collegeResult->fetch_assoc();
        $college = $collegeRow['college'];
    }

    $collegeStmt->close(); // Close the statement
}

// Step 3: Count the number of users in the same college
$collegeCountQuery = "SELECT COUNT(*) as count FROM usersinfo WHERE college = ?";
$collegeCountStmt = $conn->prepare($collegeCountQuery);
$collegeCountStmt->bind_param("s", $college);
$collegeCountStmt->execute();
$collegeCountResult = $collegeCountStmt->get_result();
$collegeCount = 0;

if ($collegeCountResult->num_rows > 0) {
    $collegeCountRow = $collegeCountResult->fetch_assoc();
    $collegeCount = $collegeCountRow['count'];
}

$collegeCountStmt->close(); // Close the statement

// Initialize arrays for users
$usersWithSignature = [];
$usersWithoutSignature = [];

// Separate users based on signature existence
foreach ($tasks as $group_task_id => $taskGroup) {
    $userId = $taskGroup[0]['idnumber'];
    $hasSignature = $signatureExists[$userId] ?? false; // Check if the user has a signature

    if ($hasSignature) {
        $usersWithSignature[$group_task_id] = $taskGroup; // Store the task group for users with signatures
    } else {
        $usersWithoutSignature[$group_task_id] = $taskGroup; // Store the task group for users without signatures
    }
}

// Combine users without signatures first, then users with signatures
$sortedTasks = array_merge($usersWithoutSignature, $usersWithSignature);

$conn->close();
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Created Tasks</title>
    <style>
      

      /* Message styling */

      /* Modal Styles */
      .ipcr-modal {
          display: none; /* Hidden by default */
          position: fixed; /* Stay in place */
          z-index: 1000; /* Sit on top */
          left: 0;
          top: 0; /* Adjust the top position */
          width: 100%; /* Full width */
          height: 100%; /* Full height */
          overflow: auto; /* Enable scroll if needed */
          background-color: rgba(0, 0, 0, 0.6); /* Slightly darker background for better visibility */
      }

      .ipcr-modal-content {
          background-color: #fefefe;
          margin: 5% auto;
          padding: 20px;
          border: 1px solid #888;
          width: 80%;
          max-height: 100%;
          overflow-y: auto;
          position: relative;
      }

      .close-button {
          position: fixed;
          top: 15px;
          right: 50px;
          font-size: 28px;
          font-weight: bold;
          cursor: pointer;
          z-index: 1001; /* Make sure the button is on top of the modal */
          display: none; /* Hide the button by default */
      }

      #file-content-wrapper {
          position: relative;
          z-index: 1;
          overflow: hidden; /* Add overflow: hidden to contain the iframe */
      }

      #file-content-wrapper iframe {
          width: 100%;
          height: 100%;
          border: none; /* Remove the border from the iframe */
      }


         /* Modal Styles */
         .rate-quality-modal {
          display: none; /* Hidden by default */
          position: fixed; /* Stay in place */
          z-index: 1000; /* Sit on top */
          left: 0;
          top: 0;
          width: 100%;
          height: 100%;
          overflow: auto; /* Enable scroll if needed */
          background-color: rgba(0, 0, 0, 0.6); /* Black background with opacity */
      }

      .rate-modal-content {
          background-color: #fefefe;
          margin: 10% auto;
          padding: 20px;
          border: 1px solid #888;
          width: 30%; /* Smaller width for rate modal */
          text-align: center;
      }

      /* Style the close button */
      #close-modal-btn {
      position: absolute;
      top: 10px;
      right: 10px;
      font-size: 50px;
      cursor: pointer;
      background-color: transparent;
      border: none;
      padding: 0;
      color: white;
      }

      /* Make the close button visible when the modal is open */
      .rate-quality-modal.show #close-modal-btn {
      display: block;
      }

              /* Message Modal Styles */
              .message-modal {
          display: none; /* Hidden by default */
          position: fixed; /* Stay in place */
          z-index: 1000; /* Sit on top */
          left: 0;
          top: 0;
          width: 100%;
          height: 100%;
          overflow: auto; /* Enable scroll if needed */
          background-color: rgba(0, 0, 0, 0.6); /* Black background with opacity */
      }

      .message-modal-content {
          background-color: #fefefe;
          margin: 10% auto;
          padding: 20px;
          border: 1px solid #888;
          width: 30%; /* Smaller width for message modal */
          text-align: center;
      }

      /* Style the close button for message modal */
      #close-message-modal-btn {
          position: absolute;
          top: 10px;
          right: 10px;
          font-size: 50px;
          cursor: pointer;
          background-color: transparent;
          border: none;
          padding: 0;
          color: white;
      }
  </style>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<script>
        function closeTab() {
            window.close(); // Attempt to close the current tab
        }

            // Function to save the scroll position
        function saveScrollPosition() {
            localStorage.setItem("scrollPos", window.scrollY);
        }

        // Function to restore the scroll position
        function restoreScrollPosition() {
            const scrollPos = localStorage.getItem("scrollPos");
            if (scrollPos) {
                window.scrollTo(0, parseInt(scrollPos)); // Scroll to the saved position
                localStorage.removeItem("scrollPos"); // Clean up after restoring
            }
        }
    </script>
<script>
    // Function to view IPCR messages and open the modal
    // Function to view IPCR messages and open the modal
    function viewIpcrMessageModal(userId, semesterId) {
        var modal = document.getElementById("ipcr-message-modal");
        var modalContent = document.getElementById("ipcr-message-content");
        modal.style.display = "block"; // Show the modal
        modalContent.innerHTML = ""; // Clear previous content

        // Fetch the IPCR messages via AJAX
        var xhr = new XMLHttpRequest();
        xhr.open("GET", "../process/get_ipcr_messages.php?idnumber=" + userId + "&semester_id=" + semesterId, true);
        xhr.onload = function() {
            if (xhr.status === 200) {
                var messages = JSON.parse(xhr.responseText);
                if (messages.length > 0) {
                    messages.forEach(function(message) {
                        modalContent.innerHTML += "<p>" + message + "</p>";
                    });
                } else {
                    modalContent.innerHTML = "<p>No feedback found.</p>";
                }
            } else {
                modalContent.innerHTML = "<p>Error loading messages.</p>";
            }
        };
        xhr.send();
    }


    // Function to close the IPCR message modal
    function closeIpcrMessageModal() {
        document.getElementById("ipcr-message-modal").style.display = "none"; // Hide the modal
}

// Function to close the IPCR message modal
function closeIpcrMessageModal() {
    document.getElementById("ipcr-message-modal").style.display = "none"; // Hide the modal
}
</script>
<script>
function firstSignature(idnumber, semester_id) {
    console.log("ID Number: ", idnumber);
    console.log("Semester ID: ", semester_id);

    // Create a new XMLHttpRequest object
    var xhr = new XMLHttpRequest();

    // Prepare the request
    xhr.open("POST", "../process/save_first_signature_to_ipcr.php", true); // Path to your PHP script
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

    // Define what happens on successful data submission
    xhr.onload = function() {
        if (xhr.status === 200) {
            alert(xhr.responseText); // Show the response from the server


            // Rex send email
            // Using the fetch API to call PHP script
            fetch('../../../feature_experiment/notify_users/includes/send_email_async.php', {
                method: 'POST', // or 'POST' if you're sending data
                body: JSON.stringify({ message: "VPAA Gived First Signature your IPCR Forms for <?php echo $taskGroup[0]['name_of_semester'] ?>", user_id: idnumber }),
            }) 
            .then(response => response.text())
            .then(data => { 
                console.log(data);
            })


            location.reload(); // Reload the page to see the updates
        } else {
            alert("An error occurred while saving the first signature.");
        }
    };

    // Send the request with the data
    xhr.send("idnumber=" + encodeURIComponent(idnumber) + "&semester_id=" + encodeURIComponent(semester_id));
}
</script>
<script>
    function saveSignature(idnumber, semester_id) {
        // Create a new XMLHttpRequest object
        var xhr = new XMLHttpRequest();
        
        // Prepare the request
        xhr.open("POST", "../process/save_signature.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        
        // Define what happens on successful data submission
        xhr.onload = function() {
            if (xhr.status === 200) {
                // Save the current scroll position
                sessionStorage.setItem('scrollPosition', window.scrollY);
                
                // Display response from the server
                alert(xhr.responseText); // Alert the user
                
                // Refresh the page after the user clicks "OK"
                location.reload();
            } else {
                alert("An error occurred while processing your request.");
            }
        };
        
        // Send the request with the data
        xhr.send("idnumber=" + encodeURIComponent(idnumber) + "&semester_id=" + encodeURIComponent(semester_id));
    }

    // Restore scroll position after the page reloads
    window.onload = function() {
        var scrollPosition = sessionStorage.getItem('scrollPosition');
        if (scrollPosition) {
            window.scrollTo(0, parseInt(scrollPosition));
            sessionStorage.removeItem('scrollPosition'); // Clear the scroll position after using it
        }
    };

    function finalSignature(idnumber, semester_id) {
        // Create a new XMLHttpRequest object
        var xhr = new XMLHttpRequest();
        
        // Prepare the request
        xhr.open("POST", "vpaa_final_signature_to_ipcr.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        
        // Define what happens on successful data submission
        xhr.onload = function() {
            if (xhr.status === 200) {
                // Save the current scroll position
                sessionStorage.setItem('scrollPosition', window.scrollY);
                
                // Display response from the server
                alert(xhr.responseText); // Alert the user


                // Rex send email
                // Using the fetch API to call PHP script
                fetch('../../../feature_experiment/notify_users/includes/send_email_async.php', {
                    method: 'POST', // or 'POST' if you're sending data
                    body: JSON.stringify({ message: "VPAA Gived Final Signature your IPCR Forms for <?php echo $taskGroup[0]['name_of_semester'] ?>", user_id: idnumber }),
                }) 
                .then(response => response.text())
                .then(data => { 
                    console.log(data);
                })
                

                
                // Refresh the page after the user clicks "OK"
                location.reload();
            } else {
                alert("An error occurred while processing your request.");
            }
        };
        
        // Send the request with the data
        xhr.send("idnumber=" + encodeURIComponent(idnumber) + "&semester_id=" + encodeURIComponent(semester_id));
    }

    function firstSignature(idnumber, semester_id) {
        // Create a new XMLHttpRequest object
        var xhr = new XMLHttpRequest();
        
        // Prepare the request
        xhr.open("POST", "first_signature_to_dean_ipcr.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        
        // Define what happens on successful data submission
        xhr.onload = function() {
            if (xhr.status === 200) {
                // Save the current scroll position
                sessionStorage.setItem('scrollPosition', window.scrollY);
                
                // Display response from the server
                alert(xhr.responseText); // Alert the user
                
                // Refresh the page after the user clicks "OK"
                location.reload();
            } else {
                alert("An error occurred while processing your request.");
            }
        };
        
        // Send the request with the data
        xhr.send("idnumber=" + encodeURIComponent(idnumber) + "&semester_id=" + encodeURIComponent(semester_id));
    }

    // Restore scroll position after the page reloads
    window.onload = function() {
        var scrollPosition = sessionStorage.getItem('scrollPosition');
        if (scrollPosition) {
            window.scrollTo(0, parseInt(scrollPosition));
            sessionStorage.removeItem('scrollPosition'); // Clear the scroll position after using it
        }
    };
</script>
</head>
<body>
    <div class="header">
        <style>
            .header {
                position: fixed; /* Fixes the header at the top of the viewport */
                top: 0; /* Aligns the header to the top */
                left: 0; /* Aligns the header to the left */
                width: 100%; /* Makes the header span the full width of the viewport */
                padding: 15px; /* Space inside the header */
                background-color: #4CAF50; /* Green background color */
                color: white; /* White text color */
                text-align: center; /* Center the text */
                border-radius: 0 0 5px 5px; /* Rounded corners at the bottom */
                margin-bottom: 20px; /* Space below the header */
                font-family: Arial, sans-serif; /* Font style */
                box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2); /* Subtle shadow for depth */
                z-index: 1000; /* Ensures the header stays above other content */
            }
        </style>
         <h2><?php echo htmlspecialchars($college); ?> : <?php echo htmlspecialchars($collegeCount); ?> / <?php echo htmlspecialchars($totalUsers); ?></h2>
    </div>
<div class="user-task-group" style="border: 1px solid #ddd; border-radius: 5px; padding: 15px; margin-bottom: 20px; background-color: #f9f9f9;">
    <button style="background-color: blue; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; position: fixed; right: 20px; top: 20px; z-index: 1000;" onclick="closeTab()">Back</button>
    <h3>IPCR TASKS</h3>
    <?php if (!empty($tasks)): ?>
    <?php foreach ($tasks as $group_task_id => $taskGroup): ?>
        <?php 
        // Check if the user has a signature
        $userId = $taskGroup[0]['idnumber'];
        $hasSignature = $signatureExists[$userId] ?? false; // Check if the user has a signature
        $idnumber = $task['idnumber'];
        $designation = $taskGroup[0]['designation'];
        ?>
            <div class="user-task-group" style="border: 1px solid #ddd; border-radius: 5px; padding: 15px; margin-bottom: 20px; background-color: #f9f9f9;">
                <h4>Semester : <?php echo htmlspecialchars($taskGroup[0]['name_of_semester']); ?></h4>
                <h5><?php echo htmlspecialchars($taskGroup[0]['firstname']) . ' ' . htmlspecialchars($taskGroup[0]['lastname']); ?></h5>

                <!-- Dropdown button -->
                <div class="dropdown">
                    <style>
                        .dropdown {
                            position: relative;
                            display: inline-block;
                        }
                        .dropdown-toggle {
                            background-color: #4CAF50;
                            color: white;
                            border: none;
                            border-radius: 5px;
                            cursor: pointer;
                            font-size: 16px;
                            padding: 8px;
                        }
                        .dropdown-content {
                            display: none;
                            position: absolute;
                            background-color: #f9f9f9;
                            min-width: 160px;
                            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
                            border-radius: 5px;
                            z-index: 1;
                        }
                        .dropdown-content button {
                            background-color: transparent;
                            color: black;
                            border: none;
                            cursor: pointer;
                            padding: 10px;
                            text-align: left;
                            width: 100%;
                        }
                        .dropdown-content button:hover {
                            background-color: #ddd;
                        }
                        .dropdown-content button {
                            margin-bottom: 10px; /* Adjust the value as needed */
                            width: 100%; /* Optional: Make buttons full width */
                        }

                        .dropdown-content button:last-child {
                            margin-bottom: 0; /* Remove margin from the last button */
                        }
                    </style>
                    <script>
                        function toggleDropdown(button) {
                            const dropdownContent = button.nextElementSibling;
                            dropdownContent.style.display = dropdownContent.style.display === "block" ? "none" : "block";
                        }
                        window.onclick = function(event) {
                            if (!event.target.matches('.dropdown-toggle')) {
                                const dropdowns = document.getElementsByClassName("dropdown-content");
                                for (let i = 0; i < dropdowns.length; i++) {
                                    const openDropdown = dropdowns[i];
                                    if (openDropdown.style.display === "block") {
                                        openDropdown.style.display = "none";
                                    }
                                }
                            }
                        }
                    </script>
                    <button onclick="toggleDropdown(this)" class="dropdown-toggle">☰</button>
                    <div class="dropdown-content">
                        <button class="view-ipcr-button" onclick="openModal('<?php echo htmlspecialchars($semester_id); ?>', '<?php echo htmlspecialchars($taskGroup[0]['idnumber']); ?>', '<?php echo htmlspecialchars($group_task_id); ?>')">View IPCR Forms</button>
                        <?php if (strcasecmp($designation, 'Dean') === 0): ?>
                            <button class="signature-button" onclick="firstSignature('<?php echo htmlspecialchars($taskGroup[0]['idnumber']); ?>', '<?php echo htmlspecialchars($semester_id); ?>')">First Signature</button>
                        <?php endif; ?>
                        <button class="signature-button" onclick="finalSignature('<?php echo htmlspecialchars($taskGroup[0]['idnumber']); ?>', '<?php echo htmlspecialchars($semester_id); ?>')">Final Signature</button>
                    </div>
                </div>

                <div style="display: flex; align-items: center; border: 1px solid #ccc; border-radius: 5px; padding: 5px; background-color: #f0f0f0; margin-top: 10px;">
                   <!-- Dean's First Signature Indicator -->
                   <?php if ($taskGroup[0]['designation'] === 'Dean'): // Check if the user's designation is Dean ?>
                        <div style="display: flex; align-items: center; margin-left: 15px;">
                            <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; 
                                        background-color: <?= $for_deans_first_signature[$taskGroup[0]['idnumber']] ?? 'gray'; ?>;" 
                                title="Dean's First Signature Status"></span>
                            <span style="margin-left: 2px;">First Signature</span>
                        </div>
                    <?php endif; ?>  
                <div style="margin: 0 10px; border-left: 1px solid #ccc; height: 15px;"></div> <!-- Vertical line for separation -->
                    <div style="display: flex; align-items: center; margin-left: 15px;">
                        <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; 
                                    background-color: <?= $finalSignatureExists[$taskGroup[0]['idnumber']] ?? 'gray'; ?>;" 
                            title="Final Signature Status"></span>
                        <span style="margin-left: 2px;"> Final Signature</span>
                    </div>
                </div>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <p>No tasks created yet.</p>
<?php endif; ?>
</div>






<!-- Modal Structure -->
<div id="myModal" class="modal">
    <div class="modal-content">
    <span class="close" style="font-size: 40px; position: absolute; right: 15px; top: 5px; " onclick="closeModal()">&times;</span>
        <iframe id="modalIframe" src="" width="100%" height="600px" frameborder="0"></iframe>
    </div>
</div>
<style>
    .modal {
        display: none; /* Hidden by default */
        position: fixed; /* Stay in place */
        z-index: 1000; /* Sit on top */
        left: 50%; /* Center horizontally */
        top: 50%; /* Center vertically */
        transform: translate(-50%, -50%); /* Adjust position back up by 50% of the modal height */
        width: 1200px; /* Full width, or adjust as needed */
        max-width: 1200px; /* Set a maximum width for larger screens */
        height: auto; /* Auto height to fit content */
        background-color: rgba(0, 0, 0, 0.4); /* Black background with opacity */
        overflow: auto; /* Enable scroll if needed */
    }

    .modal-content {
        background-color: #fefefe;
        padding: 20px;
        border: 1px solid #888;
        width: 1200px; /* Make sure modal content takes full width */
        box-sizing: border-box; /* Include padding and border in the element's total width */
        position: relative; /* Relative position for absolute positioning of close button */
    }

    .close {
        color: #333; /* Change color to something visible */
        font-size: 40px; 
        position: absolute; 
        right: 15px; 
        top: 5px; 
        cursor: pointer; /* Pointer cursor on hover */
        z-index: 1001; /* Ensure the close button is above the modal content */
    }

    .close:hover {
        color: red; /* Change color on hover for better visibility */
    }
</style>
<script>
    document.addEventListener('DOMContentLoaded', function () {
    const myModal = document.getElementById('myModal');
    const closeMyModal = document.querySelector('.modal .close');
    const modalIframe = document.getElementById('modalIframe');

    // Close the modal when the close button is clicked
    closeMyModal.addEventListener('click', function () {
        closeModal();
    });

    // Close the modal when clicking outside of the modal content
    window.addEventListener('click', function (event) {
        if (event.target === myModal) {
            closeModal();
        }
    });
});

// Function to open modal and set iframe source
document.addEventListener('DOMContentLoaded', function () {
    const myModal = document.getElementById('myModal');
    const closeMyModal = document.querySelector('.modal .close');
    const modalIframe = document.getElementById('modalIframe');

    // Close the modal when the close button is clicked
    closeMyModal.addEventListener('click', function () {
        closeModal();
    });

    // Close the modal when clicking outside of the modal content
    window.addEventListener('click', function (event) {
        if (event.target === myModal) {
            closeModal();
        }
    });
});

// Function to open modal and set iframe source
// Function to open modal and set iframe source
function openModal(semesterId, userId, groupTaskId) {
    const modalIframe = document.getElementById('modalIframe'); // Ensure this element exists in your HTML
    modalIframe.src = `ipcr_forms.php?id_of_semester=${semesterId}&idnumber=${userId}&group_task_id=${groupTaskId}`;
    
    // Show the modal
    const myModal = document.getElementById('myModal');
    myModal.style.display = 'block';
}

// Function to close the modal
function closeModal() {
    const modal = document.getElementById('myModal');
    modal.style.display = 'none';
    
    // Clear the iframe source when closing the modal
    const modalIframe = document.getElementById('modalIframe');
    modalIframe.src = ''; // This prevents the previous content from being displayed when reopened
}
</script>
</body>
</html>
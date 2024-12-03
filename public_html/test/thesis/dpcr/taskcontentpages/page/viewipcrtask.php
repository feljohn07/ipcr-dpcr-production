<?php
session_start();
include '../../../dbconnections/config.php'; // Include your database connection

// Capture the semester_id from the POST request
$semester_id = isset($_POST['semester_id']) ? $_POST['semester_id'] : null;
$tasks = [];
$uniqueUsers = [];

// Check if semester_id is provided
if ($semester_id) {
    // Validate and sanitize the semester_id
    $semester_id = htmlspecialchars($semester_id);

    $query = "SELECT t1.*, t2.file_name, t2.file_type, t2.file_content, 
            u.firstname, u.lastname, u.designation,  -- Fetching designation from usersinfo
            t1.quality, t1.efficiency, t1.timeliness, t1.average, t1.note_feedback
        FROM ipcrsubmittedtask t1
        LEFT JOIN ipcr_file_submitted t2 
        ON t1.task_id = t2.task_id AND t1.group_task_id = t2.group_task_id
        LEFT JOIN usersinfo u 
        ON t1.idnumber = u.idnumber
        WHERE t1.id_of_semester = ?";
    // Prepare and execute the statement
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("s", $semester_id); // Bind the semester_id to the query
        $stmt->execute();

        // Fetch the results
        $result = $stmt->get_result();

        // Process the result and group tasks by task group id
        while ($row = $result->fetch_assoc()) {
            $group_task_id = $row['group_task_id'];
            $tasks[$group_task_id][] = $row;

            // Add the user ID to the unique users array
            $uniqueUsers[$row['idnumber']] = true; // Assuming 'idnumber' is the user ID
        }

        // Close the statement
        $stmt->close();
    } else {
        // Handle query preparation error
        echo "Error preparing statement: " . $conn->error;
    }
}

// Count the number of unique users based on the tasks fetched
$totalUsers = count($uniqueUsers);

// Add this code to check for existing signatures after fetching the tasks
$signatureExists = []; // Array to keep track of signatures
$messageExists = [];   // Array to keep track of messages

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

        // Check for existing messages
        $messageCheckQuery = "SELECT * FROM performance_ipcr_message WHERE idnumber = ? AND semester_id = ?";
        $messageCheckStmt = $conn->prepare($messageCheckQuery);
        $messageCheckStmt->bind_param("si", $idnumber, $semester_id);
        $messageCheckStmt->execute();
        $messageCheckResult = $messageCheckStmt->get_result();
        $messageExists[$idnumber] = $messageCheckResult->num_rows > 0;
        $messageCheckStmt->close();
    }
}

// Initialize an array to keep track of final signatures
$finalSignatureExists = [];

// Check for existing final signatures
foreach ($tasks as $group_task_id => $taskGroup) {
    foreach ($taskGroup as $task) {
        $idnumber = $task['idnumber'];

        // Check for existing records in the user_semesters table
        $finalSignatureCheckQuery1 = "SELECT * FROM user_semesters WHERE idnumber = ? AND semester_id = ?";
        $finalSignatureCheckStmt1 = $conn->prepare($finalSignatureCheckQuery1);
        $finalSignatureCheckStmt1->bind_param("si", $idnumber, $semester_id);
        $finalSignatureCheckStmt1->execute();
        $finalSignatureCheckResult1 = $finalSignatureCheckStmt1->get_result();

        // Check for existing records in the for_ipcr_final_signature table
        $finalSignatureCheckQuery2 = "SELECT * FROM for_ipcr_final_signature WHERE idnumber = ? AND semester_id = ?";
        $finalSignatureCheckStmt2 = $conn->prepare($finalSignatureCheckQuery2);
        $finalSignatureCheckStmt2->bind_param("si", $idnumber, $semester_id);
        $finalSignatureCheckStmt2->execute();
        $finalSignatureCheckResult2 = $finalSignatureCheckStmt2->get_result();

        // Determine the status of final signature
        if ($finalSignatureCheckResult2->num_rows > 0) {
            // If there are records in for_ipcr_final_signature, set it to blue
            $finalSignatureExists[$idnumber] = 'blue';
        } elseif ($finalSignatureCheckResult1->num_rows > 0) {
            // If there are records in user_semesters but not in for_ipcr_final_signature, set it to green
            $finalSignatureExists[$idnumber] = '#08F008';
        } else {
            // If there are no records in either, set it to gray
            $finalSignatureExists[$idnumber] = 'gray';
        }

        // Close the statements
        $finalSignatureCheckStmt1->close();
        $finalSignatureCheckStmt2->close();
    }
}

// Get the college of the logged-in user (assuming user info is stored in session)
$userId = $_SESSION['idnumber']; // Replace with the actual session variable for user ID
$college = "";

// Fetch the college of the currently logged-in user
$collegeQuery = "SELECT college FROM usersinfo WHERE idnumber = ?";
$collegeStmt = $conn->prepare($collegeQuery);
$collegeStmt->bind_param("s", $userId);
$collegeStmt->execute();
$collegeResult = $collegeStmt->get_result();

if ($collegeResult->num_rows > 0) {
    $collegeRow = $collegeResult->fetch_assoc();
    $college = $collegeRow['college'];
}

// Count the number of users in the same college
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

// Close the statements
$collegeStmt->close();
$collegeCountStmt->close();

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

// Function to check if a user has a semester entry
function userHasSemesterEntry($conn, $idnumber, $semester_id)
{
    $query = "SELECT * FROM user_semesters WHERE idnumber = ? AND semester_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $idnumber, $semester_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();
    return $exists;
}
// Check if a signature exists for this user and semester_id
$signatureCheckQuery = "SELECT * FROM to_ipcr_signature WHERE idnumber = ? AND semester_id = ?";
$signatureCheckStmt = $conn->prepare($signatureCheckQuery);
$signatureCheckStmt->bind_param("si", $userId, $semester_id);
$signatureCheckStmt->execute();
$signatureCheckResult = $signatureCheckStmt->get_result();

// Determine if the buttons should be shown
$buttonsVisible = $signatureCheckResult->num_rows > 0; // true if a matching row exists

// Close the statement
$signatureCheckStmt->close();

// After fetching the first signature data, check for the second table
$presidentSignatureExists = []; // Array to keep track of president signatures

// Check for existing president signatures
foreach ($tasks as $group_task_id => $taskGroup) {
    foreach ($taskGroup as $task) {
        $idnumber = $task['idnumber'];

        // Check for existing records in the president_first_signature_to_ipcr table
        $presidentSignatureCheckQuery = "SELECT * FROM president_first_signature_to_ipcr WHERE idnumber = ? AND semester_id = ?";
        $presidentSignatureCheckStmt = $conn->prepare($presidentSignatureCheckQuery);
        $presidentSignatureCheckStmt->bind_param("si", $idnumber, $semester_id);
        $presidentSignatureCheckStmt->execute();
        $presidentSignatureCheckResult = $presidentSignatureCheckStmt->get_result();

        // Determine the status of president signature
        $presidentSignatureExists[$idnumber] = $presidentSignatureCheckResult->num_rows > 0;

        // Close the statement
        $presidentSignatureCheckStmt->close();
    }
}
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
                display: none;
                /* Hidden by default */
                position: fixed;
                /* Stay in place */
                z-index: 1000;
                /* Sit on top */
                left: 0;
                top: 0;
                /* Adjust the top position */
                width: 100%;
                /* Full width */
                height: 100%;
                /* Full height */
                overflow: auto;
                /* Enable scroll if needed */
                background-color: rgba(0, 0, 0, 0.6);
                /* Slightly darker background for better visibility */
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
                z-index: 1001;
                /* Make sure the button is on top of the modal */
                display: none;
                /* Hide the button by default */
            }

            #file-content-wrapper {
                position: relative;
                z-index: 1;
                overflow: hidden;
                /* Add overflow: hidden to contain the iframe */
            }

            #file-content-wrapper iframe {
                width: 100%;
                height: 100%;
                border: none;
                /* Remove the border from the iframe */
            }


            /* Modal Styles */
            .rate-quality-modal {
                display: none;
                /* Hidden by default */
                position: fixed;
                /* Stay in place */
                z-index: 1000;
                /* Sit on top */
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                overflow: auto;
                /* Enable scroll if needed */
                background-color: rgba(0, 0, 0, 0.6);
                /* Black background with opacity */
            }

            .rate-modal-content {
                background-color: #fefefe;
                margin: 10% auto;
                padding: 20px;
                border: 1px solid #888;
                width: 30%;
                /* Smaller width for rate modal */
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
                display: none;
                /* Hidden by default */
                position: fixed;
                /* Stay in place */
                z-index: 1000;
                /* Sit on top */
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                overflow: auto;
                /* Enable scroll if needed */
                background-color: rgba(0, 0, 0, 0.6);
                /* Black background with opacity */
            }

            .message-modal-content {
                background-color: #fefefe;
                margin: 10% auto;
                padding: 20px;
                border: 1px solid #888;
                width: 30%;
                /* Smaller width for message modal */
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

            // Function to view the file in a modal
            function viewFile(fileContent, fileType, fileName) {
                saveScrollPosition(); // Save the scroll position when opening the modal

                var modal = document.getElementById("file-modal");
                var modalContent = document.getElementById("file-modal-content");
                modal.style.display = "block"; // Show the modal
                modalContent.innerHTML = ""; // Clear previous content

                // Show the close button when the modal is opened
                var closeButton = document.getElementById("close-button");
                closeButton.style.display = "block";

                switch (fileType) {
                    case 'image/jpeg':
                    case 'image/png':
                    case 'image/gif':
                        modalContent.innerHTML = '<img src="data:' + fileType + ';base64,' + fileContent + '" alt="Image" style="max-width: 100%; height: auto;">';
                        break;
                    case 'application/pdf':
                        // Create a Blob object from the base64 string
                        const byteCharacters = atob(fileContent);
                        const byteNumbers = new Array(byteCharacters.length);
                        for (let i = 0; i < byteCharacters.length; i++) {
                            byteNumbers[i] = byteCharacters.charCodeAt(i);
                        }
                        const byteArray = new Uint8Array(byteNumbers);
                        const blob = new Blob([byteArray], { type: fileType });
                        const blobUrl = URL.createObjectURL(blob);

                        const iframe = document.createElement('iframe');
                        iframe.src = blobUrl;
                        iframe.width = '100%';
                        iframe.height = '500px';
                        modalContent.appendChild(iframe);
                        break;
                    // Document types
                    case 'application/msword':
                    case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
                    case 'application/vnd.ms-powerpoint':
                    case 'application/vnd.openxmlformats-officedocument.presentationml.presentation':
                    case 'application/vnd.ms-excel':
                    case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
                        modalContent.innerHTML = '<iframe src="data:' + fileType + ';base64,' + fileContent + '" frameborder="0" width="100%" height="500px"></iframe>';
                        break;
                    default:
                        modalContent.innerText = 'Unsupported file type.';
                        break;
                }
            }

            // Function to close the modal
            function closeFileModal() {
                var modal = document.getElementById("file-modal");
                modal.style.display = "none";
                var closeButton = document.getElementById("close-button");
                closeButton.style.display = "none"; // Hide the close button when the modal is closed

                restoreScrollPosition(); // Restore the scroll position when closing the modal
            }
        </script>

        <script>
            // Function to open the message modal and store user ID and semester ID
            // Function to open the message modal and store user ID and semester ID
            function openMessageModal(userId) {
                document.getElementById("message-user-id-input").value = userId; // Store the user ID (idnumber)
                document.getElementById("message-modal").style.display = "block"; // Show the message modal
            }

            // Function to submit message using AJAX
            function submitMessage() {
                var message = document.getElementById("message-input").value;
                var idnumber = document.getElementById("message-user-id-input").value; // This is the idnumber of the user
                var semesterId = document.getElementById("message-semester-id-input").value; // Get the semester_id

                if (message === "") {
                    alert("Please enter a message.");
                    return; // Prevent submission if invalid
                }

                // Save the scroll position before sending the AJAX request
                saveScrollPosition();

                // Send AJAX request to store the message
                var xhr = new XMLHttpRequest();
                xhr.open("POST", "../process/submit_ipcr_message.php", true); // Path to your PHP script
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.onload = function () {
                    if (xhr.status === 200) {
                        alert("Message submitted successfully!");
                        closeMessageModal(); // Close the modal after submission
                        location.reload(); // Reload the page to see the updates
                    } else {
                        alert("Error submitting message.");
                    }
                };
                xhr.send("idnumber=" + idnumber + "&message=" + encodeURIComponent(message) + "&semester_id=" + encodeURIComponent(semesterId)); // Use idnumber instead of user_id
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

            // Restore scroll position after page load
            window.onload = function () {
                restoreScrollPosition();
            };


            // Function to close the message modal
            function closeMessageModal() {
                document.getElementById("message-modal").style.display = "none"; // Hide the message modal
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
                xhr.onload = function () {
                    if (xhr.status === 200) {
                        var messages = JSON.parse(xhr.responseText);
                        if (messages.length > 0) {
                            messages.forEach(function (message) {
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
                xhr.onload = function () {
                    if (xhr.status === 200) {
                        alert(xhr.responseText); // Show the response from the server
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
                // Confirmation dialog
                if (!confirm("Click OK to confirm saving the signature.")) {
                    return; // Exit the function if the user cancels
                }

                // Create a new XMLHttpRequest object
                var xhr = new XMLHttpRequest();

                // Prepare the request
                xhr.open("POST", "../process/save_signature.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

                // Define what happens on successful data submission
                xhr.onload = function () {
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

            function finalSignature(idnumber, semester_id) {
                // Confirmation dialog
                if (!confirm("Click OK to confirm finalizing the signature.")) {
                    return; // Exit the function if the user cancels
                }

                // Create a new XMLHttpRequest object
                var xhr = new XMLHttpRequest();

                // Prepare the request
                xhr.open("POST", "../process/finalsignature_to_ipcr.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

                // Define what happens on successful data submission
                xhr.onload = function () {
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
            window.onload = function () {
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
                    position: fixed;
                    /* Fixes the header at the top of the viewport */
                    top: 0;
                    /* Aligns the header to the top */
                    left: 0;
                    /* Aligns the header to the left */
                    width: 100%;
                    /* Makes the header span the full width of the viewport */
                    padding: 15px;
                    /* Space inside the header */
                    background-color: #4CAF50;
                    /* Green background color */
                    color: white;
                    /* White text color */
                    text-align: center;
                    /* Center the text */
                    border-radius: 0 0 5px 5px;
                    /* Rounded corners at the bottom */
                    margin-bottom: 20px;
                    /* Space below the header */
                    font-family: Arial, sans-serif;
                    /* Font style */
                    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
                    /* Subtle shadow for depth */
                    z-index: 1000;
                    /* Ensures the header stays above other content */
                }
            </style>
            <h2>Users who submitted froms <?php echo htmlspecialchars($collegeCount); ?> /
                <?php echo htmlspecialchars($totalUsers); ?></h2>
        </div>
        <div class="user-task-group"
            style="border: 1px solid #ddd; border-radius: 5px; padding: 15px; margin-bottom: 20px; background-color: #f9f9f9;">
            <button
                style="background-color: blue; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; position: fixed; right: 20px; top: 20px; z-index: 1000;"
                onclick="closeTab()">Back</button>
            <h3>IPCR TASKS</h3>
            <?php if (!empty($tasks)): ?>
                <?php foreach ($tasks as $group_task_id => $taskGroup): ?>
                    <?php
                    // Check if the user has a signature
                    $userId = $taskGroup[0]['idnumber'];
                    $hasSignature = $signatureExists[$userId] ?? false; // Check if the user has a signature
                    $designation = $taskGroup[0]['designation']; // Get the designation from the first task
                    $idnumber = $task['idnumber'];
                    // Check if the user has an entry in user_semesters
                    $hasSemesterEntry = userHasSemesterEntry($conn, $userId, $semester_id);
                    ?>

                    <div class="user-task-group"
                        style="border: 1px solid #ddd; border-radius: 5px; padding: 15px; margin-bottom: 20px; background-color: #f9f9f9;">
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
                                    box-shadow: 0px 8px 16px 0px rgba(0, 0, 0, 0.2);
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
                                    margin-bottom: 10px;
                                    /* Adjust the value as needed */
                                    width: 100%;
                                    /* Optional: Make buttons full width */
                                }

                                .dropdown-content button:last-child {
                                    margin-bottom: 0;
                                    /* Remove margin from the last button */
                                }
                            </style>
                            <script>
                                function toggleDropdown(button) {
                                    const dropdownContent = button.nextElementSibling;
                                    dropdownContent.style.display = dropdownContent.style.display === "block" ? "none" : "block";
                                }
                                window.onclick = function (event) {
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
                                <button class="view-ipcr-button"
                                    onclick="openModal('<?php echo htmlspecialchars($semester_id); ?>', '<?php echo htmlspecialchars($taskGroup[0]['idnumber']); ?>', '<?php echo htmlspecialchars($group_task_id); ?>')">View
                                    IPCR Forms
                                </button>
                                
                                <?php 
                                // Check if the designation is not Dean or dean
                                if (strcasecmp($designation, 'Dean') !== 0): // strcasecmp is case-insensitive comparison
                                ?>
                                    <button class="signature-button"
                                        onclick="saveSignature('<?php echo htmlspecialchars($taskGroup[0]['idnumber']); ?>', '<?php echo htmlspecialchars($semester_id); ?>')">Sign</button>
                                    
                                    <?php if ($buttonsVisible): // Show buttons only if the condition is met ?>
                                        <button class="message-button"
                                            onclick="openMessageModal('<?php echo htmlspecialchars($taskGroup[0]['idnumber']); ?>')">Send
                                            Development Feedback</button>
                                    <?php endif; ?>
                                    
                                    <button class="message-button"
                                        onclick="viewIpcrMessageModal('<?php echo htmlspecialchars($taskGroup[0]['idnumber']); ?>', '<?php echo htmlspecialchars($semester_id); ?>')">View
                                        Development Feedback</button>
                                    
                                    <?php if ($hasSemesterEntry): // Only show the button if the user has an entry ?>
                                        <button class="signature-button"
                                            onclick="finalSignature('<?php echo htmlspecialchars($userId); ?>', '<?php echo htmlspecialchars($semester_id); ?>')">Final
                                            Signature</button>
                                    <?php endif; ?>
                                <?php 
                                endif; // End of designation check
                                ?>
                            </div>
                        </div>

                        <?php if ($hasSignature): // Only show the status indicators if the user has a signature ?>
                            <div
                                style="display: flex; align-items: center; border: 1px solid #ccc; border-radius: 5px; padding: 5px; background-color: #f0f0f0; margin-top: 10px;">
                                <div style="display: flex; align-items: center; margin-right: 15px;">
                                    <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%;
                                        background-color: <?php echo $signatureExists[$taskGroup[0]['idnumber']] ? 'blue' : 'gray'; ?>;"
                                        title="Signature Status"></span>
                                    <span style="margin-left: 2px;"> Signature</span>
                                </div>
                                <div style="margin: 0 10px; border-left: 1px solid #ccc; height: 15px;"></div>
                                <!-- Vertical line -->
                                <div style="display: flex; align-items: center; margin-left: 15px;">
                                    <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%;
                                        background-color: <?php echo $messageExists[$taskGroup[0]['idnumber']] ? 'blue' : 'gray'; ?>;"
                                        title="Message Status"></span>
                                    <span style="margin-left: 2px;"> Development Feedback</span>
                                </div>
                                <div style="margin: 0 10px; border-left: 1px solid #ccc; height: 15px;"></div>
                                <!-- Vertical line for separation -->
                                <div style="display: flex; align-items: center; margin-left: 15px;">
                                    <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%;
                                        background-color: <?= $finalSignatureExists[$taskGroup[0]['idnumber']] ?? 'gray'; ?>;"
                                        title="Final Signature Status"></span>
                                    <span style="margin-left: 2px;"> Final Signature</span>
                                </div>
                            </div>
                        <?php endif; // End of signature check ?>
                        <?php if ($hasSignature || $presidentSignatureExists[$userId]): // Show if either signature exists ?>
                            <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
                                <tr>
                                    <th style="border: 1px solid #ddd; padding: 12px; background-color: #4CAF50; color: white; text-align: center;"
                                        rowspan="2">Area of Evaluation</th>
                                    <th style="border: 1px solid #ddd; padding: 12px; background-color: #4CAF50; color: white; text-align: center;"
                                        rowspan="2">Task Name</th>
                                    <th style="border: 1px solid #ddd; padding: 12px; background-color: #4CAF50; color: white; text-align: center;"
                                        rowspan="2">Description</th>
                                    <th style="border: 1px solid #ddd; padding: 12px; background-color: #4CAF50; color: white; text-align: center; width: auto;"
                                        rowspan="2">Target</th>
                                    <th style="border: 1px solid #ddd; padding: 12px; background-color: #4CAF50; color: white; text-align: center; width: auto;"
                                        colspan="4">Rating</th>
                                    <th style="border: 1px solid #ddd; padding: 12px; background-color: #4CAF50; color: white; text-align: center;"
                                        rowspan="2">Uploaded Files</th>
                                    <th style="border: 1px solid #ddd; padding: 0; background-color: #4CAF50; color: white; text-align: center;"
                                        rowspan="2">Note Feedback</th>
                                    <th style="border: 1px solid #ddd; padding: 12px; background-color: #4CAF50; color: white; text-align: center;"
                                        rowspan="2">Action</th>
                                </tr>
                                <tr>

                                    <th
                                        style="border: 1px solid #ddd; padding: 0; background-color: #4CAF50; color: white; text-align: center; width: auto;">
                                        Q</th>
                                    <th
                                        style="border: 1px solid #ddd; padding: 0; background-color: #4CAF50; color: white; text-align: center; width: auto;">
                                        E</th>
                                    <th
                                        style="border: 1px solid #ddd; padding: 0; background-color: #4CAF50; color: white; text-align: center; width: auto;">
                                        T</th>
                                    <th
                                        style="border: 1px solid #ddd; padding: 0; background-color: #4CAF50; color: white; text-align: center; width: auto;">
                                        A</th>
                                </tr>
                                <?php
                                $displayedTasks = array();
                                foreach ($taskGroup as $task): ?>
                                    <?php if (!isset($displayedTasks[$task['task_id']])): ?>
                                        <tr>
                                            <td style="border: 1px solid #ddd; padding: 12px;">
                                                <?php echo htmlspecialchars($task['task_type']); ?></td>
                                            <td
                                                style="border: 1px solid #ddd; padding: 12px; max-width: 200px; overflow-wrap: break-word; word-wrap: break-word; white-space: normal;">
                                                <?php echo htmlspecialchars($task['task_name']); ?>
                                            </td>
                                            <td
                                                style="border: 1px solid #ddd; padding: 12px; max-width: 300px; overflow-wrap: break-word; word-wrap: break-word; white-space: normal;">
                                                <?php echo nl2br(htmlspecialchars($task['description'])); ?>
                                            </td>
                                            <td style="border: 1px solid #ddd; padding: 12px;">
                                                <?php echo htmlspecialchars($task['documents_required']); ?></td>
                                            <td style="border: 1px solid #ddd; padding: 2px; text-align: center;">
                                                <?php echo htmlspecialchars($task['quality']); ?></td>
                                            <td style="border: 1px solid #ddd; padding: 2px; text-align:center;">
                                                <?php echo htmlspecialchars($task['efficiency']); ?></td>
                                            <td style="border: 1px solid #ddd; padding: 2px; text-align: center;">
                                                <?php echo htmlspecialchars($task['timeliness']); ?></td>
                                            <td style="border: 1px solid #ddd; padding: 2px; text-align: center;">
                                                <?php echo htmlspecialchars($task['average']); ?></td>

                                            <td
                                                style="border: 1px solid #ddd; padding: 2px; max-width: 100px; overflow-wrap: break-word; font-size: 12px; padding: 5px;">
                                                <?php
                                                $files = array_filter($taskGroup, function ($t) use ($task) {
                                                    return $t['task_id'] == $task['task_id'];
                                                });
                                                ?>
                                                <ul>
                                                    <?php foreach ($files as $file): ?>
                                                        <li>
                                                            <a href="#"
                                                                onclick="viewFile('<?php echo base64_encode($file['file_content']); ?>', '<?php echo $file['file_type']; ?>', '<?php echo $file['file_name']; ?>')">
                                                                <?php echo htmlspecialchars($file['file_name']); ?>
                                                            </a>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </td>
                                            <td style="border: 1px solid #ddd; padding: 2px; text-align: center;">
                                                <?php if (!empty($task['note_feedback'])): ?>
                                                    <button type="button" title="Show Note Feedback" aria-label="Show Note Feedback"
                                                        style="background: none; border: none; cursor: pointer;"
                                                        data-note-feedback="<?php echo htmlspecialchars($task['note_feedback']); ?>"
                                                        onclick="showNoteFeedback(this)">
                                                        <i class="fas fa-comment-dots" style="font-size: 24px; color: #007bff;"></i>
                                                        <!-- Message Icon -->
                                                    </button>
                                                <?php else: ?>
                                                    <span>No Feedback</span> <!-- Optional: Display a message when there's no feedback -->
                                                <?php endif; ?>
                                            </td>
                                            <td style="border: 1px solid #ddd; padding: 12px; text-align: center;">
                                                <button class="rate-button"
                                                    onclick="openRateModal('<?php echo htmlspecialchars($task['task_id']); ?>', '<?php echo htmlspecialchars($task['quality']); ?>', '<?php echo htmlspecialchars($task['efficiency']); ?>', '<?php echo htmlspecialchars($task['timeliness']); ?>')">Rate</button>
                                                <button class="note-button"
                                                    onclick="sendNotePrompt('<?php echo htmlspecialchars($task['task_id']); ?>')">Send
                                                    Note</button>
                                            </td>
                                        </tr>
                                        <?php $displayedTasks[$task['task_id']] = true; ?>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </table>
                        <?php endif; // End of signature check ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No tasks created yet.</p>
            <?php endif; ?>
        </div>





        <!-- Message Modal -->
        <div id="message-modal" class="message-modal">
            <div class="message-modal-content">
                <!-- Close button (outside the modal content) -->
                <button id="close-message-modal-btn" onclick="closeMessageModal()">x</button>
                <h4>Send Message</h4>
                <label for="message-input">Message:</label>
                <textarea id="message-input" required></textarea>
                <input type="hidden" id="message-user-id-input"> <!-- For idnumber -->
                <input type="hidden" id="message-semester-id-input"
                    value="<?php echo htmlspecialchars($semester_id); ?>"> <!-- Hidden input for semester_id -->
                <button onclick="submitMessage()">Submit Message</button>
            </div>
        </div>

        <!-- File Modal -->
        <div id="file-modal" class="ipcr-modal">
            <!-- Close button -->
            <button id="close-button" class="close-button" onclick="closeFileModal()">Close</button>
            <div id="file-modal-content" class="ipcr-modal-content">
                <!-- File content will be displayed here -->
                <div id="file-content-wrapper" style="position: relative; z-index: 1;">
                    <!-- iframe will be created dynamically based on file type -->
                </div>
            </div>
        </div>

        <!-- Rate Modal -->
        <!-- Rate Modal -->
        <div id="rate-modal" class="rate-quality-modal">
            <div class="rate-modal-content">
                <button id="close-modal-btn" onclick="closeRateModal()">x</button>
                <h4>Rate Task</h4>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td><label for="quality-input">Quality:</label></td>
                        <td>
                            <input type="number" id="quality-input" min="0" max="5"
                                placeholder="Enter rating for quality" required style="width: 100%;">
                        </td>
                    </tr>
                    <tr>
                        <td><label for="efficiency-input">Efficiency:</label></td>
                        <td>
                            <input type="number" id="efficiency-input" min="0" max="5"
                                placeholder="Enter rating for efficiency" required style="width: 100%;">
                        </td>
                    </tr>
                    <tr>
                        <td><label for="timeliness-input">Timeliness:</label></td>
                        <td>
                            <input type="number" id="timeliness-input" min="0" max="5"
                                placeholder="Enter rating for timeliness" required style="width: 100%;">
                        </td>
                    </tr>
                </table>
                <input type="hidden" id="task-id-input">
                <button onclick="submitRating()"
                    style="margin-top: 20px; padding: 10px 15px; background-color: #4CAF50; color: white; border: none; border-radius: 5px; cursor: pointer;">Submit
                    Rating</button>
            </div>
            <script>
                // Function to open the rate modal and store task ID and current ratings
                function openRateModal(taskId, currentQuality, currentEfficiency, currentTimeliness) {
                    document.getElementById("task-id-input").value = taskId; // Store the task ID

                    // Set input values; show '0' if the rating is zero
                    document.getElementById("quality-input").value = currentQuality; // Set quality input
                    document.getElementById("efficiency-input").value = currentEfficiency; // Set efficiency input
                    document.getElementById("timeliness-input").value = currentTimeliness; // Set timeliness input

                    // Show or hide the close button based on the values
                    const closeButton = document.getElementById("close-modal-btn");
                    if (currentQuality === "0" && currentEfficiency === "0" && currentTimeliness === "0") {
                        closeButton.style.display = "block"; // Show the close button even if all values are zero
                    } else {
                        closeButton.style.display = "block"; // Show the close button
                    }

                    document.getElementById("rate-modal").style.display = "block"; // Show the rate modal
                }

                // Function to close the rate modal
                function closeRateModal() {
                    document.getElementById("rate-modal").classList.remove("show"); // Remove the show class from the modal
                    document.getElementById("rate-modal").style.display = "none"; // Hide the rate modal
                    document.getElementById("close-modal-btn").style.display = "none"; // Hide the close button
                }

                // Function to submit rating using AJAX
                function submitRating() {
                    var quality = document.getElementById("quality-input").value;
                    var efficiency = document.getElementById("efficiency-input").value;
                    var timeliness = document.getElementById("timeliness-input").value;
                    var taskId = document.getElementById("task-id-input").value;

                    if (quality === "" || isNaN(quality) || parseFloat(quality) < 0 || parseFloat(quality) > 5 ||
                        efficiency === "" || isNaN(efficiency) || parseFloat(efficiency) < 0 || parseFloat(efficiency) > 5 ||
                        timeliness === "" || isNaN(timeliness) || parseFloat(timeliness) < 0 || parseFloat(timeliness) > 5) {
                        alert("Please enter valid ratings between 0.0 and 5.0.");
                        return; // Prevent submission if invalid
                    }

                    // Save the current scroll position
                    sessionStorage.setItem('scrollPosition', window.scrollY);

                    // Send AJAX request to store the rating
                    var xhr = new XMLHttpRequest();
                    xhr.open("POST", "../process/submit_rating.php", true); // Path to your PHP script
                    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                    xhr.onload = function () {
                        if (xhr.status === 200) {
                            alert("Rating submitted successfully!");
                            location.reload(); // Reload the page to see the updates
                        } else {
                            alert("Error submitting rating.");
                        }
                    };
                    xhr.send("task_id=" + taskId + "&quality=" + quality + "&efficiency=" + efficiency + "&timeliness=" + timeliness);
                }

                // Restore scroll position after the page reloads
                window.onload = function () {
                    var scrollPosition = sessionStorage.getItem('scrollPosition');
                    if (scrollPosition) {
                        window.scrollTo(0, parseInt(scrollPosition)); // Scroll to the saved position
                        sessionStorage.removeItem('scrollPosition'); // Clean up after restoring
                    }
                };
            </script>

        </div>

        <!-- IPCR Message Modal -->
        <div id="ipcr-message-modal" class="message-modal">
            <div class="message-modal-content" style="position: relative;">
                <button id="close-ipcr-message-modal-btn"
                    style="position: absolute; top: 10px; right: 10px; font-size: 28px; cursor: pointer; background-color: transparent; border: none; color: #555;"
                    onclick="closeIpcrMessageModal()">×</button>
                <h4>Development Feedback</h4>
                <div id="ipcr-message-content" style="max-height: 300px; overflow-y: auto;"></div>
            </div>
        </div>


        <!-- Send Note to ipcr -->
        <!-- Note Feedback Modal Structure -->
        <div id="note-feedback-modal" class="note-feedback-modal" style="display: none;">
            <style>
                .note-feedback-modal {
                    display: none;
                    /* Hidden by default */
                    position: fixed;
                    /* Stay in place */
                    z-index: 1;
                    /* Sit on top */
                    left: 0;
                    top: 0;
                    width: 100%;
                    /* Full width */
                    height: 100%;
                    /* Full height */
                    overflow: auto;
                    /* Enable scroll if needed */
                    background-color: rgba(0, 0, 0, 0.4);
                    /* Black w/ opacity */
                }

                .modal-content {
                    background-color: #fefefe;
                    margin: 15% auto;
                    /* 15% from the top and centered */
                    padding: 20px;
                    border: 1px solid #888;
                    width: 80%;
                    /* Could be more or less, depending on screen size */
                }

                .close {
                    color: #aaa;
                    float: right;
                    font-size: 28px;
                    font-weight: bold;
                }

                .close:hover,
                .close:focus {
                    color: black;
                    text-decoration: none;
                    cursor: pointer;
                }
            </style>
            <script>
                function showNoteFeedback(button) {
                    var noteFeedback = button.getAttribute('data-note-feedback');

                    if (noteFeedback) {
                        // Use innerHTML to render HTML entities correctly
                        document.getElementById('note-feedback-text').innerHTML = noteFeedback;
                        document.getElementById('note-feedback-modal').style.display = 'block'; // Show the modal
                    } else {
                        alert("No note feedback available.");
                    }
                }

                function closeNoteFeedbackModal() {
                    document.getElementById('note-feedback-modal').style.display = 'none'; // Close the modal
                }
            </script>
            <div class="modal-content">
                <span class="close" onclick="closeNoteFeedbackModal()">&times;</span>
                <h2>Note Feedback</h2>
                <p id="note-feedback-text"></p>
            </div>
        </div>
        <script>
            function sendNotePrompt(taskId) {
                var semesterId = "<?php echo htmlspecialchars($semester_id); ?>"; // Get the semester ID from PHP
                var note = prompt("Enter your note here:");

                if (note === null) {
                    return; // User canceled the prompt
                }

                if (note.trim() === "") {
                    alert("Please enter a note.");
                    return; // Prevent submission if the note is empty
                }

                // Save the current scroll position
                sessionStorage.setItem('scrollPosition', window.scrollY);

                // Send AJAX request to store the note
                var xhr = new XMLHttpRequest();
                xhr.open("POST", "../process/submit_note_to_ipcrtask.php", true); // Path to your PHP script
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.onload = function () {
                    if (xhr.status === 200) {
                        alert("Note submitted successfully!");
                        location.reload(); // Reload the page to see the updates
                    } else {
                        alert("Error submitting note.");
                    }
                };
                xhr.send("task_id=" + encodeURIComponent(taskId) + "&note=" + encodeURIComponent(note) + "&semester_id=" + encodeURIComponent(semesterId)); // Send task ID, note, and semester ID
            }

            // Restore scroll position after the page reloads
            window.onload = function () {
                var scrollPosition = sessionStorage.getItem('scrollPosition');
                if (scrollPosition) {
                    window.scrollTo(0, parseInt(scrollPosition));
                    sessionStorage.removeItem('scrollPosition'); // Clear the scroll position after using it
                }
            };
        </script>
        <!-- Send Note to ipcr -->
        <!-- Note Feedback Modal Structure -->

        <!-- Modal Structure -->
        <div id="myModal" class="modal">
            <div class="modal-content">
                <span class="close" style="font-size: 40px; position: absolute; right: 15px; top: 5px; "
                    onclick="closeModal()">&times;</span>
                <iframe id="modalIframe" src="" width="100%" height="600px" frameborder="0"></iframe>
            </div>
        </div>
        <style>
            .modal {
                display: none;
                /* Hidden by default */
                position: fixed;
                /* Stay in place */
                z-index: 1000;
                /* Sit on top */
                left: 50%;
                /* Center horizontally */
                top: 50%;
                /* Center vertically */
                transform: translate(-50%, -50%);
                /* Adjust position back up by 50% of the modal height */
                width: 1200px;
                /* Full width, or adjust as needed */
                max-width: 1200px;
                /* Set a maximum width for larger screens */
                height: auto;
                /* Auto height to fit content */
                background-color: rgba(0, 0, 0, 0.4);
                /* Black background with opacity */
                overflow: auto;
                /* Enable scroll if needed */
            }

            .modal-content {
                background-color: #fefefe;
                padding: 20px;
                border: 1px solid #888;
                width: 1200px;
                /* Make sure modal content takes full width */
                box-sizing: border-box;
                /* Include padding and border in the element's total width */
                position: relative;
                /* Relative position for absolute positioning of close button */
            }

            .close {
                color: #333;
                /* Change color to something visible */
                font-size: 40px;
                position: absolute;
                right: 15px;
                top: 5px;
                cursor: pointer;
                /* Pointer cursor on hover */
                z-index: 1001;
                /* Ensure the close button is above the modal content */
            }

            .close:hover {
                color: red;
                /* Change color on hover for better visibility */
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
                modalIframe.src = `../process/view_ipcr_forms.php?id_of_semester=${semesterId}&idnumber=${userId}&group_task_id=${groupTaskId}`;

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
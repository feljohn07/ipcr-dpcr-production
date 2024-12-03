<?php
session_start();
include '../../../dbconnections/config.php'; // Include your database connection

// Capture the semester_id from the POST request
$semester_id = isset($_POST['semester_id']) ? $_POST['semester_id'] : null;
if (!isset($_POST['semester_id']) || empty($_POST['semester_id'])) {
    header("Location: ../../dpcrdash.php");
    exit(); // Ensure no further code is executed after the redirect
}

// Initialize an empty array to store the tasks
$tasks = [];

// Check if semester_id is provided
if ($semester_id) {
    // Validate and sanitize the semester_id
    $semester_id = htmlspecialchars($semester_id);
    
    // Query the database to get tasks based on semester_id
    $query = "SELECT t1.*, t2.file_name, t2.file_type, t2.file_content, t1.quality 
    FROM ipcrsubmittedtask t1 
    LEFT JOIN ipcr_file_submitted t2 
    ON t1.task_id = t2.task_id AND t1.group_task_id = t2.group_task_id 
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
        }

        // Define the order of task types
$taskTypeOrder = [
    'strategic' => 1,
    'core' => 2,
    'support' => 3
];

// Sort the tasks within each group by task_type
foreach ($tasks as $group_task_id => &$taskGroup) {
    usort($taskGroup, function($a, $b) use ($taskTypeOrder) {
        return $taskTypeOrder[$a['task_type']] <=> $taskTypeOrder[$b['task_type']];
    });
}

        // Close the statement
        $stmt->close();
    } else {
        // Handle query preparation error
        echo "Error preparing statement: " . $conn->error;
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
        function closeModal() {
            var modal = document.getElementById("file-modal");
            modal.style.display = "none";
            var closeButton = document.getElementById("close-button");
            closeButton.style.display = "none"; // Hide the close button when the modal is closed

            restoreScrollPosition(); // Restore the scroll position when closing the modal
        }
    </script>
    <script>
            // Function to open the rate modal and store task ID
            function openRateModal(taskId) {
                document.getElementById("task-id-input").value = taskId; // Store the task ID
                document.getElementById("rate-modal").classList.add("show"); // Add the show class to the modal
                document.getElementById("rate-modal").style.display = "block"; // Show the rate modal
                document.getElementById("close-modal-btn").style.display = "block"; // Show the close button
            }

            // Function to close the rate modal
            function closeRateModal() {
                document.getElementById("rate-modal").classList.remove("show"); // Remove the show class from the modal
                document.getElementById("rate-modal").style.display = "none"; // Hide the rate modal
                document.getElementById("close-modal-btn").style.display = "none"; // Hide the close button
            }

            // Function to submit rating using AJAX
            function submitRating() {
                var rating = document.getElementById("rating-input").value;
                var taskId = document.getElementById("task-id-input").value;

                if (rating === "" || isNaN(rating) || parseFloat(rating) < 0 || parseFloat(rating) > 5) {
                    alert("Please enter a valid rating between 0.0 and 5.0.");
                    return; // Prevent submission if invalid
                }

                // Check if the rating is valid (non-empty)
                if (rating === "") {
                    alert("Please enter a valid rating.");
                    return;
                }

                // Send AJAX request to store the rating
                var xhr = new XMLHttpRequest();
                xhr.open("POST", "../process/submit_rating.php", true); // Path to your PHP script
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        alert("Rating submitted successfully!");
                        closeRateModal(); // Close the modal after submission

                        // Save scroll position before reloading
                        saveScrollPosition();
                        location.reload(); // Reload the page to see the updates
                    } else {
                        alert("Error submitting rating.");
                    }
                };
                xhr.send("task_id=" + taskId + "&rating=" + rating);
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
            window.onload = function() {
                restoreScrollPosition();
            };

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
    xhr.onload = function() {
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
window.onload = function() {
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
    xhr.onload = function() {
        if (xhr.status === 200) {
            var messages = JSON.parse(xhr.responseText);
            if (messages.length > 0) {
                messages.forEach(function(message) {
                    modalContent.innerHTML += "<p>" + message + "</p>";
                });
            } else {
                modalContent.innerHTML = "<p>No messages found.</p>";
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
    function saveSignature(idnumber, semester_id) {
    // Create a new XMLHttpRequest object
    var xhr = new XMLHttpRequest();
    
    // Prepare the request
    xhr.open("POST", "../process/save_signature.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    
    // Define what happens on successful data submission
    xhr.onload = function() {
        if (xhr.status === 200) {
            // Optionally, you can update the UI or notify the user here
            alert(xhr.responseText); // Display response from the server
        } else {
            alert("An error occurred while processing your request.");
        }
    };
    
    // Send the request with the data
    xhr.send("idnumber=" + encodeURIComponent(idnumber) + "&semester_id=" + encodeURIComponent(semester_id));
}
</script>
</head>
<body>
<div class="container">
    <button style="background-color: green; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; position: fixed; right: 20px; top: 20px; z-index: 1000;" onclick="closeTab()">Back</button>
    <h3>IPCR TASKS</h3>
    <?php if (!empty($tasks)): ?>
        <?php foreach ($tasks as $group_task_id => $taskGroup): ?>
            <div class="user-task-group" style="border: 1px solid #ddd; border-radius: 5px; padding: 15px; margin-bottom: 20px; background-color: #f9f9f9;">
                <h4>Semester : <?php echo htmlspecialchars($taskGroup[0]['name_of_semester']); ?></h4>
                <h5><?php echo htmlspecialchars($taskGroup[0]['firstname']) . ' ' . htmlspecialchars($taskGroup[0]['lastname']); ?></h5>
                   <!-- Dropdown button -->
                <div class="dropdown">
                    <style>
                        /* Dropdown button */
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
                    </style>
                    <script>
                        function toggleDropdown(button) {
                            const dropdownContent = button.nextElementSibling;
                            dropdownContent.style.display = dropdownContent.style.display === "block" ? "none" : "block";
                        }

                        // Close the dropdown if clicked outside
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
                        <button class="message-button" onclick="openMessageModal('<?php echo htmlspecialchars($taskGroup[0]['idnumber']); ?>')">Message User</button>
                        <button class="message-button" onclick="viewIpcrMessageModal('<?php echo htmlspecialchars($taskGroup[0]['idnumber']); ?>', '<?php echo htmlspecialchars($semester_id); ?>')">View IPCR Message</button>
                        <button class="signature-button" onclick="saveSignature('<?php echo htmlspecialchars($taskGroup[0]['idnumber']); ?>', '<?php echo htmlspecialchars($semester_id); ?>')">Sign IPCR</button>
                    </div>
                </div>
                <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
                    <tr>
                        <th style="border: 1px solid #ddd; padding: 12px; background-color: #4CAF50; color: white; text-align: center;">Task Type</th>
                        <th style="border: 1px solid #ddd; padding: 12px; background-color: #4CAF50; color: white; text-align: center;">Task Name</th>
                        <th style="border: 1px solid #ddd; padding: 12px; background-color: #4CAF50; color: white; text-align: center;">Description</th>
                        <th style="border: 1px solid #ddd; padding: 12px; background-color: #4CAF50; color: white; text-align: center; width: auto;">Target</th>
                        <th style="border: 1px solid #ddd; padding: 12px; background-color: #4CAF50; color: white; text-align: center; width: auto;">Quality</th>
                        <th style="border: 1px solid #ddd; padding: 12px; background-color: #4CAF50; color: white; text-align: center;">Uploaded Files</th>
                        <th style="border: 1px solid #ddd; padding: 12px; background-color: #4CAF50; color: white; text-align: center;">Action</th>
                    </tr>
                    <?php 
                    $displayedTasks = array();
                    foreach ($taskGroup as $task): ?>
                        <?php if (!isset($displayedTasks[$task['task_id']])): ?>
                            <tr>
                                <td style="border: 1px solid #ddd; padding: 12px;"><?php echo htmlspecialchars($task['task_type']); ?></td>
                                <td style="border: 1px solid #ddd; padding: 12px; max-width: 200px; overflow-wrap: break-word; word-wrap: break-word; white-space: normal;"><?php echo htmlspecialchars($task['task_name']); ?></td>
                                <td style="border: 1px solid #ddd; padding: 12px; max-width: 300px; overflow-wrap: break-word; word-wrap: break-word; white-space: normal;"><?php echo htmlspecialchars($task['description']); ?></td>
                                <td style="border: 1px solid #ddd; padding: 12px;"><?php echo htmlspecialchars($task['documents_required']); ?></td>
                                <td style="border: 1px solid #ddd; padding: 12px;"><?php echo htmlspecialchars($task['quality']); ?></td>
                                <td style="border: 1px solid #ddd; padding: 12px; max-width: 100px; overflow-wrap: break-word; font-size: 12px; padding: 5px;">
                                    <?php 
                                    $files = array_filter ($taskGroup, function($t) use ($task) {
                                        return $t['task_id'] == $task['task_id'];
                                    });
                                    ?>
                                    <ul>
                                        <?php foreach ($files as $file): ?>
                                            <li>
                                            <a href="#" onclick="viewFile('<?php echo base64_encode($file['file_content']); ?>', '<?php echo $file['file_type']; ?>', '<?php echo $file['file_name']; ?>')">
                                                <?php echo htmlspecialchars($file['file_name']); ?>
                                            </a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </td>
                                <td style="border: 1px solid #ddd; padding: 12px;">
                                    <button class="rate-button" onclick="openRateModal('<?php echo htmlspecialchars($task['task_id']); ?>')">Rate</button>
                                </td>
                            </tr>
                            <?php $displayedTasks[$task['task_id']] = true; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No tasks created yet.</p>
    <?php endif; ?>
</div>


        <!-- Message Modal -->
<!-- Inside your message modal -->
<div id="message-modal" class="message-modal">
    <div class="message-modal-content">
        <!-- Close button (outside the modal content) -->
        <button id="close-message-modal-btn" onclick="closeMessageModal()">x</button>
        <h4>Send Message</h4>
        <label for="message-input">Message:</label>
        <textarea id="message-input" required></textarea>
        <input type="hidden" id="message-user-id-input"> <!-- For idnumber -->
        <input type="hidden" id="message-semester-id-input" value="<?php echo htmlspecialchars($semester_id); ?>"> <!-- Hidden input for semester_id -->
        <button onclick="submitMessage()">Submit Message</button>
    </div>
</div>


    <!-- The modal remains the same -->
    <div id="file-modal" class="ipcr-modal">
        <button id="close-button" class="close-button" onclick="closeModal()">Close</button>
            <!-- Modal for viewing file content -->
        <div id="file-modal-content" class="ipcr-modal-content">
                <!-- File content will be displayed here -->
            <div id="file-content-wrapper" style ="position: relative; z-index: 1;">
                <!-- iframe will be created dynamically based on file type -->
            </div>
        </div>
    </div>

    <div id="rate-modal" class="rate-quality-modal">
    <div class="rate-modal-content">
        <!-- Close button (outside the modal content) -->
        <button id="close-modal-btn" onclick="closeRateModal()">x</button>
        <h4>Rate Task</h4>
        <label for="rating-input">Rate Quality:</label>
        <input type="number" id="rating-input" step="0.1" min="0" max="5" placeholder="Enter rating (0-5)" required>
        <input type="hidden" id="task-id-input">
        <button onclick="submitRating()">Submit Rating</button>
    </div>
</div>

<div id="ipcr-message-modal" class="message-modal">
    <div class="message-modal-content" style="position: relative;">
        <button id="close-ipcr-message-modal-btn" 
                style="position: absolute; top: 10px; right: 10px; font-size: 28px; cursor: pointer; background-color: transparent; border: none; color: #555;" 
                onclick="closeIpcrMessageModal()">×</button>
        <h4>IPCR Messages</h4>
        <div id="ipcr-message-content" style="max-height: 300px; overflow-y: auto;"></div>
    </div>
</div>
</body>
</html>
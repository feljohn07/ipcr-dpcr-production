<?php
session_start();
include '../../dbconnections/config.php'; // Updated relative path

// Retrieve semester_id from URL parameter
$semester_id = $_POST['semester_id'];
if (!isset($_POST['semester_id']) || empty($_POST['semester_id'])) {
    header("Location: ../dpcrdash.php");
    exit(); // Ensure no further code is executed after the redirect
}

// Query to get the values of vpapproval and presidentapproval
$query = "SELECT vpapproval, presidentapproval FROM semester_tasks WHERE semester_id = ?";
$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $semester_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    $row = $result->fetch_assoc();
    
    $showButton = true;
}
$stmt->close();

// Function to update overall document counts
function updateSemesterDocuments($semester_id) {
    global $conn;

    // Calculate total required documents
    $query = "
        SELECT 
            COALESCE(SUM(documents_req), 0) AS total_required
        FROM (
            SELECT documents_req FROM strategic_tasks WHERE semester_id = ?
            UNION ALL
            SELECT documents_req FROM core_tasks WHERE semester_id = ?
            UNION ALL
            SELECT documents_req FROM support_tasks WHERE semester_id = ?
        ) AS all_tasks
    ";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("iii", $semester_id, $semester_id, $semester_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $total_required = $result['total_required'];
    $stmt->close();

    // Calculate total uploaded documents
    $query = "
        SELECT 
            COALESCE(SUM(documents_uploaded), 0) AS total_uploaded
        FROM (
            SELECT documents_uploaded FROM strategic_tasks WHERE semester_id = ?
            UNION ALL
            SELECT documents_uploaded FROM core_tasks WHERE semester_id = ?
            UNION ALL
            SELECT documents_uploaded FROM support_tasks WHERE semester_id = ?
        ) AS all_tasks
    ";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("iii", $semester_id, $semester_id, $semester_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $total_uploaded = $result['total_uploaded'];
    $stmt->close();

    return [
        'total_required' => $total_required,
        'total_uploaded' => $total_uploaded
    ];
}

$totals = updateSemesterDocuments($semester_id);

// Calculate progress percentage
$progress_percentage = ($totals['total_required'] > 0) ? ($totals['total_uploaded'] / $totals['total_required']) * 100 : 0;

// Fetch semester details
$semester_stmt = $conn->prepare("SELECT * FROM semester_tasks WHERE semester_id = ?");
if (!$semester_stmt) {
    die("Prepare failed: " . $conn->error);
}
$semester_stmt->bind_param("i", $semester_id);
$semester_stmt->execute();
$semester_result = $semester_stmt->get_result();
$semester = $semester_result->fetch_assoc();
$semester_stmt->close();

// Function to fetch tasks with aggregated owner details and file attachments
function fetchTasksWithDetails($task_table, $task_type, $semester_id) {
    global $conn;

    $query = "
    SELECT 
        t.task_id,
        t.task_name, 
        t.description, 
        t.documents_req, 
        t.documents_uploaded,
        t.due_date,
        
-- Subquery to get owners
(SELECT GROUP_CONCAT(
    DISTINCT CONCAT(
        ta.lastname, ' ', ta.firstname, ' (', ta.target, '/', ta.num_file, ')', ',', ta.assignuser
    ) SEPARATOR '<br>'
) 
FROM task_assignments ta 
WHERE ta.idoftask = t.task_id 
  AND ta.semester_id = t.semester_id 
  AND ta.task_type = ?
  AND ta.status = 'approved') AS owner,
          
        -- Subquery to get files
        (SELECT GROUP_CONCAT(
            DISTINCT CONCAT(
                '<a href=\"view_file.php?task_id=', t.task_id, '&file_name=', a.file_name, '\" target=\"_blank\">', 
                a.file_name, 
                ' (', a.user_idnumber, ')',  -- Removed the owner name from here
                '</a>' 
            ) SEPARATOR '<br>' 
        )
        FROM task_attachments a
        WHERE a.id_of_task = t.task_id 
          AND a.task_type = ?) AS files,
        
        t.quality,
        t.efficiency,
        t.timeliness,
        t.average
    FROM $task_table t
    WHERE t.semester_id = ?
    GROUP BY t.task_id, t.task_name, t.description, t.documents_req, t.documents_uploaded, t.quality, t.efficiency, t.timeliness, t.average 
    ";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("ssi", $task_type, $task_type, $semester_id); // Bind task_type twice for both subqueries
    $stmt->execute();
    $result = $stmt->get_result();
    $tasks = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $tasks;
}


// Fetch tasks
$strategic_tasks = fetchTasksWithDetails('strategic_tasks', 'strategic', $semester_id);
$core_tasks = fetchTasksWithDetails('core_tasks', 'core', $semester_id);
$support_tasks = fetchTasksWithDetails('support_tasks', 'support', $semester_id);

// Fetch userapproval and users_final_approval values from the database
$query = "SELECT userapproval, users_final_approval FROM semester_tasks WHERE semester_id = ?";
$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $semester_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

// Ensure that the row is not empty before accessing values
if ($row) {
    $current_user_approval = $row['userapproval'];
    $current_final_approval = $row['users_final_approval'];
} else {
    // Handle case where no record is found
    $current_user_approval = 0;  // Default value
    $current_final_approval = 0;  // Default value
}
$stmt->close();

// Determine button text based on userapproval
$button_text = ($current_user_approval === 1) ? "Remove First Signature" : "Sign";   
$final_button_text = ($current_final_approval === 1) ? "Remove Final Signature" : "Final Sign";  

// After fetching the semester details
// After fetching the semester details
$semester_stmt = $conn->prepare("SELECT * FROM semester_tasks WHERE semester_id = ?");
if (!$semester_stmt) {
    die("Prepare failed: " . $conn->error);
}
$semester_stmt->bind_param("i", $semester_id);
$semester_stmt->execute();
$semester_result = $semester_stmt->get_result();
$semester = $semester_result->fetch_assoc();
$semester_stmt->close();

// Determine button visibility based on presidentapproval and final_approval_press
$showFinalButton = false; // Default to false
if ($semester) {
    // Show the button if presidentapproval is 1 and final_approval_press is not 1
    if ($semester['presidentapproval'] === 1 && $semester['final_approval_vpaa'] !== 1) {
        $showFinalButton = true; // Show the button
    }
}

// New variable to control sign button visibility
$showSignButton = ($semester['vpapproval'] === 1) ? false : true;

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Tasks for Semester <?php echo htmlspecialchars($semester_id); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        /* Basic styles for the page */
        table {
            width: 100%; /* Make the table take full width */
            border-collapse: collapse; /* Collapse borders */
        }

        th{
            padding: 10px;
            border: 1px solid #ddd;
            text-align: center;
        }

        td {
            text-align: left; 
            border: 1px solid #ddd;
            padding: 10px;
        } 

        /* Set widths for Task Name and Description */
        .task-name {
            min-width: 200px; /* Minimum width for Task Name */
            max-width: 300px; /* Maximum width for Task Name */
            width: 30%; /* Base width percentage */
        }

        .description {
            min-width: 250px; /* Minimum width for Description */
            max-width: 400px; /* Maximum width for Description */
            width: 40%; /* Base width percentage */
        }

        /* Set widths for other columns */
        .other-columns {
            width: 10%; /* Base width for other columns */
            min-width: 80px; /* Minimum width for other columns */
        }

        /* Optional: Adjust the last column to fit Action button */
        .action {
            width: 100px; /* Fixed width for Action column */
        }
        .owner-item {
        white-space: nowrap; /* Prevent line breaks */
        display: inline-flex; /* Use flex to align items */
        align-items: center; /* Center the icon vertically with the text */
        margin-right: 10px; /* Space between owner items */
    }

    .owner-item i {
        margin-left: 5px; /* Space between the owner's name and the icon */
    }

                    /* Progress bar styling */
            .circle {
                position: relative;
                width: 60px; /* Adjusted width */
                height: 60px; /* Adjusted height */
                margin: auto; /* Center the circle within the td */
            }

            svg {
                transform: rotate(-90deg);
            }

            .circle-bg {
                fill: none;
                stroke: #e6e6e6;
                stroke-width: 6; /* Adjusted stroke width */
            }

            .circle-progress {
                fill: none;
                stroke: #4caf50; /* Change this color as needed */
                stroke-width: 6; /* Adjusted stroke width */
                stroke-linecap: round;
                transition: stroke-dasharray 0.5s ease;
            }
            .percentage {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                font-size: 14px; /* Adjusted font size */
                font-weight: bold;
                color: #333;
            }

            .view-file-icon {
                cursor: pointer;
                color: blue; /* Change color */
                font-size: 24px; /* Change size */
                margin: 5px; /* Add margin */
                transition: transform 0.2s; /* Add transition for effect */
            }

            .view-file-icon:hover {
                transform: scale(1.1); /* Scale effect on hover */
            }
    </style>
</head>
<body>
<div class="top-right-dropdown">
    <style>
        .top-right-dropdown {
            position: sticky;
            top: 0;
            right: 0;
            z-index: 1000; /* Ensure it's on top of other elements */
            display: flex; /* Use flexbox for alignment */
            justify-content: space-between; /* Space between left and right */
            align-items: flex-start; /* Align items at the start */
            padding: 10px; /* Add some padding for spacing */
        }

        .nav-buttons {
            display: flex; /* Use flexbox for buttons */
            align-items: center; /* Center vertically */
        }

        .nav-buttons button {
            margin-right: 10px; /* Add space between buttons */
            padding: 5px 10px; /* Button padding */
            cursor: pointer; /* Pointer cursor on hover */
            color: white;
            background-color: #23bc1d;
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); /* Add box shadow */
            transition: box-shadow 0.3s ease; /* Smooth transition for the box shadow */
        }

        .dropdown {
            position: relative;
            display: inline-block;
        }

        .hamburger {
            background-color: #333; /* Dark background color */
            color: white; /* White color for the dots */
            border: none; /* Remove border */
            cursor: pointer; /* Pointer cursor on hover */
            padding: 10px; /* Increase padding for size */
            font-size: 24px; /* Increase font size for larger dots */
            border-radius: 5px; /* Optional: rounded corners */
        }

        .hamburger:focus {
            outline: none; /* Remove focus outline */
        }

        .hamburger:hover {
            background-color: #444; /* Darker shade on hover */
        }

        .dropdown-content {
            display: none; /* Hidden by default */
            position: absolute;
            left: -160px; /* Adjust this value as needed to position the dropdown */
            background-color: white;
            min-width: 160px;
            box-shadow: 0px 8px 16px 0px rgba(0, 0, 0, 0.2);
            z-index: 1;
        }

        .dropdown-content button {
            color: black;
            padding: 12px 16px;
            text-align: left;
            border: none;
            background: none;
            width: 100%;
            cursor: pointer;
        }

        .dropdown-content button:hover {
            background-color: #f1f1f1; /* Add hover effect */
        }

        .close-tab-btn {
            margin-top: 10px; /* Maintain the margin */
            background-color: #f0f0f0; /* Light background */
            color: black; /* Text color */
            border: none; /* Remove border */
            cursor: pointer; /* Pointer cursor on hover */
            padding: 10px; /* Add some padding */
        }

        .close-tab-btn:hover {
            background-color: red; /* Change background to red on hover */
            color: black; /* Change text color to white on hover */
        }

        .button-spacing {
    margin-bottom: 10px; /* Adjust the value as needed */
}
    </style>
    
    <div class="nav-buttons">
        <button onclick="scrollToSection('strategicTasks')">Strategic Tasks</button>
        <button onclick="scrollToSection('coreTasks')">Core Tasks</button>
        <button onclick="scrollToSection('supportTasks')">Support Tasks</button>
    </div>
    
    <div class="dropdown">
    <button class="hamburger" onclick="toggleDropdown()">&#9776;</button>
    <div class="dropdown-content" id="dropdownContent">
        <?php if ($showButton): ?>
            <button class="generate-btn">Generate into Forms</button>
        <?php endif; ?>
        <button class="sign-btn" 
                onclick="toggleApproval(<?php echo htmlspecialchars($semester_id); ?>)" 
                <?php echo $showSignButton ? '' : 'style="display:none;"'; ?>>
            <?php echo htmlspecialchars($button_text); ?>
        </button>
        <button class="final-sign-btn" onclick="toggleFinalApproval(<?php echo htmlspecialchars($semester_id); ?>)" <?php echo $showFinalButton ? '' : 'style="display:none;"'; ?>>
            <?php echo htmlspecialchars($final_button_text); ?>
        </button>
        <button class="close-tab-btn" onclick="closeTab()">Close This Tab</button>
    </div>
</div>

<script>
    function scrollToSection(sectionId) {
        const section = document.getElementById(sectionId);
        if (section) {
            section.scrollIntoView({ behavior: 'smooth' });
        }
    }

    function toggleDropdown() {
        const dropdownContent = document.getElementById("dropdownContent");
        dropdownContent.style.display = dropdownContent.style.display === "block" ? "none" : "block";
        sessionStorage.setItem("dropdownState", dropdownContent.style.display);
    }

    // Retrieve the dropdown state from session storage
    window.onload = function() {
        const dropdownContent = document.getElementById("dropdownContent");
        const dropdownState = sessionStorage.getItem("dropdownState");
        if (dropdownState === "block") {
            dropdownContent.style.display = "block";
        }
    }

    // Close the dropdown if the user clicks outside of it
    window.onclick = function(event) {
        const dropdownContent = document.getElementById("dropdownContent");
        if (!event.target.matches('.hamburger') && !dropdownContent.contains(event.target)) {
            dropdownContent.style.display = "none";
            sessionStorage.setItem("dropdownState", "none"); // Update session storage
        }
    }

    function closeTab() {
        window.close(); // Attempt to close the current tab
    }

    function toggleApproval(semesterId) {
        console.log("Toggle approval called for semesterId: " + semesterId);
        
        // Prevent default form submission action
        event.preventDefault(); // Ensure 'event' is defined or passed as an argument
        
        $.ajax({
            type: "POST",
            url: "usersignature.php",
            data: {
                semester_id: semesterId,
                toggle_approval: true
            },
            success: function(data) {
                // Toggle the button text based on the new approval state
                const button = document.querySelector('.sign-btn');
                if (button.innerText === "Sign") {
                    button.innerText = "Remove First Signature";
                } else {
                    button.innerText = "Sign";
                }
                console.log("Approval toggled successfully!");
            },
            error: function(xhr, status, error) {
                console.error("Error toggling approval: " + error);
            }
        });
    }

    function toggleFinalApproval(semesterId) {
        console.log("Toggle final approval called for semesterId: " + semesterId);
        
        // Prevent default form submission action
        event.preventDefault(); // Ensure 'event' is defined or passed as an argument
        
        $.ajax({
            type: "POST",
            url: "users_final_signature.php",
            data: {
                semester_id: semesterId,
                anotherFunction: true
            },
            success: function(data) {
                // Toggle the button text based on the new approval state
                const button = document.querySelector('.final-sign-btn');
                if (button.innerText === "Final Sign") {
                    button.innerText = "Remove Final Signature";
                } else {
                    button.innerText = "Final Sign";
                }
                console.log("Final approval toggled successfully!");
            },
            error: function(xhr, status, error) {
                console.error("Error toggling final approval: " + error);
            }
        });
    }

    // Function to close modals
    function closeModal(modal) {
        modal.style.display = 'none';
    }
</script>
</div>

<!-- Modal Structure -->
<div id="myModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <iframe id="modalIframe" src="" width="100%" height="600px" frameborder="0"></iframe>
    </div>
    <style>
        .modal {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 1000; /* Sit on top */
            left: 0;
            top: -220px; /* Align at the top */
            width: 100%; /* Full width */
            height: auto; /* Change height to auto to fit content */
            overflow: auto; /* Enable scroll if needed */
            background-color: rgba(0, 0, 0, 0.4); /* Black background with opacity */
        }

        .modal-content {
            background-color: #fefefe;
            margin: 0; /* Remove margin to allow it to be flush with the top */
            padding: 20px;
            border: 1px solid #888;
            width: 80%; /* Could be more or less, depending on screen size */
            position: relative; /* Ensure relative positioning for the close button */
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
        document.addEventListener('DOMContentLoaded', function () {
            const generateBtn = document.querySelector('.generate-btn');
            const myModal = document.getElementById('myModal');
            const closeMyModal = document.querySelector('.modal .close');

            generateBtn.addEventListener('click', function () {
                // Get the semester_id value
                const semesterId = <?php echo json_encode($semester_id); ?>;
                
                // Set the iframe source with the semester_id as a parameter
                modalIframe.src = `generateform.php?semester_id=${semesterId}`;
                myModal.style.display = 'block';
            });

            closeMyModal.addEventListener('click', function () {
                closeModal();
            });

            window.addEventListener('click', function (event) {
                if (event.target === myModal) {
                    closeModal();
                }
            });
        });

        // Function to close modals
        function closeModal() {
            const modal = document.getElementById('myModal');
            modal.style.display = 'none';
        }
    </script>
</div>

<div class="header" style="padding: 20px; background-color: #f4f4f4; border-bottom: 2px solid #ccc;">
    <h2 style="margin: 0; font-size: 24px; color: #333;"><?php echo htmlspecialchars($semester['semester_name']); ?></h2>
    <p style="margin: 5px 0; color: #666;">
        <strong></strong> <?php echo date('F d, Y', strtotime($semester['start_date'])); ?> -  <?php echo date('F d, Y', strtotime($semester['end_date'])); ?>
    </p>
    <p style="margin: 5px 0;"><strong>Progress:</strong></p>
    <div class="progress-bar-container" style="background-color: #e0e0e0; border-radius: 5px; overflow: hidden; width: 100%; height: 20px;">
        <div class="progress-bar" style="width: <?php echo htmlspecialchars($progress_percentage); ?>%; background-color: #4caf50; height: 100%; text-align: center; color: white;">
            <?php echo htmlspecialchars(round($progress_percentage)); ?>%
        </div>
    </div>
</div>


<div class="tabledata">
    <h3 style="margin: 20px 0; font-size: 20px; color: #333;" id="strategicTasks">Strategic Tasks:</h3>
    <table>
        <thead>
                <tr style="background-color: #f4f4f4;">
                    <th rowspan="2" style="padding: 10px; border: 1px solid #ddd; text-align: center;">Task Name</th>
                    <th rowspan="2" style="padding: 10px; border: 1px solid #ddd; text-align: center;">Description</th>
                    <th rowspan="2" style="padding: 10px; border: 1px solid #ddd; text-align: center;">Target</th>
                    <th rowspan="2" style="padding: 10px; border: 1px solid #ddd; text-align: center;">Owner</th>
                    <th rowspan="2" style="padding: 10px; border: 1px solid #ddd; text-align: center;">Progress</th>
                    <th colspan="5" style="width: 100px; padding: 2px; border: 1px solid #ddd; text-align: center;">Rate</th>
                    <th rowspan="2" style="padding: 10px; border: 1px solid #ddd; text-align: center;">Due date</th>
                </tr>
                <tr>
                    <th style="padding: 20px; border: 1px solid #ddd; text-align: center;">Q</th>
                    <th style="padding: 20px; border: 1px solid #ddd; text-align: center;">E</th>
                    <th style="padding: 20px; border: 1px solid #ddd; text-align: center;">T</th>
                    <th style="padding: 20px; border: 1px solid #ddd; text-align: center;">A</th>
                    <th style="padding: 20px; border: 1px solid #ddd; text-align: center;">Action</th>
                </tr>
            </thead>
        <tbody>
            <?php foreach ($strategic_tasks as $task): 
                $progress = ($task['documents_req'] > 0) ? ($task['documents_uploaded'] / $task['documents_req']) * 100 : 0;
            ?>
                
                <tr data-task-id="<?php echo htmlspecialchars($task['task_id']); ?>" data-task-type="strategic_tasks" style="border-bottom: 1px solid #ddd;">
                <td> 
                    <?php 
                        // First remove any escape characters using stripslashes()
                        $task_name = stripslashes($task['task_name']);
                            
                        // Replace <breakline> with <br> after removing slashes
                        $task_name = str_replace('<break(+)line>', '<br>', $task_name);
                            
                        // Display the task name after replacing <breakline> with <br>
                        echo $task_name; 
                    ?>
                </td>
                <td>
                    <?php 
                        // First remove any escape characters using stripslashes()
                        $description = stripslashes($task['description']);
                            
                        // Replace <breakline> with <br> after removing slashes
                        $description = str_replace('<break(+)line>', '<br>', $description);
                            
                        // Display the description after replacing <breakline> with <br>
                        echo $description; 
                    ?>
                </td>
                <td><?php echo htmlspecialchars($task['documents_req']); ?></td>
                <td style="white-space: nowrap; padding: 10px; text-align: left; height: 100px; overflow-y: auto; display: block;">
                    <?php 
                    // Split the owner string by <br> to get individual owners
                    $owners = explode('<br>', $task['owner']);
                    
                    if (!empty($owners[0])) { // Check if there is at least one owner
                        foreach ($owners as $owner) {
                            // Split the owner string to get name and owner ID
                            list($owner_name, $assignuser_id) = explode(',', $owner);
                            
                            // Display each owner's name
                            echo htmlspecialchars($owner_name); 
                            
                            // Set the owner ID from the assignuser
                            $owner_id = trim($assignuser_id); // Use the assignuser as the owner ID
                            
                            // Icon for viewing files
                           // Inside the foreach loop for owners
                           echo ' <i class="fas fa-file-alt view-file-icon" 
                           data-task-id="' . $task['task_id'] . '" 
                           data-owner-id="' . htmlspecialchars($owner_id) . '" 
                           data-semester-id="' . $semester_id . '" 
                           data-task-type="strategic" 
                           data-owner-name="' . htmlspecialchars($owner_name) . '" 
                           style="cursor:pointer;"></i><br>';
                        }
                    } else {
                        echo 'No owners assigned'; // Optional: Display a message when there are no owners
                    }
                    ?>
                </td>
                <td style="padding: 10px; border: 1px solid #ddd;">
                    <div class="circle">
                        <svg width="60" height="60"> <!-- Further reduced size -->
                            <circle class="circle-bg" cx="30" cy="30" r="25"></circle> <!-- Adjusted radius -->
                            <circle class="circle-progress" cx="30" cy="30" r="25" style="stroke-dasharray: <?php echo ($progress / 100) * (2 * pi() * 25); ?>, 157.08;"></circle> <!-- Adjusted radius -->
                        </svg>
                        <div class="percentage"><?php echo round($progress); ?>%</div> <!-- Rounded to integer -->
                    </div>
                </td>
                <td style="padding: 0; text-align: center;"><?php echo htmlspecialchars($task['quality']); ?></td>
                <td style="padding: 0; text-align: center;"><?php echo htmlspecialchars($task['efficiency']); ?></td>
                <td style="padding: 0; text-align: center;"><?php echo htmlspecialchars($task['timeliness']); ?></td>
                <td style="padding: 0; text-align: center;"><?php echo htmlspecialchars($task['average']); ?></td>
                <td style="padding: 10px; border: 1px solid #ddd;"><button class="edit-btn" onclick="editRow(this)">Edit</button></td>
                <td><?php echo htmlspecialchars(date('M/d/Y', strtotime($task['due_date']))); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h3 style="margin: 20px 0; font-size: 20px; color: #333;" id="coreTasks">Core Tasks:</h3>
        <table>
            <thead>
                <tr style="background-color: #f4f4f4;">
                    <th rowspan="2" style="padding: 10px; border: 1px solid #ddd; text-align: center;">Task Name</th>
                    <th rowspan="2" style="padding: 10px; border: 1px solid #ddd; text-align: center;">Description</th>
                    <th rowspan="2" style="padding: 10px; border: 1px solid #ddd; text-align: center;">Target</th>
                    <th rowspan="2" style="padding: 10px; border: 1px solid #ddd; text-align: center;">Owner</th>
                    <th rowspan="2" style="padding: 10px; border: 1px solid #ddd; text-align: center;">Progress</th>
                    <th colspan="5" style="width: 100px; padding: 2px; border: 1px solid #ddd; text-align: center;">Rate</th>
                    <th rowspan="2" style="padding: 10px; border: 1px solid #ddd; text-align: center;">Due date</th>
                </tr>
                <tr>
                    <th style="padding: 20px; border: 1px solid #ddd; text-align: center;">Q</th>
                    <th style="padding: 20px; border: 1px solid #ddd; text-align: center;">E</th>
                    <th style="padding: 20px; border: 1px solid #ddd; text-align: center;">T</th>
                    <th style="padding: 20px; border: 1px solid #ddd; text-align: center;">A</th>
                    <th style="padding: 20px; border: 1px solid #ddd; text-align: center;">Action</th>
                </tr>
            </thead>
        <tbody>
            <?php foreach ($core_tasks as $task):
                $progress = ($task['documents_req'] > 0) ? ($task['documents_uploaded'] / $task['documents_req']) * 100 : 0;
            ?>
           <tr data-task-id="<?php echo htmlspecialchars($task['task_id']); ?>" data-task-type="core_tasks" style="border-bottom: 1px solid #ddd;">
                <td> 
                    <?php 
                        // First remove any escape characters using stripslashes()
                        $task_name = stripslashes($task['task_name']);
                            
                        // Replace <breakline> with <br> after removing slashes
                        $task_name = str_replace('<break(+)line>', '<br>', $task_name);
                            
                        // Display the task name after replacing <breakline> with <br>
                        echo $task_name; 
                    ?>
                </td>
                <td>
                    <?php 
                        // First remove any escape characters using stripslashes()
                        $description = stripslashes($task['description']);
                            
                        // Replace <breakline> with <br> after removing slashes
                        $description = str_replace('<break(+)line>', '<br>', $description);
                            
                        // Display the description after replacing <breakline> with <br>
                        echo $description; 
                    ?>
                </td>
                <td><?php echo htmlspecialchars($task['documents_req']); ?></td>
                <td style="white-space: nowrap; padding: 10px; text-align: left; height: 100px; overflow-y: auto; display: block;">
                    <?php 
                    // Split the owner string by <br> to get individual owners
                    $owners = explode('<br>', $task['owner']);
                    
                    if (!empty($owners[0])) { // Check if there is at least one owner
                        foreach ($owners as $owner) {
                            // Split the owner string to get name and owner ID
                            list($owner_name, $assignuser_id) = explode(',', $owner);
                            
                            // Display each owner's name
                            echo htmlspecialchars($owner_name); 
                            
                            // Set the owner ID from the assignuser
                            $owner_id = trim($assignuser_id); // Use the assignuser as the owner ID
                            
                            // Icon for viewing files
                           // Inside the foreach loop for owners
                           echo ' <i class="fas fa-file-alt view-file-icon" 
                           data-task-id="' . $task['task_id'] . '" 
                           data-owner-id="' . htmlspecialchars($owner_id) . '" 
                           data-semester-id="' . $semester_id . '" 
                           data-task-type="core" 
                           data-owner-name="' . htmlspecialchars($owner_name) . '" 
                           style="cursor:pointer;"></i><br>';
                        }
                    } else {
                        echo 'No owners assigned'; // Optional: Display a message when there are no owners
                    }
                    ?>
                </td>
                <td style="padding: 10px; border: 1px solid #ddd;">
                    <div class="circle">
                        <svg width="60" height="60"> <!-- Further reduced size -->
                            <circle class="circle-bg" cx="30" cy="30" r="25"></circle> <!-- Adjusted radius -->
                            <circle class="circle-progress" cx="30" cy="30" r="25" style="stroke-dasharray: <?php echo ($progress / 100) * (2 * pi() * 25); ?>, 157.08;"></circle> <!-- Adjusted radius -->
                        </svg>
                        <div class="percentage"><?php echo round($progress); ?>%</div> <!-- Rounded to integer -->
                    </div>
                </td>
                <td style="padding: 0; text-align: center;"><?php echo htmlspecialchars($task['quality']); ?></td>
                <td style="padding: 0; text-align: center;"><?php echo htmlspecialchars($task['efficiency']); ?></td>
                <td style="padding: 0; text-align: center;"><?php echo htmlspecialchars($task['timeliness']); ?></td>
                <td style="padding: 0; text-align: center;"><?php echo htmlspecialchars($task['average']); ?></td>
                <td style="padding: 10px; border: 1px solid #ddd;"><button class="edit-btn" onclick="editRow(this)">Edit</button></td>
                <td><?php echo htmlspecialchars(date('M/d/Y', strtotime($task['due_date']))); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        </table>

        <h3 style="margin: 20px 0; font-size: 20px; color: #333;" id="supportTasks">Support Tasks:</h3>
        <table>
        <thead>
                <tr style="background-color: #f4f4f4;">
                    <th rowspan="2" style="padding: 10px; border: 1px solid #ddd; text-align: center;">Task Name</th>
                    <th rowspan="2" style="padding: 10px; border: 1px solid #ddd; text-align: center;">Description</th>
                    <th rowspan="2" style="padding: 10px; border: 1px solid #ddd; text-align: center;">Target</th>
                    <th rowspan="2" style="padding: 10px; border: 1px solid #ddd; text-align: center;">Owner</th>
                    <th rowspan="2" style="padding: 10px; border: 1px solid #ddd; text-align: center;">Progress</th>
                    <th colspan="5" style="width: 100px; padding: 2px; border: 1px solid #ddd; text-align: center;">Rate</th>
                    <th rowspan="2" style="padding: 10px; border: 1px solid #ddd; text-align: center;">Due date</th>
                </tr>
                <tr>
                    <th style="padding: 20px; border: 1px solid #ddd; text-align: center;">Q</th>
                    <th style="padding: 20px; border: 1px solid #ddd; text-align: center;">E</th>
                    <th style="padding: 20px; border: 1px solid #ddd; text-align: center;">T</th>
                    <th style="padding: 20px; border: 1px solid #ddd; text-align: center;">A</th>
                    <th style="padding: 20px; border: 1px solid #ddd; text-align: center;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($support_tasks as $task):  
                    $progress = ($task['documents_req'] > 0) ? ($task['documents_uploaded'] / $task['documents_req']) * 100 : 0;
                ?>
               <tr data-task-id="<?php echo htmlspecialchars($task['task_id']); ?>" data-task-type="support_tasks" style="border-bottom: 1px solid #ddd;">
                <td> 
                    <?php 
                        // First remove any escape characters using stripslashes()
                        $task_name = stripslashes($task['task_name']);
                            
                        // Replace <breakline> with <br> after removing slashes
                        $task_name = str_replace('<break(+)line>', '<br>', $task_name);
                            
                        // Display the task name after replacing <breakline> with <br>
                        echo $task_name; 
                    ?>
                </td>
                <td>
                    <?php 
                        // First remove any escape characters using stripslashes()
                        $description = stripslashes($task['description']);
                            
                        // Replace <breakline> with <br> after removing slashes
                        $description = str_replace('<break(+)line>', '<br>', $description);
                            
                        // Display the description after replacing <breakline> with <br>
                        echo $description; 
                    ?>
                </td>
                    <td><?php echo htmlspecialchars($task['documents_req']); ?></td>
                    <td style="white-space: nowrap; padding: 10px; text-align: left; height: 100px; overflow-y: auto; display: block;">
                        <?php 
                        // Split the owner string by <br> to get individual owners
                        $owners = explode('<br>', $task['owner']);
                        
                        if (!empty($owners[0])) { // Check if there is at least one owner
                            foreach ($owners as $owner) {
                                // Split the owner string to get name and owner ID
                                list($owner_name, $assignuser_id) = explode(',', $owner);
                                
                                // Display each owner's name
                                echo htmlspecialchars($owner_name); 
                                
                                // Set the owner ID from the assignuser
                                $owner_id = trim($assignuser_id); // Use the assignuser as the owner ID
                                
                                // Icon for viewing files
                            // Inside the foreach loop for owners
                            echo ' <i class="fas fa-file-alt view-file-icon" 
                            data-task-id="' . $task['task_id'] . '" 
                            data-owner-id="' . htmlspecialchars($owner_id) . '" 
                            data-semester-id="' . $semester_id . '" 
                            data-task-type="support" 
                            data-owner-name="' . htmlspecialchars($owner_name) . '" 
                            style="cursor:pointer;"></i><br>';
                            }
                        } else {
                            echo 'No owners assigned'; // Optional: Display a message when there are no owners
                        }
                        ?>
                    </td>
                  
                    <td style="padding: 10px; border: 1px solid #ddd;">
                    <div class="circle">
                        <svg width="60" height="60"> <!-- Further reduced size -->
                            <circle class="circle-bg" cx="30" cy="30" r="25"></circle> <!-- Adjusted radius -->
                            <circle class="circle-progress" cx="30" cy="30" r="25" style="stroke-dasharray: <?php echo ($progress / 100) * (2 * pi() * 25); ?>, 157.08;"></circle> <!-- Adjusted radius -->
                        </svg>
                        <div class="percentage"><?php echo round($progress); ?>%</div> <!-- Rounded to integer -->
                    </div>
                </td>
                    <td style="padding: 0; text-align: center;"><?php echo htmlspecialchars($task['quality']); ?></td>
                    <td style="padding: 0; text-align: center;"><?php echo htmlspecialchars($task['efficiency']); ?></td>
                    <td style="padding: 0; text-align: center;"><?php echo htmlspecialchars($task['timeliness']); ?></td>
                    <td style="padding: 0; text-align: center;"><?php echo htmlspecialchars($task['average']); ?></td>
                    <td style="padding: 10px; border: 1px solid #ddd;"><button class="edit-btn" onclick="editRow(this)">Edit</button></td>
                    <td><?php echo htmlspecialchars(date('M/d/Y', strtotime($task['due_date']))); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<!-- View File Modal -->
<div id="viewFileModal" class="file-modal" style="display:none;">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>View Files</h2>
        <div id="modalBody">
            <!-- Content will be loaded here via JavaScript -->
        </div>
    </div>
    <style>
        .file-modal {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 1; /* Sit on top */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            overflow: auto; /* Enable scroll if needed */
            background-color: rgb(0,0,0); /* Fallback color */
            background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto; /* 15% from the top and centered */
            padding: 20px;
            border: 1px solid #888;
            width: 80%; /* Could be more or less, depending on screen size */
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
        $(document).ready(function() {
            // When the user clicks on the icon
            $('.view-file-icon').click(function() {
            var taskId = $(this).data('task-id');
            var ownerId = $(this).data('owner-id');
            var semesterId = $(this).data('semester-id'); // Get semester_id
            var taskType = $(this).data('task-type'); // Get task_type
            var ownerName = $(this).data('owner-name'); // Get owner's full name
            
            // Load relevant files into the modal
            $('#modalBody').html('Loading files...'); // Show loading message
            
            // Make an AJAX request to fetch files
            $.ajax({
                url: 'fetch_files.php', // Ensure this PHP file exists and is correct
                type: 'POST',
                data: {
                    task_id: taskId,
                    owner_id: ownerId,
                    semester_id: semesterId, // Send semester_id
                    task_type: taskType // Send task_type
                },
                success: function(data) {
                    // Create a table structure
                    var modalContent = '<h3>' + ownerName + '</h3>';
                    
                    // Assuming `data` contains the file links
                    if (data.trim() !== '') {
                        modalContent +=  data ;
                    } else {
                        modalContent += '';
                    }
                    
                    modalContent += '</tbody></table>';
                    $('#modalBody').html(modalContent); // Load the table into the modal
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error("AJAX Error: " + textStatus + ": " + errorThrown);
                    $('#modalBody').html('Error loading files. Please try again later.');
                }
            });
            
            // Show the modal
            $('#viewFileModal').css('display', 'block');
        });

            // When the user clicks on <span> (x), close the modal
            $('.close').click(function() {
                $('#viewFileModal').css('display', 'none');
            });

            // When the user clicks anywhere outside of the modal, close it
            $(window).click(function(event) {
                if ($(event.target).is('#viewFileModal')) {
                    $('#viewFileModal').css('display', 'none');
                }
            });
        });
    </script>
</div>
<script>
    function editRow(button) {
        console.log('Edit button clicked'); // Debugging statement
        const row = button.closest('tr');
        const cells = row.querySelectorAll('td');

        cells.forEach((cell, index) => {
            if (index >= 5 && index <= 7) { // Columns for Quality, Efficiency, Timeliness (not Average)
                if (cell.querySelector('input')) return; // Skip if already in edit mode

                const value = cell.textContent.trim() || null;
                cell.innerHTML = `
                    <input type="number" class="edit-input" min="1" max="5" value="${value !== null ? value : ''}" placeholder="Rate (1-5)">
                `;
            }
        });

        button.textContent = 'Save';
        button.setAttribute('onclick', 'saveRow(this)');
    }

    function saveRow(button) {
        console.log('Save button clicked'); // Debugging statement
        const row = button.closest('tr');
        const cells = row.querySelectorAll('td');
        const taskId = row.dataset.taskId; // Ensure taskId is set in data attribute
        const taskType = row.dataset.taskType; // Get taskType from the row

        let updates = { taskType: taskType }; // Include taskType
        cells.forEach((cell, index) => {
            if (index >= 5 && index <= 7) { // Columns for Quality, Efficiency, Timeliness
                const input = cell.querySelector('input');
                if (input) {
                    updates[['quality', 'efficiency', 'timeliness'][index - 5]] = input.value || null;
                }
            }
        });

        // AJAX request to save updates
        fetch('update_rating.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ taskId, ...updates }), // Include taskId in the request
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Rate updated successfully!');
                button.textContent = 'Edit';
                // Save the scroll position before reloading
                localStorage.setItem("scrollPos", window.scrollY);
                location.reload(); // Refresh the page
            } else {
                alert('Error updating task: ' + (data.message || 'Unknown error.'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating task.');
        });
    }

    // Restore scroll position function
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

    // Adding event listener to save scroll position before the page unloads
    window.onbeforeunload = function() {
        localStorage.setItem("scrollPos", window.scrollY);
    };
</script>
</body>
</html>
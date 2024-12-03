<?php
session_start();
include '../../dbconnections/config.php'; // Updated relative path

// Retrieve semester_id from URL parameter
$semester_id = $_POST['semester_id'];


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
        GROUP_CONCAT(DISTINCT CONCAT(ta.lastname, ' ', ta.firstname) SEPARATOR ', ') AS owner,
        ta.assignuser,

        GROUP_CONCAT(
            DISTINCT CONCAT(
                '<a href=\"view_file.php?task_id=', t.task_id, '&file_name=', a.file_name, '\" target=\"_blank\">', 
                a.file_name, 
               '</a> (', ta.firstname, ' ', ta.lastname, ' ', ta.quality, ' ', ta.efficiency, ' ', ta.timeliness, ' ', ta.average, ' ', ta.deansmessage, ', ', ta.assignuser, ' ', ta.id, ')'
            ) SEPARATOR '<br>' 
        ) AS files,
        t.quality,
        t.efficiency,
        t.timeliness,
        t.average
    FROM $task_table t
    LEFT JOIN task_attachments a 
        ON t.task_id = a.id_of_task 
        AND a.task_type = ?
    LEFT JOIN task_assignments ta 
        ON a.task_type = ta.task_type 
        AND a.id_of_semester = ta.semester_id 
        AND a.id_of_task = ta.idoftask 
        AND a.user_idnumber = ta.assignuser
    WHERE t.semester_id = ?
    GROUP BY t.task_id, t.task_name, t.description, t.documents_req, t.documents_uploaded, t.quality, t.efficiency, t.timeliness, t.average 
    ";
    

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("si", $task_type, $semester_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $tasks = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $tasks;
}

// New function to fetch assignment details
function fetchAssignmentsBySemester($semester_id) {
    global $conn;

    $query = "
        SELECT 
            ta.firstname, 
            ta.lastname, 
            ta.target, 
            ta.num_file, 
            ta.task_type,
            ta.status
        FROM task_assignments ta
        JOIN semester_tasks st ON ta.semester_id = st.semester_id
        WHERE st.semester_id = ? AND ta.status = 'approved'
        GROUP BY ta.task_type, ta.firstname, ta.lastname, ta.target, ta.num_file, ta.status
    ";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("i", $semester_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $assignments = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $assignments;
}

// Fetch assignment details
$assignments = fetchAssignmentsBySemester($semester_id);



$strategic_tasks = fetchTasksWithDetails('strategic_tasks', 'strategic', $semester_id);
$core_tasks = fetchTasksWithDetails('core_tasks', 'core', $semester_id);
$support_tasks = fetchTasksWithDetails('support_tasks', 'support', $semester_id);


// Fetch userapproval value from the database
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

// Determine button text based on userapproval
$button_text = ($current_approval === 1) ? "Unsign" : "Sign";   
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Tasks for Semester <?php echo htmlspecialchars($semester_id); ?></title>
<style>
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


    .owner-cell {
        line-height: 1.5;
        padding: 5px;
    }

    .file-attached-data {
        font-size: 10px;
    }

    @media (max-width: 768px) {
        table {
            display: block;
            overflow-x: auto;
            white-space: nowrap;
        }

        thead {
            display: none;
        }

        tr {
            display: block;
            margin-bottom: 10px;
        }

        td {
            display: block;
            text-align: right;
            padding-left: 50%;
            position: relative;
            border: 1px solid #ddd;
        }

        td::before {
            content: attr(data-label);
            position: absolute;
            left: 0;
            width: 50%;
            padding-left: 10px;
            font-weight: bold;
            background-color: #f4f4f4;
        }
    }
    .edit-btn {
        background-color: #4CAF50;
        color: #fff;
        border: none;
        padding: 10px 20px;
        font-size: 16px;
        cursor: pointer;
    }

    .edit-btn:hover {
        background-color: #3e8e41;
    }

    /* Style for the select dropdown */
    .edit-select {
        width: 100%;
        padding: 5px;
        border: 1px solid #ddd;
        border-radius: 4px;
        background-color: #fff;
        font-size: 14px;
        color: #333;
        box-shadow: 0 2px 3px rgba(0, 0, 0, 0.1);
        cursor: pointer;
        outline: none;
    }

    /* Style for the select dropdown on focus */
    .edit-select:focus {
        border-color: #4CAF50;
        box-shadow: 0 0 5px rgba(76, 175, 80, 0.5);
    }

    /* Style for the options inside the select */
    .edit-select option {
        padding: 10px;
        background-color: #fff;
        color: #333;
    }

    /* Style for the select dropdown in a disabled state */
    .edit-select:disabled {
        background-color: #f4f4f4;
        color: #999;
        cursor: not-allowed;
    }
    /* Modal Styles */
    .modal {
        display: none; /* Hidden by default */
        position: fixed; /* Stay in place */
        z-index: 1000; /* Sit on top */
        left: 0;
        top: 0;
        width: 100%; /* Full width */
        height: 100%; /* Full height */
        overflow: auto; /* Enable scroll if needed */
        background-color: rgba(0,0,0,0.4); /* Black background with opacity */
    }

    .modal-content {
        background-color: #fefefe;
        margin: 1% auto; /* 15% from the top and centered */
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

    /* Basic modal styling */
    .filemodal {
        display: none; /* Hidden by default */
        position: fixed; /* Stay in place */
        z-index: 1000; /* Sit on top */
        left: 0;
        top: 0;
        width: 100%; /* Full width */
        height: 100%; /* Full height */
        overflow: auto; /* Enable scroll if needed */
        background-color: rgba(0,0,0,0.4); /* Black background with opacity */
    }

    /* Modal content styling */
    .filemodal-content {
        background-color: #fefefe;
        margin: 1% auto; /* 15% from the top and centered */
        padding: 20px;
        border: 1px solid #888;
        width: 80%; /* Could be more or less, depending on screen size */
    }

    /* Close button styling */
    .filemodal .close {
        color: #aaa; /* Gray color for close button */
        float: right; /* Float to the right */
        font-size: 28px; /* Larger font size */
        font-weight: bold; /* Bold text */
    }

    .filemodal .close:hover,
    .filemodal .close:focus {
        color: black; /* Change color on hover/focus */
        text-decoration: none; /* Remove underline */
        cursor: pointer; /* Pointer cursor */
    }

    /* Table styling */
    #file-table {
        width: 100%; /* Full width */
        border-collapse: collapse; /* Merge borders */
        margin-top: 20px; /* Space above the table */
    }

    #file-table th,
    #file-table td {
        border: 1px solid #ddd; /* Light gray border */
        padding: 8px; /* Padding in cells */
        text-align: left; /* Left align text */
    }

    #file-table th {
        background-color: #f2f2f2; /* Light gray background for header */
        font-weight: bold; /* Bold text for header */
    }

    /* Action button styling */
    .action-button {
        background-color: #4CAF50; /* Green background */
        color: white; /* White text */
        border: none; /* No border */
        padding: 10px 15px; /* Padding inside the button */
        text-align: center; /* Center text */
        text-decoration: none; /* No underline */
        display: inline-block; /* Display as inline block */
        margin: 4px 2px; /* Margin around the button */
        cursor: pointer; /* Pointer cursor */
        border-radius: 5px; /* Rounded corners */
    }

    .action-button:hover {
        background-color: #45a049; /* Darker green on hover */
    }
</style>



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
            background-color: #23bc1d ;
            border : none ;
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
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
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
            <button class="show-approved" onclick="showModalAssignedAndApprovedModel()">Show Assigned and Approved</button>
            <button class="sign-btn" onclick="toggleApproval(<?php echo htmlspecialchars($semester_id); ?>)">
                <?php echo htmlspecialchars($button_text); ?>
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
                        button.innerText = "Unsign";
                    } else {
                        button.innerText = "Sign";
                    }
                    console.log("Approval toggled successfully!");

                    // Optionally refresh the page or keep the dropdown state
                    // location.reload(); // Uncomment if you want to refresh the page
                },
                error: function(xhr, status, error) {
                    console.error("Error toggling approval: " + error);
                }
            });
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
            <?php echo htmlspecialchars(round($progress_percentage, 2)); ?>%
        </div>
    </div>
</div>

<!-- The modal structure -->
<!-- The modal structure -->
<div class="modal" id="assignmentsModal" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); z-index: 9999;">
    <div style="background-color: white; margin: 15% auto; padding: 20px; border-radius: 5px; width: 80%; max-width: 2000px;">
    <span style="cursor: pointer; float: right; font-size: 40px; padding: 10px;" onclick="closeModalforassignandapprove()">&times;</span>
        <h3 style="text-align: center; margin-bottom: 5px; font-size: 20px;">Target Accomplished by Area of Evaluation</h3>
        <div class="assignments-section" style="padding: 20px;">
            
            <div style="display: flex; justify-content: space-between; flex-wrap: nowrap;"> <!-- Ensure horizontal layout -->
                <?php
                // Initialize arrays to hold assignments by task type
                $grouped_assignments = [
                    'strategic' => [],
                    'core' => [],
                    'support' => []
                ];

                // Group assignments by task type
                foreach ($assignments as $assignment) {
                    $grouped_assignments[$assignment['task_type']][] = $assignment;
                }

                // Loop through each task type and create a column for it
                foreach ($grouped_assignments as $task_type => $grouped_assignment): ?>
                    <div style="flex: 1; min-width: 250px; margin: 10px; border: 1px solid #ddd; border-radius: 5px; overflow: hidden;">
                        <h4 style="margin: 20px; font-size: 18px; color: #333; text-align: left;"><?php echo ucfirst($task_type); ?> Assignments:</h4> <!-- Align left -->
                        <div style="max-height: 300px; overflow-y: auto;"> <!-- Scrollable tbody -->
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="background-color: #f4f4f4; font-size: 14px;"> <!-- Adjusted font size -->
                                        <th style="padding: 10px; border: 1px solid #ddd;">First Name</th>
                                        <th style="padding: 10px; border: 1px solid #ddd;">Last Name</th>
                                        <th style="padding: 10px; border: 1px solid #ddd;">Target</th>
                                        <th style="padding: 10px; border: 1px solid #ddd;">Number of Files</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($grouped_assignment)): ?>
                                        <?php foreach ($grouped_assignment as $assignment): ?>
                                        <tr style="<?php echo (htmlspecialchars($assignment['target']) == htmlspecialchars($assignment['num_file'])) ? 'background-color: rgba(144, 238, 144, 0.3);' : ''; ?>"> <!-- Subtle highlight -->
                                            <td style="padding: 10px; border: 1px solid #ddd;"><?php echo htmlspecialchars($assignment['firstname']); ?></td>
                                            <td style="padding: 10px; border: 1px solid #ddd;"><?php echo htmlspecialchars($assignment['lastname']); ?></td>
                                            <td style="padding: 10px; border: 1px solid #ddd;"><?php echo htmlspecialchars($assignment['target']); ?></td>
                                            <td style="padding: 10px; border: 1px solid #ddd;"><?php echo htmlspecialchars($assignment['num_file']); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" style="padding: 10px; border: 1px solid #ddd; text-align: center;">No assignments found for this  Area of Evaluation.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
    function showModalAssignedAndApprovedModel() {
        document.getElementById("assignmentsModal").style.display = "block";
    }

    function closeModalforassignandapprove() {
        document.getElementById("assignmentsModal").style.display = "none";
    }
</script>

<div class="tabledata" style="padding: 20px;">
    <h3 style="margin: 20px 0; font-size: 20px; color: #333;" id="strategicTasks">Strategic Tasks:</h3>
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
        <thead>
            <tr style="background-color: #f4f4f4;">
                <th rowspan="2" style="padding: 10px; border: 1px solid #ddd;">Task Name</th>
                <th rowspan="2" style="padding: 10px; border: 1px solid #ddd;">Description</th>
                <th rowspan="2" style="padding: 10px; border: 1px solid #ddd;">Documents Required</th>
                <th rowspan="2" style="padding: 10px; border: 1px solid #ddd;">Owner</th>
                <th rowspan="2" style="padding: 10px; border: 1px solid #ddd;">File Attached</th>
                <th rowspan="2" style="padding: 10px; border: 1px solid #ddd;">Progress</th>
                <th colspan="5" style="width: 400px; padding: 10px; border: 1px solid #ddd;">Rate</th>
            </tr>
            <tr>
                <th style="padding: 10px; border: 1px solid #ddd;">Q</th>
                <th style="padding: 10px; border: 1px solid #ddd;">E</th>
                <th style="padding: 10px; border: 1px solid #ddd;">T</th>
                <th style="padding: 10px; border: 1px solid #ddd;">A</th>
                <th style="padding: 10px; border: 1px solid #ddd;">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($strategic_tasks as $task): 
                $progress = ($task['documents_req'] > 0) ? ($task['documents_uploaded'] / $task['documents_req']) * 100 : 0;
            ?>
            <tr data-task-id="<?php echo htmlspecialchars($task['task_id']); ?>" data-task-type="strategic_tasks" style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px; border: 1px solid #ddd;"><?php echo htmlspecialchars($task['task_name']); ?></td>
                <td style="padding: 10px; border: 1px solid #ddd;"><?php echo htmlspecialchars($task['description']); ?></td>
                <td style="padding: 10px; border: 1px solid #ddd;"><?php echo htmlspecialchars($task['documents_req']); ?></td>
                <td class="owner-cell" style="padding: 10px; border: 1px solid #ddd;">
                    <?php
                    $owners = explode(', ', $task['owner']);
                    foreach ($owners as $owner) {
                        echo htmlspecialchars($owner) . '<br>';
                    }
                    ?>
                </td>
                <td class="file-attached-data" style="padding: 10 px; border: 1px solid #ddd;">
                    <?php
                    // Split the file list into an array
                    $files = !empty($task['files']) ? explode('<br>', $task['files']) : [];

                    // Check if there are any files
                    if (empty($files[0]) || $files[0] === 'No files attached') {
                        echo 'No files attached';
                    } else {
                        echo '<button class="show-files-btn" onclick="showFilesModal(' . htmlspecialchars($task['task_id']) . ', \'' . htmlspecialchars($task['files']) . '\')">Show Files</button>';
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
                <td style="padding: 10px; border: 1px solid #ddd;"><?php echo htmlspecialchars($task['quality']); ?></td>
                <td style="padding: 10px; border: 1px solid #ddd;"><?php echo htmlspecialchars($task['efficiency']); ?></td>
                <td id="timeliness" style="padding: 10px; border: 1px solid #ddd;"><?php echo htmlspecialchars($task['timeliness']); ?></td>
                <td style="padding: 10px; border: 1px solid #ddd;"><?php echo htmlspecialchars($task['average']); ?></td>
                <td style="padding: 10px; border: 1px solid #ddd;"><button class="edit-btn" onclick="editRow(this)">Edit</button></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h3 style="margin: 20px 0; font-size: 20px; color: #333;" id="coreTasks">Core Tasks:</h3>
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
        <thead>
            <tr style="background-color: #f4f4f4;">
                <th rowspan="2" style="padding: 10px; border: 1px solid #ddd;">Task Name</th>
                <th rowspan="2" style="padding: 10px; border: 1px solid #ddd;">Description</th>
                <th rowspan="2" style="padding: 10px; border: 1px solid #ddd;">Documents Required</th>
                <th rowspan="2" style="padding: 10px; border: 1px solid #ddd;">Owner</th>
                <th rowspan="2" style="padding: 10px; border: 1px solid #ddd;">File Attached</th>
                <th rowspan="2" style="padding: 10px; border: 1px solid #ddd;">Progress</th>
                <th colspan="5" style="width: 400px; padding: 10px; border: 1px solid #ddd;">Rate</th>
            </tr>
            <tr>
                <th style="padding: 10px; border: 1px solid #ddd;">Q</th>
                <th style="padding: 10px; border: 1px solid #ddd;">E</th>
                <th style="padding: 10px; border: 1px solid #ddd;">T</th>
                <th style="padding: 10px; border: 1px solid #ddd;">A</th>
                <th style="padding: 10px; border: 1px solid #ddd;">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($core_tasks as $task): 
                $progress = ($task['documents_req'] > 0) ? ($task['documents_uploaded'] / $task['documents_req']) * 100 : 0;
            ?>
            <tr data-task-id="<?php echo htmlspecialchars($task['task_id']); ?>" data-task-type="core_tasks" style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px; border: 1px solid #ddd;"><?php echo htmlspecialchars($task['task_name']); ?></td>
                <td style="padding: 10px; border: 1px solid #ddd;"><?php echo htmlspecialchars($task['description']); ?></td>
                <td style="padding: 10px; border: 1px solid #ddd;"><?php echo htmlspecialchars($task['documents_req']); ?></td>
                <td class="owner-cell" style="padding: 10px; border: 1px solid #ddd;">
                    <?php
                    $owners = explode(', ', $task['owner']);
                    foreach ($owners as $owner) {
                        echo htmlspecialchars($owner) . '<br>';
                    }
                    ?>
                </td>
                <td class="file-attached-data" style="padding: 10px; border: 1px solid #ddd;">
                    <?php
                    // Split the file list into an array
                    $files = explode('<br>', $task['files']);

                    // Check if there are any files
                    if (empty($files[0]) || $files[0] === 'No files attached') {
                        echo 'No files attached';
                    } else {
                        echo '<button class="show-files-btn" onclick="showFilesModal(' . htmlspecialchars($task['task_id']) . ', \'' . htmlspecialchars($task['files']) . '\')">Show Files</button>';
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
                <td style="padding: 10px; border: 1px solid #ddd;"><?php echo htmlspecialchars($task['quality']); ?></td>
                <td style="padding: 10px; border: 1px solid #ddd;"><?php echo htmlspecialchars($task['efficiency']); ?></td>
                <td id="timeliness" style="padding: 10px; border: 1px solid #ddd;"><?php echo htmlspecialchars($task['timeliness']); ?></td>
                <td style="padding: 10px; border: 1px solid #ddd;"><?php echo htmlspecialchars($task['average']); ?></td>
                <td style="padding: 10px; border: 1px solid #ddd;"><button class="edit-btn" onclick="editRow(this)">Edit</button></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h3 style="margin: 20px 0; font-size: 20px; color: #333;" id="supportTasks">Support Tasks:</h3>
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
        <thead>
            <tr style="background-color: #f4f4f4;">
                <th rowspan="2" style="padding: 10px; border: 1px solid #ddd;">Task Name</th>
                <th rowspan="2" style="padding: 10px; border: 1px solid #ddd;">Description</th>
                <th rowspan="2" style="padding: 10px; border: 1px solid #ddd;">Documents Required</th>
                <th rowspan="2" style="padding: 10px; border: 1px solid #ddd;">Owner</th>
                <th rowspan="2" style="padding: 10px; border: 1px solid #ddd;">File Attached</th>
                <th rowspan="2" style="padding: 10px; border: 1px solid #ddd;">Progress</th>
                <th colspan="5" style="width: 400px; padding: 10px; border: 1px solid #ddd;">Rate</th>
            </tr>
            <tr>
                <th style="padding: 10px; border: 1px solid #ddd;">Q</th>
                <th style="padding: 10px; border: 1px solid #ddd;">E</th>
                <th style="padding: 10px; border: 1px solid #ddd;">T</th>
                <th style="padding: 10px; border: 1px solid #ddd;">A</th>
                <th style="padding: 10px; border: 1px solid #ddd;">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($support_tasks as $task): 
                $progress = ($task['documents_req'] > 0) ? ($task['documents_uploaded'] / $task ['documents_req']) * 100 : 0;
            ?>
            <tr data-task-id="<?php echo htmlspecialchars($task['task_id']); ?>" data-task-type="support_tasks" style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px; border: 1px solid #ddd;"><?php echo htmlspecialchars($task['task_name']); ?></td>
                <td style="padding: 10px; border: 1px solid #ddd;"><?php echo htmlspecialchars($task['description']); ?></td>
                <td style="padding: 10px; border: 1px solid #ddd;"><?php echo htmlspecialchars($task['documents_req']); ?></td>
                <td class="owner-cell" style="padding: 10px; border: 1px solid #ddd;">
                    <?php
                    $owners = explode(', ', $task['owner']);
                    foreach ($owners as $owner) {
                        echo htmlspecialchars($owner) . '<br>';
                    }
                    ?>
                </td>
                <td class="file-attached-data" style="padding: 10px; border: 1px solid #ddd;">
                    <?php
                    // Split the file list into an array
                    $files = explode('<br>', $task['files']);

                    // Check if there are any files
                    if (empty($files[0]) || $files[0] === 'No files attached') {
                        echo 'No files attached';
                    } else {
                        echo '<button class="show-files-btn" onclick="showFilesModal(' . htmlspecialchars($task['task_id']) . ', \'' . htmlspecialchars($task['files']) . '\')">Show Files</button>';
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
                <td style="padding: 10px; border: 1px solid #ddd;"><?php echo htmlspecialchars($task['quality']); ?></td>
                <td style="padding: 10px; border: 1px solid #ddd;"><?php echo htmlspecialchars($task['efficiency']); ?></td>
                <td id="timeliness" style="padding: 10px; border: 1px solid #ddd;"><?php echo htmlspecialchars($task['timeliness']); ?></td>
                <td style="padding: 10px; border: 1px solid #ddd;"><?php echo htmlspecialchars($task['average']); ?></td>
                <td style="padding: 10px; border: 1px solid #ddd;"><button class="edit-btn" onclick="editRow(this)">Edit</button></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div id="filesModal" class="filemodal">
    <div class="filemodal-content">
        <span class="close" onclick="closeFilesModal()">&times;</span>
        <h2>Files Attached</h2>
        <table id="file-table">
            <thead>
                <tr>
                    <th style="text-align: center;">File Attach</th>
                    <th style="text-align: center;">Name</th>
                    <th style="text-align: center; width: 100px;">Quality</th>
                    <th style="text-align: center;">Action</th>
                </tr>
            </thead>
            <tbody id="modal-file-list"></tbody>
        </table>
    </div>
</div>




    <!-- Modal Structure -->
<div id="myModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <iframe id="modalIframe" src="" width="100%" height="600px" frameborder="0"></iframe>
    </div>
</div>
<script>
    function editRow(button) {
        const row = button.closest('tr');
        const cells = row.querySelectorAll('td');

        cells.forEach((cell, index) => {
            if (index >= 6 && index < 9) { // Columns for Quality, Efficiency, Timeliness
                if (cell.querySelector('input')) return; // Skip if already in edit mode

                // Check if cell textContent is empty (for null values)
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
        const row = button.closest('tr');
        const cells = row.querySelectorAll('td');
        const taskId = row.dataset.taskId; // Ensure taskId is set in data attribute
        const taskType = row.dataset.taskType; // Make sure taskType is correctly set

        let updates = {};
        cells.forEach((cell, index) => {
            if (index >= 6 && index < 9) { // Columns for Quality, Efficiency, Timeliness
                const input = cell.querySelector('input');
                if (input) {
                    updates[['quality', 'efficiency', 'timeliness'][index - 6]] = input.value || null;
                }
            }
        });

        // AJAX request to save updates
        fetch('update_rating.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ taskId, taskType, ...updates }), // Include taskType in the request
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Rate updated successfully!');
                button.textContent = 'Edit';
                location.reload(); // Optionally refresh the page or update the UI
            } else {
                alert('Error updating task.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating task.');
        });
    }
</script>
<script>
    function checkAndHideEditButton(row) {
        // Get the values of the quality, efficiency, and timeliness column
        var timeliness = row.querySelector('#timeliness').textContent.trim();

        // Find the edit button
        var editButton = row.querySelector('.edit-btn');

        // Check if any of the columns are empty or null
        if (!quality || !efficiency || !timeliness) {
            // Hide the button if any of the values are missing
            editButton.style.display = 'none';
        } else {
            // Ensure the button is visible if all values are present
            editButton.style.display = 'inline-block';
        }
    }

    // Call this function for each row after the page loads
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('tbody tr').forEach(row => checkAndHideEditButton(row));
    });
</script>

<script>
 document.addEventListener('DOMContentLoaded', function () {
    const generateBtn = document.querySelector('.generate-btn');
    const myModal = document.getElementById('myModal');
    const filesModal = document.getElementById('filesModal');
    const closeMyModal = document.querySelector('.modal .close');
    const closeFilesModalBtn = document.querySelector('.filemodal .close');

    generateBtn.addEventListener('click', function () {
        // Get the semester_id value
        const semesterId = <?php echo json_encode($semester_id); ?>;
        
        // Set the iframe source with the semester_id as a parameter
        modalIframe.src = `generateform.php?semester_id=${semesterId}`;
        myModal.style.display = 'block';
    });

    closeMyModal.addEventListener('click', function () {
        closeModal(myModal);
    });

    closeFilesModalBtn.addEventListener('click', function () {
        closeModal(filesModal);
    });

        window.addEventListener('click', function (event) {
            if (event.target === myModal) {
                closeModal(myModal);
            }
            if (event.target === filesModal) {
                closeModal(filesModal);
            }
        });
    });

    // Function to close modals
    function closeModal(modal) {
        modal.style.display = 'none';
    }
</script>

<script>
    function showFilesModal(taskId, files) {
        const modal = document.getElementById("filesModal");
        const fileList = document.getElementById("modal-file-list");

        // Clear previous file list
        fileList.innerHTML = '';

        // Split files and process them
        const fileArray = files.split('<br>');
        const userFiles = {};

        fileArray.forEach(file => {
            if (file) {
                // Extract file link and details
                const fileLinkMatch = file.match(/<a.*?<\/a>/); // Extract file link
                const detailsMatch = file.match(/\((.*?)\)/); // Extract details (firstname lastname quality efficiency timeliness average assignuser id)

                const fileLink = fileLinkMatch ? fileLinkMatch[0] : 'No file link';
                const details = detailsMatch ? detailsMatch[1].split(' ') : ['N/A'];

                // Extract details into variables
                const firstName = details[0];
                const lastName = details[1];
                const quality = details[2] || "N/A"; // String
                const assignUser = details[details.length - 2]; // Second last item (String)
                const id = parseInt(details[details.length - 1], 10) || "N/A"; // Convert to Integer, default to "N/A"

                // If user already exists in the object, append the file link
                const userKey = `${firstName} ${lastName}`;
                if (userFiles[userKey]) {
                    userFiles[userKey].files.push(fileLink);
                } else {
                    userFiles[userKey] = {
                        firstName: firstName,
                        lastName: lastName,
                        files: [fileLink],
                        quality: quality,
                        assignUser: assignUser,
                        id: id // Store the assignuser and id
                    };
                }
            }
        });

        // Create rows for each user
        for (const userKey in userFiles) {
            const user = userFiles[userKey];
            const fileLinks = user.files.map((file, index) => `${index + 1}. ${file}`).join('<br>'); // Number each file link

            const row = document.createElement("tr");
            row.innerHTML = `
                <td style="font-size: 12px;">${fileLinks}</td>
                <td>${user.lastName}, ${user.firstName}</td>
                <td>${user.quality}</td>
                <td style="text-align: center;">
                    <button class="action-button" onclick="editUser('${user.assignUser}', '${user.id}', '${user.quality}')">
                        <i class="fas fa-pencil-alt" aria-hidden="true"></i> Edit Quality
                    </button>

                <button class="action-button" onclick="sendMessage('${user.assignUser}', '${user.id}')">
                    <i class="fas fa-envelope" aria-hidden="true"></i>
                </button>

                </td>
            `;
            fileList.appendChild(row);
        }

        // Show the modal
        modal.style.display = "block";
    }

    // Placeholder functions for edit and send message
    function editUser(assignUser, id, currentQuality) {
        // Display a prompt to get new quality value from the user
        const newQuality = prompt("Enter new quality value:", currentQuality);

        if (newQuality !== null) {
            // If the user clicked "OK", proceed with the update
            updateQuality(assignUser, id, newQuality);
        }
    }

    function updateQuality(assignUser, id, quality) {
        const xhr = new XMLHttpRequest();
        xhr.open("POST", "update_quality.php", true); // Change to your PHP file for updating quality
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onload = function () {
            if (xhr.status === 200) {
                // Handle the response if needed
                alert("Quality updated successfully!");
                // Optionally, you can refresh the page or update the UI
                location.reload(); // or update the UI to reflect the change
            } else {
                alert("Error updating quality.");
            }
        };
        xhr.send("assignUser=" + encodeURIComponent(assignUser) + "&id=" + encodeURIComponent(id) + "&quality=" + encodeURIComponent(quality));
    }

    function sendMessage(assignUser) {
        console.log(`Sending message to user: ${assignUser}`);
    }

    function sendMessage(assignUser, id) {
        // Prompt the user for a message
        const message = prompt("Enter your message:");

        if (message !== null) {
            // If the user clicked "OK", proceed to send the message
            saveMessage(assignUser, id, message);
        }
    }

    function saveMessage(assignUser, id, message) {
        const xhr = new XMLHttpRequest();
        xhr.open("POST", "send_note_to_ipcr.php", true); // Change to your PHP file for handling messages
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onload = function () {
            if (xhr.status === 200) {
                // Handle the response if needed
                alert("Message sent successfully!");
                // Optionally, refresh the page or update the UI
                location.reload(); // or update the UI to reflect the change
            } else {
                alert("Error sending message.");
            }
        };
        xhr.send("assignUser=" + encodeURIComponent(assignUser) + "&id=" + encodeURIComponent(id) + "&message=" + encodeURIComponent(message));
    }


</script>

</body>
</html>

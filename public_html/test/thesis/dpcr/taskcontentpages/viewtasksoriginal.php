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
        GROUP_CONCAT(DISTINCT CONCAT(ta.lastname, ' ', ta.firstname, ' (', ta.assignuser, ')') SEPARATOR ', ') AS owner,
        GROUP_CONCAT(
            DISTINCT CONCAT(
                '<a href=\"view_file.php?task_id=', t.task_id, '&file_name=', a.file_name, '\" target=\"_blank\">', 
                a.file_name, 
               '</a> (', ta.firstname, ' ', ta.lastname, ' ', ta.quality, ' ', ta.efficiency, ' ', ta.timeliness, ' ', ta.average, ')'
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
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Tasks for Semester <?php echo htmlspecialchars($semester_id); ?></title>
<style>
    body {
        font-family: Arial, sans-serif;
        color: #333;
        margin: 0;
        padding: 0;
        background-color: #f9f9f9;
    }

    .header {
        text-align: center;
        background-color: #ffffff;
        padding: 20px;
        border-bottom: 2px solid #ddd;
        margin-bottom: 20px;
    }

    .header h2 {
        margin: 0;
        font-size: 30px;
        margin-bottom: 40px;
        color: #333;
    }

    .header p {
        font-size: 18px;
        color: #555;
        margin: 5px 0;
    }

    .tabledata {
        padding: 0 20px 20px;
    }

    .tabledata h3 {
        font-size: 22px;
        color: #333;
        margin-bottom: 15px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 30px;
        background-color: #fff;
        table-layout: fixed;
    }

    table th, table td {
        border: 1px solid #ddd;
        padding: 10px;
        text-align: left;
        word-wrap: break-word;
        overflow-wrap: break-word;
    }

    table th {
        text-align: center;
        background-color: #f4f4f4;
        color: #333;
        font-weight: bold;
    }

    table tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    table tr:hover {
        background-color: #f1f1f1;
    }

    .progress-bar-container {
        width: 100%;
        background-color: #f3f3f3;
        border-radius: 5px;
        overflow: hidden;
        margin-top: 5px;
    }

    .progress-bar {
        height: 20px;
        background-color: #4caf50;
        color: white;
        text-align: center;
        line-height: 20px;
        border-radius: 5px;
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
            justify-content: flex-end; /* Align to the right */
            padding: 10px; /* Add some padding for spacing */
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
    
<div class="dropdown">
        <button class="hamburger" onclick="toggleDropdown()">&#9776;</button>
        <div class="dropdown-content" id="dropdownContent">
            <?php if ($showButton): ?>
                <button class="generate-btn">Generate into Forms</button>
            <?php endif; ?>
            <button class="sign-btn" onclick="toggleApproval(<?php echo htmlspecialchars($semester_id); ?>)">
                <?php echo htmlspecialchars($button_text); ?>
            </button>
            <button class="close-tab-btn" onclick="closeTab()">Close This Tab</button>
        </div>
    </div>
    <script>
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


    <div class="header">
        <h2><?php echo htmlspecialchars($semester['semester_name']); ?></h2>
        <p><strong></strong> <?php echo date('F d, Y', strtotime($semester['start_date'])); ?> -  <?php echo date('F d, Y', strtotime($semester['end_date'])); ?></p>
        <p><strong></strong></p>
        <p><strong>Progress:</strong>
            <div class="progress-bar-container">
                <div class="progress-bar" style="width: <?php echo htmlspecialchars($progress_percentage); ?>%;">
                    <?php echo htmlspecialchars(round($progress_percentage, 2)); ?>%
                </div>
            </div>
        </p>
    </div>

    <div class="tabledata">
        <h3>Strategic Tasks:</h3>
        <table>
    <thead>
        <tr>
            <th rowspan="2">Task Name</th>
            <th rowspan="2">Description</th>
            <th rowspan="2">Documents Required</th>
            <th rowspan="2">Owner</th>
            <th rowspan="2">File Attached</th>
            <th rowspan="2">Progress</th>
            <th colspan="5" style="width : 400px;">Rate</th> <!-- Parent header -->
        </tr>
        <tr>
            <th>Q</th>
            <th>E</th>
            <th>T</th> <!-- Child headers -->
            <th>A</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($strategic_tasks as $task): 
            $progress = ($task['documents_req'] > 0) ? ($task['documents_uploaded'] / $task['documents_req']) * 100 : 0;
        ?>
       <tr data-task-id="<?php echo htmlspecialchars($task['task_id']); ?>" data-task-type="strategic_tasks">
            <td><?php echo htmlspecialchars($task['task_name']); ?></td>
            <td><?php echo htmlspecialchars($task['description']); ?></td>
            <td><?php echo htmlspecialchars($task['documents_req']); ?></td>
            <td class="owner-cell">
                <?php
                $owners = explode(', ', $task['owner']);
                foreach ($owners as $owner) {
                    echo htmlspecialchars($owner) . '<br>';
                }
                ?>
            </td>
            <td class="file-attached-data">
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
            <td>
                <div class="progress-bar-container">
                    <div class="progress-bar" style="width: <?php echo htmlspecialchars($progress); ?>%;">
                        <?php echo htmlspecialchars(round($progress, 2)); ?>%
                    </div>
                </div>
            </td>
            <td><?php echo htmlspecialchars($task['quality']); ?></td>
            <td><?php echo htmlspecialchars($task['efficiency']); ?></td>
            <td id="timeliness"><?php echo htmlspecialchars($task['timeliness']); ?></td>
            <td><?php echo htmlspecialchars($task['average']); ?></td>
            <td><button class="edit-btn" onclick="editRow(this)">Edit</button></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>


        <h3>Core Tasks:</h3>
        <table>
            <thead>
                <tr>
                    <th rowspan="2">Task Name</th>
                    <th rowspan="2">Description</th>
                    <th rowspan="2">Documents Required</th>
                    <th rowspan="2">Owner</th>
                    <th rowspan="2">File Attached</th>
                    <th rowspan="2">Progress</th>
                    <th colspan="5" style="width : 400px;">Rate</th> <!-- Parent header -->
                </tr>
                <tr>
                    <th>Q</th>
                    <th>E</th>
                    <th>T</th> <!-- Child headers -->
                    <th>A</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($core_tasks as $task): 
                    $progress = ($task['documents_req'] > 0) ? ($task['documents_uploaded'] / $task['documents_req']) * 100 : 0;
                ?>
               <tr data-task-id="<?php echo htmlspecialchars($task['task_id']); ?>" data-task-type="core_tasks">
                    <td><?php echo htmlspecialchars($task['task_name']); ?></td>
                    <td><?php echo htmlspecialchars($task['description']); ?></td>
                    <td><?php echo htmlspecialchars($task['documents_req']); ?></td>
                    <td class="owner-cell">
                        <?php
                        $owners = explode(', ', $task['owner']);
                        foreach ($owners as $owner) {
                            echo htmlspecialchars($owner) . '<br>';
                        }
                        ?>
                    </td>
                    
                    <td class="file-attached-data">
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

                    <td>
                        <div class="progress-bar-container">
                            <div class="progress-bar" style="width: <?php echo htmlspecialchars($progress); ?>%;">
                                <?php echo htmlspecialchars(round($progress, 2)); ?>%
                            </div>
                        </div>
                    </td>
                    <td id="quality"><?php echo htmlspecialchars($task['quality']); ?></td>
                    <td id="efficiency"><?php echo htmlspecialchars($task['efficiency']); ?></td>
                    <td id="timeliness"><?php echo htmlspecialchars($task['timeliness']); ?></td>
                    <td><?php echo htmlspecialchars($task['average']); ?></td>
                    <td><button class="edit-btn" onclick="editRow(this)">Edit</button></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h3>Support Tasks:</h3>
        <table>
            <thead>
                <tr>
                    <th rowspan="2">Task Name</th>
                    <th rowspan="2">Description</th>
                    <th rowspan="2">Documents Required</th>
                    <th rowspan="2">Owner</th>
                    <th rowspan="2">File Attached</th>
                    <th rowspan="2">Progress</th>
                    <th colspan="5" style="width : 400px;">Rate</th> <!-- Parent header -->
                </tr>
                <tr>
                    <th>Q</th>
                    <th>E</th>
                    <th>T</th> <!-- Child headers -->
                    <th>A</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($support_tasks as $task): 
                    $progress = ($task['documents_req'] > 0) ? ($task['documents_uploaded'] / $task['documents_req']) * 100 : 0;
                ?>
              <tr data-task-id="<?php echo htmlspecialchars($task['task_id']); ?>" data-task-type="support_tasks">
                    <td><?php echo htmlspecialchars($task['task_name']); ?></td>
                    <td><?php echo htmlspecialchars($task['description']); ?></td>
                    <td><?php echo htmlspecialchars($task['documents_req']); ?></td>
                    <td class="owner-cell">
                        <?php
                        $owners = explode(', ', $task['owner']);
                        foreach ($owners as $owner) {
                            echo htmlspecialchars($owner) . '<br>';
                        }
                        ?>
                    </td>
                    <td class="file-attached-data">
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
                    <td>
                        <div class="progress-bar-container">
                            <div class="progress-bar" style="width: <?php echo htmlspecialchars($progress); ?>%;">
                                <?php echo htmlspecialchars(round($progress, 2)); ?>%
                            </div>
                        </div>
                    </td>
                    <td id="quality"><?php echo htmlspecialchars($task['quality']); ?></td>
                    <td id="efficiency"><?php echo htmlspecialchars($task['efficiency']); ?></td>
                    <td id="timeliness"><?php echo htmlspecialchars($task['timeliness']); ?></td>
                    <td><?php echo htmlspecialchars($task['average']); ?></td>
                    <td><button class="edit-btn" onclick="editRow(this)">Edit</button></td>
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
                    <th>File Attach</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Q</th>
                    <th>E</th>
                    <th>T</th>
                    <th>A</th>
                    <th>Action</th>
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
                if (cell.querySelector('select')) return; // Skip if already in edit mode

                // Check if cell textContent is empty (for null values)
                const value = cell.textContent.trim() || null;
                cell.innerHTML = `
                    <select class="edit-select">
                        <option value="" ${value === null ? 'selected' : ''}>Select Rating</option>
                        <option value="1" ${value === '1' ? 'selected' : ''}>1 (Poor)</option>
                        <option value="2" ${value === '2' ? 'selected' : ''}>2 (Fair)</option>
                        <option value="3" ${value === '3' ? 'selected' : ''}>3 (Good)</option>
                        <option value="4" ${value === '4' ? 'selected' : ''}>4 (Very Good)</option>
                        <option value="5" ${value === '5' ? 'selected' : ''}>5 (Excellent)</option>
                    </select>
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
                const select = cell.querySelector('select');
                if (select) {
                    updates[['quality', 'efficiency', 'timeliness'][index - 6]] = select.value || null;
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
    
    // Create an object to store files by user
    const userFiles = {};

    // Loop through files and assign metrics to users
    fileArray.forEach((file) => {
        if (file) {
            // Extract file details including first and last name
            const fileLinkMatch = file.match(/<a.*?<\/a>/); // Extract file link
            const nameMatch = file.match(/\((.*?)\)/); // Extract name (firstname lastname)
            const metricsMatch = file.match(/\(([^)]+)\)$/); // Extract metrics

            const fileLink = fileLinkMatch ? fileLinkMatch[0] : 'No file link';
            const fullName = nameMatch ? nameMatch[1].split(' ') : ['N/A', 'N/A'];
            const firstName = fullName[0];
            const lastName = fullName[1];

            // Extract metrics (quality, efficiency, timeliness, average)
            const metrics = metricsMatch ? metricsMatch[1].split(' ') : [];
            const quality = metrics[0] || 'N/A';
            const efficiency = metrics[1] || 'N/A';
            const timeliness = metrics[2] || 'N/A';
            const average = metrics[3] || 'N/A';

            // Find corresponding metrics for this user based on the name
            const userKey = `${firstName} ${lastName}`;

            // Store user details in the object if not already present
            if (!userFiles[userKey]) {
                userFiles[userKey] = {
                    firstName: firstName,
                    lastName: lastName,
                    files: [],
                    quality: quality,
                    efficiency: efficiency,
                    timeliness: timeliness,
                    average: average,
                };
            }

            // Add file link to the user's file list
            userFiles[userKey].files.push(fileLink);
        }
    });

    // Create rows for each user
    for (const userKey in userFiles) {
        const user = userFiles[userKey];
        const fileLinks = user.files.map((file, index) => `${index + 1}. ${file}`).join('<br>'); // Number each file link

        const row = document.createElement("tr");
        row.innerHTML = `
            <td style="font-size: 12px;">${fileLinks}</td>
            <td>${user.firstName}</td>
            <td>${user.lastName}</td>
            <td>${user.quality}</td>
            <td>${user.efficiency}</td>
            <td>${user.timeliness}</td>
            <td>${user.average}</td>
            <td><button class="action-button">Edit</button></td>
        `;
        fileList.appendChild(row);
    }

    // Show the modal
    modal.style.display = "block";
}
</script>
</body>
</html>

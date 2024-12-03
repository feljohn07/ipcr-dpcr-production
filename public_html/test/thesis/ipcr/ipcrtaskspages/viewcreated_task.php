<?php
session_start();
include '../../dbconnections/config.php'; // Include your database connection

$designation = isset($_SESSION['designation']) ? htmlspecialchars($_SESSION['designation']) : '';
function getUserIdNumber() {
    return $_SESSION['idnumber']; // Return the user ID number from the session
}

function getDesignation() {
    return $_SESSION['designation']; 
}

function fetchTasks() {
    global $conn; // Use the global database connection

    $users_idnumber = getUserIdNumber(); // Get the user ID number
    

    // Prepare the SQL query to fetch tasks created by this user with custom ordering
    $stmt = $conn->prepare("
        SELECT st.task_id, st.group_task_id, st.task_name, st.description, st.due_date,
            st.documents_required, st.documents_uploaded, st.task_type, 
            st.id_of_semester, st.name_of_semester, 
            st.sibling_code, -- Include sibling_code in the selection
            GROUP_CONCAT(fs.file_name) AS uploaded_files,
            stt.status,
            st.quality, st.efficiency, st.timeliness, st.average,
             st.note_feedback -- Fetch note_feedback from ipcrsubmittedtask
        FROM ipcrsubmittedtask st
        LEFT JOIN ipcr_file_submitted fs ON st.task_id = fs.task_id AND st.group_task_id = fs.group_task_id
        LEFT JOIN semester_tasks stt ON st.id_of_semester = stt.semester_id
        WHERE st.idnumber = ? AND stt.status = 'undone'  -- Filter by status
        GROUP BY st.group_task_id, st.task_id, st.sibling_code -- Group by sibling_code
        ORDER BY 
            CASE st.task_type 
                WHEN 'strategic' THEN 1 
                WHEN 'core' THEN 2 
                WHEN 'support' THEN 3 
                ELSE 4 
            END;
    ");

    // Check if the statement was prepared successfully
    if (!$stmt) {
        echo "Failed to prepare statement: " . $conn->error;
        return [];
    }

    // Bind the user's idnumber to the query
    $stmt->bind_param("s", $users_idnumber);

    // Execute the query
    $stmt->execute();

    // Get the result
    $result = $stmt->get_result();

    // Create an array to store the fetched tasks
    $tasks = [];

    // Check if tasks exist
    if ($result->num_rows > 0) {
        // Fetch all tasks and store them in the array
        while ($row = $result->fetch_assoc()) {
            // Escape single quotes in task_name and description
            $row['task_name'] = addslashes($row['task_name']);
            $row['description'] = addslashes($row['description']);
            
            // Group tasks by group_task_id
            $tasks[$row['group_task_id']][] = $row; 
        }
    }

    // Close the statement
    $stmt->close();

    // Return the tasks array
    return $tasks;
}

function getSemesterId($tasks) {
    // Assuming that tasks are grouped by group_task_id
    if (!empty($tasks)) {
        // Get the semester ID from the first task of the first group
        return $tasks[array_key_first($tasks)][0]['id_of_semester'] ?? null;
    }
    return null; // Return null if no tasks are available
}

// Fetch the tasks so they are available for use in the HTML
$tasks = fetchTasks();
$users_idnumber = getUserIdNumber(); // Get user ID number
$semester_id = getSemesterId($tasks); // Get semester ID from tasks

// Now you can use $users_idnumber and $semester_id later in your code as needed

// Check conditions to hide the "Strategic" option
$designation = $_SESSION['designation'] ?? 'None'; // Example designation from session
$position = $_SESSION['position'] ?? ''; // Example position from session
$hideStrategic = false;

if ($designation === 'None' && (preg_match('/^instructor-[1-3]$/', $position) || preg_match('/^assistant-professor-[1-4]$/', $position))) {
    $hideStrategic = true; // Set the flag to hide the "Strategic" option
}


function checkTableEntry($semester_id, $idnumber, $table_name) {
    global $conn;

    // Prepare the SQL query to check for the entry
    $stmt = $conn->prepare("SELECT COUNT(*) FROM $table_name WHERE semester_id = ? AND idnumber = ?");
    $stmt->bind_param("ss", $semester_id, $idnumber);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    return $count > 0; // Return true if entry exists, false otherwise
}

function getDotsStatus($semester_id, $idnumber) {
    $designation = getDesignation();
    $tables = [];

    if ($designation === 'dean' || $designation === 'Dean') {
        $tables = [
            'first_vpaa_to_ipcr_ofdeansignature', // Replaced 'to_ipcr_signature'
            'president_first_signature_to_ipcr',
            'user_semesters',
            'for_ipcr_final_signature',
            'vpaa_to_ipcr_finalsign',
            'president_final_signature_to_ipcr'
        ];
    } else {
        $tables = [
            'to_ipcr_signature',
            'president_first_signature_to_ipcr',
            'user_semesters',
            'for_ipcr_final_signature',
            'vpaa_to_ipcr_finalsign',
            'president_final_signature_to_ipcr'
        ];
    }

    $status = [];
    foreach ($tables as $table) {
        $status[] = checkTableEntry($semester_id, $idnumber, $table);
    }
    return $status; // Returns an array of true/false values for each table
}

// Fetch the tasks so they are available for use in the HTML
$tasks = fetchTasks();
$users_idnumber = getUserIdNumber(); // Get user ID number
$semester_id = getSemesterId($tasks); 

$tables = [];
if ($designation === 'dean' || $designation === 'Dean') {
    $tables = [
        'first_vpaa_to_ipcr_ofdeansignature' => "VPAAQA first Signature", // Replaced "Dean's first Signature"
        'president_first_signature_to_ipcr' => "President's first Signature",
        'user_semesters' => "Your final Signature",
        'for_ipcr_final_signature' => "Dean's final Signature",
        'vpaa_to_ipcr_finalsign' => "VPAAQA Final Signature",
        'president_final_signature_to_ipcr' => "President's Final Signature"
    ];
} else {
    $tables = [
        'to_ipcr_signature' => "Dean's first Signature",
        'president_first_signature_to_ipcr' => "President's first Signature",
        'user_semesters' => "Your final Signature",
        'for_ipcr_final_signature' => "Dean's final Signature",
        'vpaa_to_ipcr_finalsign' => "VPAAQA Final Signature",
        'president_final_signature_to_ipcr' => "President's Final Signature"
    ];
}


?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Created Tasks</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<style>
        .container {
                width: 97%; /* Adjusted for better responsiveness */
                max-width: 100%;
                margin: 0 auto;
                padding: 20px;
                font-family: Arial, sans-serif;
                background-color: #f9f9f9;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            }

            /* Table styling */
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
                border-radius: 8px;
                overflow: hidden;
            }

            /* Table borders */
            table, th, td {
                border: 1px solid #ddd;
            }

            /* Table cell padding */
            th, td {
                padding: 10px;
                text-align: left;
            }

            /* Table header styling */
            th {
                background-color: #4CAF50;
                color: white;
                text-align: center; 
                font-size: 10px;
                padding: 5px;
            }

            /* Alternate row coloring */
            tr:nth-child(even) {
                background-color: #f2f2f2;
            }

            /* Table hover effect */
            tr:hover {
                background-color: #e0e0e0; /* Slightly darker on hover */
            }

            /* Heading */
            h3 {
                color: #333;
                font-size: 24px;
                text-align: center;
                margin-bottom: 20px;
            }

            /* Message styling */
            p {
                text-align: center;
                font-size: 12px;
                color: #555;
            }

            /* Button styling */
            .generate-button {
                background-color: #4CAF50; /* Green background */
                color: white;
                padding: 10px 15px;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                margin-bottom: 15px; /* Space below button */
                transition: background-color 0.3s; /* Smooth background transition */
            }

            .generate-button:hover {
                background-color: #45a049; /* Darker green on hover */
            }

            .Edit-button {
                background-color: #4CAF50; /* Green background */
                color: white;
                padding: 10px 15px;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                margin-bottom: 15px; /* Space below button */
                transition: background-color 0.3s; /* Smooth background transition */
            }

            .Edit-button:hover {
                background-color: #45a049; /* Darker green on hover */
            }
            /* Modal Styles */
            .ipcr-modal {
                display: none; /* Hidden by default */
                position: fixed; /* Stay in place */
                z-index: 1000; /* Sit on top */
                left: 0;
                top: 0;
                width: 100%; /* Full width */
                height: 100%; /* Full height */
                overflow: auto; /* Enable scroll if needed */
                background-color: rgba(0, 0, 0, 0.6); /* Dark background for modal */
            }

            /* Modal content */
            .ipcr-modal-content {
                background-color: #fefefe;
                margin: 5% auto; /* Centered with a slight margin */
                padding: 20px;
                border: 1px solid #888;
                width: 80%; /* Could be more or less, depending on screen size */
                max-height: 90%; /* Limit the max height of the modal content */
                overflow-y: auto; /* Enable vertical scrolling if content overflows */
                border-radius: 8px; /* Rounded corners for modal */
            }

            /* Close button */
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

            /* Notification container */
            #notification {
                display: none;
                padding: 10px;
                position: fixed;
                top: 10px;
                right: 10px;
                background-color: #28a745; /* Green background for success */
                color: white;
                z-index: 1000;
                border-radius: 5px;
            }

            /* Confirmation modal styles */
            #ipcrconfirmation-model {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.5);
                z-index: 1000;
            }

            #ipcrconfirmation-model div {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background-color: #fff;
                padding: 20px;
                border-radius: 5px;
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
            }

            .view-rate-button {
                background-color: #4CAF50; /* Green background */
                color: white;
                padding: 10px 15px;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                margin-bottom: 15px; /* Space below button */
                transition: background-color 0.3s; /* Smooth background transition */
            }

            .view-rate-button:hover {
                background-color: #45a049; /* Darker green on hover */
            }

            /* Modal container */
            .modal {
            display: none; /* Hidden by default */
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
            }

            /* Modal content box */
            .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 50%;
            border-radius: 8px;
            }

            /* Close button */
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

                    /* Modal container */
            .edit-modal {
                display: none; /* Hidden by default */
                position: fixed;
                z-index: 1;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                overflow: auto;
                background-color: rgba(0, 0, 0, 0.5); /* Semi-transparent background */
            }

            /* Modal content box */
            .edit-modal-content {
                background-color: #fefefe;
                margin: 15% auto; /* Centered with a margin */
                padding: 20px;
                border: 1px solid #888;
                width: 50%; /* Width of the modal */
                border-radius: 8px; /* Rounded corners */
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

                /* Style the submit button */
    button[type="submit"] {
        padding: 8px 16px;
        font-size: 14px;
        cursor: pointer;
        position: relative;
        overflow: hidden;
    }

    /* Spinner effect when uploading */
    .button-loading {
        color: transparent; /* Hide the button text during loading */
        pointer-events: none; /* Disable clicks */
    }

    .button-loading::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 18px;
        height: 18px;
        margin: -9px; /* Center the spinner */
        border: 2px solid black; /* Set spinner border color to black */
        border-top-color: transparent; /* Top border transparent to create spinner effect */
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
        100% {
            transform: rotate(360deg);
        }
    }

</style>
</head>
<body>
<div class="container">

<?php if (!empty($tasks)): ?>
    <?php 
    // Initialize arrays to hold tasks by semester
    $tasksBySemester = [];

    // Group tasks by semester
    foreach ($tasks as $group_task_id => $taskGroup) {
        foreach ($taskGroup as $task) {
            $tasksBySemester[$task['id_of_semester']][] = $task; // Group by semester ID
        }
    }
    uasort($tasksBySemester, function($a, $b) {
        return $b[0]['id_of_semester'] <=> $a[0]['id_of_semester'];
    });
    // Display tables for each semester
    foreach ($tasksBySemester as $semesterId => $taskGroup): ?>
        <div class="semester-container" style="margin-bottom: 30px; border: 1px solid #ccc; border-radius: 5px; padding: 10px;">
            <h4><?php echo htmlspecialchars($taskGroup[0]['name_of_semester']); ?></h4>
            <div style="display: flex; align-items: center; border: 1px solid #ccc; border-radius: 5px; padding: 5px; background-color: #f0f0f0; margin-top: 10px; margin-bottom: 20px;">
                <?php
                // Get the dots status for the current semester
                $dotsStatus = getDotsStatus($semesterId, $users_idnumber);
                $totalDots = count($dotsStatus);
                foreach ($dotsStatus as $index => $status): 
                    $colorClass = $status ? 'blue' : 'gray'; // Change color based on status
                    $tableName = array_keys($tables)[$index]; // Get the original table name
                    $displayName = $tables[$tableName]; // Get the friendly display name
                ?>
                <div style="display: flex; align-items: center; margin-right: 10px; font-size: 12px;" title="<?php echo $tableName; ?>">
                    <span class="dot" style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background-color: <?php echo $colorClass; ?>;"></span>
                    <span style="margin-left: 5px;"><?php echo $displayName; ?></span>
                    <?php if ($index < $totalDots - 1): ?>
                        <div style="margin: 0 10px; border-left: 1px solid #ccc; height: 17px;"></div> <!-- Vertical line -->
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Move buttons inside the loop -->
            <button class="generate-button" onclick="generateForms('<?php echo htmlspecialchars($taskGroup[0]['group_task_id']); ?>', '<?php echo htmlspecialchars($semesterId); ?>', '<?php echo htmlspecialchars($_SESSION['designation']); ?>')">Generate Forms</button>
            <button class="Edit-button" onclick="editTasks('<?php echo htmlspecialchars($taskGroup[0]['group_task_id']); ?>', '<?php echo htmlspecialchars($semesterId); ?>')">Edit Tasks</button>
            <?php 
        $hasFinalSignatureData = checkTableEntry($semesterId, $users_idnumber, 'president_first_signature_to_ipcr');
        if ($hasFinalSignatureData): ?>
            <button class="Edit-button" onclick="toggleSaveSemester('<?php echo htmlspecialchars($semesterId); ?>')">Final Signature</button>
        <?php endif; ?>
            
            <table>
                <tr>
                    <th rowspan="2" style="font-size: 14px; width: 300px;">Outputs</th>
                    <th rowspan="2" style="font-size: 14px; width: 300px;">Success Indicator (Target + Measures)</th>
                    <th rowspan="2" style="font-size: 14px; width: 150px;">Target</th>
                    <th rowspan="2" style="font-size: 14px; width: 150px;">Documents Uploaded</th>
                    <th rowspan="2" style="font-size: 14px; width: 150px;">Due Date</th>
                    <th colspan="4" rowspan="1">Rating</th>
                    <th rowspan="2" style="font-size: 14px; width: 150px;">Uploaded Files</th>
                    <th rowspan="2" style="font-size: 14px; width: 150px;">Feedback</th>
                    <th rowspan="2" style="font-size: 14px;">Progress</th>
                    <th rowspan="2" style="font-size: 14px; width: 150px;">Upload</th>
                </tr>
                <tr>
                    <th style="font-size: 10px;">Q</th>
                    <th style="font-size: 10px;">E</th>
                    <th style="font-size: 10px;">T</th>
                    <th style="font-size: 10px;">A</th>
                </tr>
                <?php 
                // Variable to track the current task type
                $currentTaskType = '';

                foreach ($taskGroup as $task): ?>
                    <?php if ($task['task_type'] !== $currentTaskType): ?>
                        <tr>
                            <td colspan="13" style="font-size: 15px; background-color: gray; color: white;">
                                <?php echo htmlspecialchars(ucfirst($task['task_type'])) . ' Tasks'; ?>
                            </td>
                        </tr>
                        <?php $currentTaskType = $task['task_type']; // Update the current task type ?>
                    <?php endif; ?>
                    <tr>
                        <td><?php echo htmlspecialchars(stripslashes($task['task_name'])); ?></td>
                        <td style="width: 300px; padding: 0;"><?php echo nl2br(htmlspecialchars(stripslashes($task['description']))); ?></td>
                        <td><?php echo htmlspecialchars($task['documents_required']); ?></td>
                        <td><?php echo htmlspecialchars($task['documents_uploaded']); ?></td>
                        <td style="font-size: 13px;"><?php echo date('F d, Y', strtotime($task['due_date'])); ?></td>
                        <?php
                            // Calculate progress for the current task
                            $documents_req = (int) $task['documents_required'];
                            $documents_uploaded = (int) $task['documents_uploaded'];
                            $progress = $documents_req > 0 ? round(($documents_uploaded / $documents_req) * 100) : 0;
                        ?>
                        <td><?php echo htmlspecialchars($task['quality']); ?></td>
                        <td><?php echo htmlspecialchars($task['efficiency']); ?></td>
                        <td><?php echo htmlspecialchars($task['timeliness']); ?></td>
                        <td><?php echo htmlspecialchars($task['average']); ?></td>
                        <td style="word-break: break-word; font-size: 12px; padding: 5px;">
                            <?php
                            if (!empty($task['uploaded_files'])) {
                                $uploadedFiles = explode(',', $task['uploaded_files']);
                                echo '<ul>';
                                foreach ($uploadedFiles as $file) {
                                    echo '<li>
                                            <a href="#" onclick="openFileModal(\''.urlencode($file).'\', \''.urlencode($task['task_id']).'\', \''.urlencode($task['group_task_id']).'\')"> '.htmlspecialchars($file).'</a>
                                            <button class="delete-file" data-file="'.urlencode($file).'" data-task-id="'.urlencode($task['task_id']).'" data-group-task-id="'.urlencode($task['group_task_id']).'">Delete</button>
                                        </li>';
                                }
                                echo '</ul>';
                            } else {
                                echo 'No files uploaded';
                            }
                            ?>
                        </td>
                        <td style="text-align: center;">
                            <?php if (!empty($task['note_feedback'])): ?>
                                <button class="view-feedback-button" onclick="openNoteFeedbackModal('<?php echo htmlspecialchars($task['note_feedback']); ?>')" aria-label="View Feedback" style="background: none; border: none; cursor: pointer; padding: 0;">
                                    <i class="fas fa-envelope" style="font-size: 24px; color: #007bff;"></i>
                                </button>
                            <?php endif; ?>
                        </td>
                        <td style="width: 90px; height: 200px; vertical-align: middle;">
                            <div class="circle">
                                <svg width="60" height="60">
                                    <circle class="circle-bg" cx="30" cy="30" r="25"></circle>
                                    <circle class="circle-progress" cx="30" cy="30" r="25" style="stroke-dasharray: <?php echo ($progress / 100) * (2 * pi() * 25); ?>, 157.08;"></circle>
                                </svg>
                                <div class="percentage"><?php echo $progress; ?>%</div>
                            </div>
                        </td>
                        <td>
                            <form id="ipctraskupload" method="post" enctype="multipart/form-data" style="width: 190px; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
                                <input type="hidden" name="group_task_id" value="<?php echo htmlspecialchars($task['group_task_id']); ?>">
                                <input type="hidden" name="task_id" value="<?php echo htmlspecialchars($task['task_id']); ?>">
                                <input type="hidden" name="id_of_semester" value="<?php echo htmlspecialchars($task['id_of_semester']); ?>">
                                <input type="hidden" name="task_type" value="<?php echo htmlspecialchars($task['task_type']); ?>">

                                <?php
                                $documentsRequired = (int) $task['documents_required'];
                                $documentsUploaded = (int) $task['documents_uploaded'];
                                $filesToUpload = $documentsRequired - $documentsUploaded;

                                // Show file inputs if there are remaining files to upload
                                if ($filesToUpload > 0): 
                                    for ($i = 1; $i <= $filesToUpload; $i++): ?>
                                        <input type="file" name="file[]" accept=".doc, .docx, .pdf, .jpg, .jpeg, .png" style="display: block; margin: 5px 0; width: 100%; max-width: 250px;">
                                    <?php endfor; ?>
                                    <button type="submit" style="display: block; width: 100%; padding: 5px; font-size: 14px;">Upload</button>
                                <?php else: ?>
                                    <p style="font-size: 12px;">All required documents have been uploaded.</p>
                                    <button type="button" class="add-file-button" onclick="addFileInput('<?php echo htmlspecialchars($task['task_id']); ?>')">Add File</button>
                                <?php endif; ?>
                            </form>
                            
                            <div id="additional-files-<?php echo htmlspecialchars($task['task_id']); ?>" style="display: none;">
                                <form id="additional-file-upload-<?php echo htmlspecialchars($task['task_id']); ?>" method="post" enctype="multipart/form-data">
                                    <input type="hidden" name="group_task_id" value="<?php echo htmlspecialchars($task['group_task_id']); ?>">
                                    <input type="hidden" name="task_id" value="<?php echo htmlspecialchars($task['task_id']); ?>">
                                    <input type="hidden" name="id_of_semester" value="<?php echo htmlspecialchars($task['id_of_semester']); ?>">
                                    <input type="hidden" name="task_type" value="<?php echo htmlspecialchars($task['task_type']); ?>">
                                    <div id="additional-file-inputs-container-<?php echo htmlspecialchars($task['task_id']); ?>"></div>
                                    <button type="submit" style="display: block; width: 100%; padding: 5px; font-size: 14px;">Upload Additional Files</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div> <!-- End of semester-container -->
    <?php endforeach; ?>
<?php else: ?>
    <p>No tasks created yet.</p>
<?php endif; ?>
</div>


<div id="openNoteFeedbackModal" class="note-modal">
    <div class="note-modal-content">
        <span class="close-button" onclick="closeNoteFeedbackModal()">×</span>
        <h2>Feedback</h2>
        <p id="feedbackContent"></p>
    </div>
    <style>
        /* Modal Styles */
        .note-modal {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 1; /* Sit on top */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            overflow: auto; /* Enable scroll if needed */
            background-color: rgb(0, 0, 0); /* Fallback color */
            background-color: rgba(0, 0, 0, 0.4); /* Black w/ opacity */
        }

        .note-modal-content {
            background-color: #fefefe;
            margin: 15% auto; /* 15% from the top and centered */
            padding: 20px;
            border: 1px solid #888;
            width: 80%; /* Could be more or less, depending on screen size */
        }

        .close-button {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close-button:hover,
        .close-button:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
        /* Increase the font size of the feedback content */
        #feedbackContent {
            text-align:left; 
            font-size: 15px; /* Change this value to adjust the size */
            line-height: 1.5; /* Optional: Increase line height for better readability */
            color: black; /* Optional: Change text color for better contrast */
        }
    </style>
    <script>
        function openNoteFeedbackModal(feedback) {
            // Set the feedback content in the modal
            document.getElementById('feedbackContent').innerText = feedback;

            // Display the modal
            document.getElementById('openNoteFeedbackModal').style.display = 'block';
        }

        function closeNoteFeedbackModal() {
            // Hide the modal
            document.getElementById('openNoteFeedbackModal').style.display = 'none';
        }

        // Close the modal when clicking outside of it
        window.onclick = function(event) {
            const modal = document.getElementById('openNoteFeedbackModal');
            if (event.target === modal) {
                closeNoteFeedbackModal();
            }
        }
    </script>
    </div>

    <div id="fileModal" class="modal">
        <div class="modal-content" style="width: 80%; max-width: 800px; margin: auto; padding: 20px; border-radius: 5px; background-color: white; position: relative; top: 50px;">
            <span class="close" onclick="closeModalfile()">&times;</span>
            <iframe id="fileIframe" src="" style="width: 100%; height: 500px; border: none;"></iframe>
    </div>
</div>

</div>

<!-- The Modal -->
<div id="myModal" class="ipcr-modal">
    <div class="ipcr-modal-content">
        <span class="close" id="closeModal">&times;</span>
        <iframe id="iframeContent" src="" style="width: 100%; height: 500px; border: none;"></iframe>
    </div>
</div>
  <!-- Notification Container -->
  <div id="notification" style="display: none; padding: 10px; position: fixed; top: 10px; right: 10px; background-color: #28a745; color: white; z-index: 1000; border-radius: 5px;"></div>

  <!-- ipcrconfirmation-model -->
<div id="ipcrconfirmation-model" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background-color: #fff; padding: 20px; border-radius: 5px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);">
        <p style="font-size: 22px; font-weight: bold;">Confirm Deletion</p>
        <p style="font-size: 20px;">Click OK to confirm deletion of this file.</p>
        <button id="ipcr-ok-button" style="background-color: #4CAF50; color: #fff; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">OK</button>
        <button id="ipcr-cancel-button" style="background-color: #e74c3c; color: #fff; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">Cancel</button>
    </div>
</div>



</body>
<script>
function generateForms(groupTaskId, idOfSemester, designation) {
    let url;

    // Check if the designation is "Dean"
    if (designation === 'Dean') {
        // Redirect to a different form for Dean
        url = `ipcrtaskspages/ipcrform_for_dean_ipcr.php?group_task_id=${encodeURIComponent(groupTaskId)}&id_of_semester=${encodeURIComponent(idOfSemester)}`;
    } else {
        // Default URL for other designations
        url = `ipcrtaskspages/ipcrgenerate_form.php?group_task_id=${encodeURIComponent(groupTaskId)}&id_of_semester=${encodeURIComponent(idOfSemester)}`;
    }

    // Set the iframe source and display the modal
    document.getElementById('iframeContent').src = url;
    document.getElementById('myModal').style.display = "block";
}



    // Close the modal when the user clicks on <span> (x)
    document.getElementById('closeModal').onclick = function() {
        document.getElementById('myModal').style.display = "none";
    };

    // Close the modal when the user clicks anywhere outside of the modal
    window.onclick = function(event) {
        if (event.target == document.getElementById('myModal')) {
            document.getElementById('myModal').style.display = "none";
        }
    };
    </script>

<script>
    function addFileInput(taskId) {
        const additionalFilesContainer = document.getElementById('additional-file-inputs-container-' + taskId);
        
        // Create a new file input element
        const newFileInput = document.createElement('input');
        newFileInput.type = 'file';
        newFileInput.name = 'file[]'; // Name should be the same to allow multiple file uploads
        newFileInput.accept = '.doc, .docx, .pdf, .jpg, .jpeg, .png'; // Acceptable file types
        newFileInput.style.display = 'block'; // Make it block-level
        newFileInput.style.margin = '5px 0'; // Add some margin
        newFileInput.style.width = '100%'; // Full width
        newFileInput.style.maxWidth = '250px'; // Max width

        // Append the new input to the container
        additionalFilesContainer.appendChild(newFileInput);
        
        // Show the additional files section if it's hidden
        const additionalFilesSection = document.getElementById('additional-files-' + taskId);
        additionalFilesSection.style.display = 'block'; // Show the additional file inputs container
    }

    // Handle form submission via AJAX
    document.getElementById('additional-file-upload-<?php echo htmlspecialchars($task['task_id']); ?>').addEventListener('submit', function(event) {
        event.preventDefault(); // Prevent the form from submitting the default way

        var formData = new FormData(this); // Create a FormData object from the form

        var uploadButton = this.querySelector('button[type="submit"]');
        uploadButton.disabled = true; // Disable the button during the upload
        uploadButton.innerHTML = 'Uploading...'; // Change button text to "Uploading"

        // Create an AJAX request
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'ipcrtaskspages/own_task_file_sub_add.php', true); // Send the data to 'file_submit.php'

        xhr.onload = function() {
            uploadButton.disabled = false; // Enable the button after the upload
            uploadButton.innerHTML = 'Upload Additional Files'; // Restore the button text

            if (xhr.status === 200) {
                // If the file upload is successful
                localStorage.setItem('notificationMessage', 'File uploaded successfully!');
                localStorage.setItem('notificationError', 'false'); // Success notification

                // Reload the page to show the success message
                location.reload(); // This can be removed if you want to handle the success differently
            } else {
                // If the upload failed
                localStorage.setItem('notificationMessage', 'An error occurred while uploading the file.');
                localStorage.setItem('notificationError', 'true'); // Error notification
                location.reload();
            }
        };

        xhr.onerror = function() {
            uploadButton.disabled = false;
            uploadButton.innerHTML = 'Upload Additional Files';
            localStorage.setItem('notificationMessage', 'An error occurred with the request.');
            localStorage.setItem('notificationError', 'true');
            location.reload();
        };

        // Send the form data
        xhr.send(formData);
    });

    // Check if the page should show the notification
    window.addEventListener('load', function() {
        var notification = document.getElementById('notification');
        
        if (localStorage.getItem('notificationMessage')) {
            notification.textContent = localStorage.getItem('notificationMessage');
            if (localStorage.getItem('notificationError') === 'true') {
                notification.style.backgroundColor = '#dc3545'; // Red background for error
            } else {
                notification.style.backgroundColor = '#28a745'; // Green background for success
            }
            notification.style.color = 'white';
            notification.style.display = 'block'; // Show the notification

            // Hide notification after 3 seconds
            setTimeout(function () {
                notification.style.display = 'none';
                localStorage.removeItem('notificationMessage');
                localStorage.removeItem('notificationError');
            }, 3000);
        }

        // Restore scroll position after page reload
        var scrollPosition = localStorage.getItem('scrollPosition');
        if (scrollPosition !== null) {
            window.scrollTo(0, parseInt(scrollPosition, 10));
            localStorage.removeItem('scrollPosition');
        }
    });
</script>

    <script>

document.addEventListener('submit', function(event) {
    if (event.target && event.target.matches('#ipctraskupload')) {
        event.preventDefault(); // Prevent the form from submitting the default way

        var uploadButton = event.target.querySelector('button[type="submit"]');
        uploadButton.classList.add('button-loading'); // Add loading spinner class
        uploadButton.disabled = true; // Disable the button

        // Store the original document title
        var originalTitle = document.title;
        // Set the title to indicate loading
        document.title = "Uploading...";

        var formData = new FormData(event.target);

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'ipcrtaskspages/own_taskfiles_submit.php', true);

        xhr.onload = function () {
            // Reset the title to the original after the upload completes
            document.title = originalTitle;

            uploadButton.classList.remove('button-loading'); // Remove loading spinner class
            uploadButton.disabled = false; // Re-enable the button

            if (xhr.status === 200) {
                localStorage.setItem('notificationMessage', 'Upload successfully!');
                localStorage.setItem('notificationError', 'false'); // No error
            } else {
                localStorage.setItem('notificationMessage', 'An error occurred while uploading the document.');
                localStorage.setItem('notificationError', 'true'); // Error occurred
            }

            // Save scroll position before reloading
            localStorage.setItem('scrollPosition', window.scrollY);
            location.reload(); // Reload the page to show the notification
        };

        xhr.send(formData);
    }
});

// Check if the page should show the notification
var notification = document.getElementById('notification');
if (localStorage.getItem('notificationMessage')) {
    notification.textContent = localStorage.getItem('notificationMessage');
    if (localStorage.getItem('notificationError') === 'true') {
        notification.style.backgroundColor = '#dc3545'; // Red background for error
    } else {
        notification.style.backgroundColor = '#28a745'; // Green background for success
    }
    notification.style.color = 'white';
    notification.style.display = 'block'; // Show the notification

    // Hide notification after 3 seconds
    setTimeout(function () {
        notification.style.display = 'none';
        localStorage.removeItem('notificationMessage');
        localStorage.removeItem('notificationError');
    }, 3000);
}

// Restore scroll position after page reload
window.addEventListener('load', function() {
    var scrollPosition = localStorage.getItem('scrollPosition');
    if (scrollPosition !== null) {
        window.scrollTo(0, parseInt(scrollPosition, 10));
        localStorage.removeItem('scrollPosition');
    }
});


document.addEventListener('submit', function(event) {
    if (event.target && event.target.matches('#ipctraskupload')) {
        event.preventDefault(); // Prevent the form from submitting the default way

        var uploadButton = event.target.querySelector('button[type="submit"]');
        uploadButton.classList.add('button-loading'); // Add loading spinner class
        uploadButton.disabled = true; // Disable the button

        // Store the original document title
        var originalTitle = document.title;
        // Set the title to indicate loading
        document.title = "Uploading...";

        var formData = new FormData(event.target);

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'ipcrtaskspages/own_taskfiles_submit.php', true);

        xhr.onload = function () {
            // Reset the title to the original after the upload completes
            document.title = originalTitle;

            uploadButton.classList.remove('button-loading'); // Remove loading spinner class
            uploadButton.disabled = false; // Re-enable the button

            if (xhr.status === 200) {
                localStorage.setItem('notificationMessage', 'Upload successfully!');
                localStorage.setItem('notificationError', 'false'); // No error
            } else {
                localStorage.setItem('notificationMessage', 'An error occurred while uploading the document.');
                localStorage.setItem('notificationError', 'true'); // Error occurred
            }

            // Save scroll position before reloading
            localStorage.setItem('scrollPosition', window.scrollY);
            location.reload(); // Reload the page to show the notification
        };

        xhr.send(formData);
    }
});

// Check if the page should show the notification
var notification = document.getElementById('notification');
if (localStorage.getItem('notificationMessage')) {
    notification.textContent = localStorage.getItem('notificationMessage');
    if (localStorage.getItem('notificationError') === 'true') {
        notification.style.backgroundColor = '#dc3545'; // Red background for error
    } else {
        notification.style.backgroundColor = '#28a745'; // Green background for success
    }
    notification.style.color = 'white';
    notification.style.display = 'block'; // Show the notification

    // Hide notification after 3 seconds
    setTimeout(function () {
        notification.style.display = 'none';
        localStorage.removeItem('notificationMessage');
        localStorage.removeItem('notificationError');
    }, 3000);
}

// Restore scroll position after page reload
window.addEventListener('load', function() {
    var scrollPosition = localStorage.getItem('scrollPosition');
    if (scrollPosition !== null) {
        window.scrollTo(0, parseInt(scrollPosition, 10));
        localStorage.removeItem('scrollPosition');
    }
});

    // Handle file deletion
    document.addEventListener('click', function(event) {
        if (event.target && event.target.matches('.delete-file')) {
            event.preventDefault(); // Prevent default button behavior

            // Show the confirmation modal
            const modal = document.getElementById('ipcrconfirmation-model');
            modal.style.display = 'block';

            // Get file data from the clicked element
            var file = event.target.getAttribute('data-file');
            var taskId = event.target.getAttribute('data-task-id');
            var groupTaskId = event.target.getAttribute('data-group-task-id');

            // Handle OK button click
            document.getElementById('ipcr-ok-button').onclick = function() {
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'ipcrtaskspages/delete_own_taskfile.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

                xhr.onload = function () {
                    var notification = document.getElementById('notification');
                    if (xhr.status === 200) {
                        // Set the text and show the notification
                        notification.innerText = 'File deleted successfully!';
                        notification.style.backgroundColor = '#28a745'; // Success background color
                    } else {
                        // Set the text for error and show the notification
                        notification.innerText = 'An error occurred while deleting the file.';
                        notification.style.backgroundColor = '#dc3545'; // Error background color
                        console.error('Error deleting file:', xhr.status, xhr.statusText);
                    }
                    notification.style.display = 'block';

                    // Hide the notification after 3 seconds
                    setTimeout(function() {
                        notification.style.display = 'none';
                        console.log('Reloading page...');
                        window.location.reload(); // Reload page to reflect changes
                    }, 3000);
                };

                // Send the request
                xhr.send('file=' + encodeURIComponent(file) + '&task_id=' + encodeURIComponent(taskId) + '&group_task_id=' + encodeURIComponent(groupTaskId));

                // Close the modal after submission
                modal.style.display = 'none';
            };

            // Handle Cancel button click
            document.getElementById('ipcr-cancel-button').onclick = function() {
                // Close the modal without doing anything
                modal.style.display = 'none';
            };
        }
    });

    // Handle form submission
    // Handle file deletion
    document.addEventListener('click', function(event) {
        if (event.target && event.target.matches('.delete-file')) {
            event.preventDefault(); // Prevent default button behavior

            // Show the confirmation modal
            const modal = document.getElementById('ipcrconfirmation-model');
            modal.style.display = 'block';

            // Get file data from the clicked element
            var file = event.target.getAttribute('data-file');
            var taskId = event.target.getAttribute('data-task-id');
            var groupTaskId = event.target.getAttribute('data-group-task-id');

            // Handle OK button click
            document.getElementById('ipcr-ok-button').onclick = function() {
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'ipcrtaskspages/delete_own_taskfile.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

                xhr.onload = function () {
                    if (xhr.status === 200) {
                        localStorage.setItem('notificationMessage', 'File deleted successfully!');
                        localStorage.setItem('notificationError', 'false'); // No error
                    } else {
                        localStorage.setItem('notificationMessage', 'An error occurred while deleting the file.');
                        localStorage.setItem('notificationError', 'true'); // Error occurred
                        console.error('Error deleting file:', xhr.status, xhr.statusText);
                    }
                    console.log('Reloading page...');
                    window.location.reload(); // Reload page to reflect changes
                };

                // Send the request
                xhr.send('file=' + encodeURIComponent(file) + '&task_id=' + encodeURIComponent(taskId) + '&group_task_id=' + encodeURIComponent(groupTaskId));

                // Close the modal after submission
                modal.style.display = 'none';
            };

            // Handle Cancel button click
            document.getElementById('ipcr-cancel-button').onclick = function() {
                // Close the modal without doing anything
                modal.style.display = 'none';
            };
        }
    });

    // Check if the page should show the notification
    var notification = document.getElementById('notification');
    if (localStorage.getItem('notificationMessage')) {
        notification.textContent = localStorage.getItem('notificationMessage');
        if (localStorage.getItem('notificationError') === 'true') {
            notification.style.backgroundColor = '#dc3545'; // Red background for error
        } else {
            notification.style.backgroundColor = '#28a745'; // Green background for success
        }
        notification.style.color = 'white';
        notification.style.display = 'block'; // Show the notification

        // Hide notification after 3 seconds
        setTimeout(function () {
            notification.style.display = 'none';
            localStorage.removeItem('notificationMessage');
            localStorage.removeItem('notificationError');
        }, 3000);
    }

    function toggleSaveSemester(semesterId) {
    const idnumber = '<?php echo addslashes($_SESSION['idnumber']); ?>'; // Get the user idnumber from the session

    // Ask for confirmation
    const userConfirmed = confirm("Are you sure you want to save/delete your signature ?");
    if (!userConfirmed) {
        return; // Exit the function if the user cancels
    }

    // Create a request to check if the record already exists
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'ipcrtaskspages/for_final_signature.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    xhr.onload = function () {
        if (xhr.status === 200) {
            const response = JSON.parse(xhr.responseText);

            // Store the notification message and background color in localStorage
            if (response.action === 'saved') {
                localStorage.setItem('notificationMessage', 'Signature saved successfully!');
                localStorage.setItem('notificationColor', '#28a745'); // Success background color
            } else if (response.action === 'deleted') {
                localStorage.setItem('notificationMessage', 'Signature deleted successfully!');
                localStorage.setItem('notificationColor', '#dc3545'); 
            }

            // Refresh the page immediately after the action is completed
            location.reload(); // Reload the page after the request
        } else {
            console.error('Error saving/deleting semester:', xhr.status, xhr.statusText);
        }
    };

    // Send the request with semesterId and idnumber
    xhr.send('semester_id=' + encodeURIComponent(semesterId) + '&idnumber=' + encodeURIComponent(idnumber));
    window.addEventListener('load', function () {
    const notification = document.getElementById('notification');

    // Check if the notification data is stored in localStorage
    const message = localStorage.getItem('notificationMessage');
    const color = localStorage.getItem('notificationColor');

    if (message && color) {
        // Show the notification
        notification.innerText = message;
        notification.style.backgroundColor = color;
        notification.style.display = 'block';

        // Clear the notification data from localStorage after showing it
        localStorage.removeItem('notificationMessage');
        localStorage.removeItem('notificationColor');
    }
});

}

</script>
<script>
function openFileModal(file, taskId, groupTaskId) {
    var url = 'ipcrtaskspages/own_task_view_file.php?file=' + encodeURIComponent(file) + '&task_id=' + encodeURIComponent(taskId) + '&group_task_id=' + encodeURIComponent(groupTaskId);
    document.getElementById('fileIframe').src = url; // This should open the file in the iframe
    document.getElementById('fileModal').style.display = 'block'; // Show the modal
}

    function closeModalfile() {
        document.getElementById('fileModal').style.display = 'none';
    }
</script>
<script>

function editTasks(groupTaskId, semesterId) {
    window.location.href = `ipcrtaskspages/actions/edit_tasks.php?group_task_id=${encodeURIComponent(groupTaskId)}&semester_id=${encodeURIComponent(semesterId)}`;
}
</script>
</html>
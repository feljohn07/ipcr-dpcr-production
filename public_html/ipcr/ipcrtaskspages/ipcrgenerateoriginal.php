<?php
session_start(); 

// fetching the signature
include '../../dbconnections/config.php'; // Ensure this path is correct and the file sets up $conn

$message = '';

// Check if designation is null or empty
if (empty($_SESSION['designation']) && empty($_SESSION['position'])) {
    $message = "Set your Designation and Academic Rank on the Profile First."; // Updated message
} elseif (empty($_SESSION['designation'])) {
    $message = "Set your Designation on the Profile First.";
} elseif (empty($_SESSION['position'])) {
    $message = "Set your Academic Rank on the Profile First."; // This line is already correct
}

// If a message is set, display it and exit
if (!empty($message)) {
    echo "<div style='text-align: center; margin-top: 50px;'>
            <h2>$message</h2>
          </div>";
    exit; // Stop further execution of the script
}

// Initialize variables to hold the names
$collegePresident = '';
$collegeDean = '';
$vpaaqa = '';

// Initialize weighted average variables to avoid undefined variable warnings
$weighted_average_strategic = 0;
$weighted_average_core = 0;
$weighted_average_support = 0;

// Fetch College President
$sql = "SELECT firstname, middlename, lastname FROM usersinfo WHERE role = 'College President' LIMIT 1";
$result = $conn->query($sql);
if ($result === false) {
    die("Error executing query: " . $conn->error);
}
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $collegePresident = htmlspecialchars(strtoupper($row["firstname"] . " " . $row["middlename"] . " " . $row["lastname"]), ENT_QUOTES);
}

// Fetch VPAA
$sql = "SELECT firstname, middlename, lastname FROM usersinfo WHERE role = 'VPAAQA' LIMIT 1";
$result = $conn->query($sql);
if ($result === false) {
    die("Error executing query: " . $conn->error);
}
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $vpaaqa = htmlspecialchars(strtoupper($row["firstname"] . " " . $row["middlename"] . " " . $row["lastname"]), ENT_QUOTES);
}

// Fetch College Dean (Office Head) from the same college as the logged-in user
$sql = "SELECT firstname, middlename, lastname FROM usersinfo WHERE role = 'Office Head' AND college = ? LIMIT 1";
$collegeDean_stmt = $conn->prepare($sql);
$current_user_college = $_SESSION['college']; // Assuming college is stored in session
$collegeDean_stmt->bind_param("s", $current_user_college);
$collegeDean_stmt->execute();
$collegeDean_result = $collegeDean_stmt->get_result();

if ($collegeDean_result === false) {
    die("Error executing query: " . $conn->error);
}

if ($collegeDean_result->num_rows > 0) {
    $row = $collegeDean_result->fetch_assoc();
    $collegeDean = htmlspecialchars($row["firstname"] . " " . $row["middlename"] . " " . $row["lastname"], ENT_QUOTES);
}

$collegeDean_stmt->close();

// Fetch user signature from the table "signature" database "01_users"
$signature_stmt = $conn->prepare("SELECT data FROM signature WHERE idnumber = ?");
$signature_stmt->bind_param("s", $_SESSION['idnumber']);
$signature_stmt->execute();
$signature_stmt->bind_result($signature_data);
$signature_stmt->fetch();
$signature_stmt->close();

// Fetch signatures of users with the same college and role "Office Head"
$signature_stmt = $conn->prepare("SELECT data FROM signature WHERE college = ? AND role = ?");
$current_user_college = $_SESSION['college']; // Replace with the actual column name for college
$role = 'Office Head';
$signature_stmt->bind_param("ss", $current_user_college, $role);
$signature_stmt->execute();
$signature_stmt->bind_result($signature_data_office_head);
$signature_stmt->fetch();
$signature_stmt->close();

// Fetch VPAA signature
$signature_stmt = $conn->prepare("SELECT data FROM signature WHERE role = ?");
$role = 'VPAAQA'; // Define the role you are looking for
$signature_stmt->bind_param("s", $role);
$signature_stmt->execute();
$signature_stmt->bind_result($signature_data_vpaaqa);
$signature_stmt->fetch();
$signature_stmt->close();

// Fetch college president signature from the table "signature" database "01_users"
$signature_stmt = $conn->prepare("SELECT data FROM signature WHERE role = ?");
$role = 'College president'; // Define the role you are looking for
$signature_stmt->bind_param("s", $role);
$signature_stmt->execute();
$signature_stmt->bind_result($signature_data_president);
$signature_stmt->fetch();
$signature_stmt->close();

// Fetch the user's role, designation, and position from the session
$role = $_SESSION['role']; // User's role
$designation = $_SESSION['designation']; // User's designation
$user_position = isset($_SESSION['position']) ? $_SESSION['position'] : null; // Check if position is set

// Initialize the values
$support_percentage = 'Not Defined';
$core_percentage = 'Not Defined';
$strategic_percentage = 'Not Defined';

// Check if position and designation are not empty
if (!empty ($user_position) && !empty($designation)) {
    // Check conditions for IPCR with None designation and instructor or assistant professor positions
    if ($role === 'IPCR' && $designation === 'None' && 
        (preg_match('/^instructor-[1-3]$/', $user_position) || 
         preg_match('/^assistant-professor-[1-4]$/', $user_position))) {
        
        // SQL query to fetch values for "Instructor to
        $sql_rdm = "SELECT support, core, strategic FROM rdm WHERE position = 'Instructor to Assistant Professors'";
        
        // Prepare and execute the statement
        if ($stmt = $conn->prepare($sql_rdm)) {
            $stmt->execute();
            $result_rdm = $stmt->get_result();

            // Check if results exist
            if ($result_rdm->num_rows > 0) {
                $row = $result_rdm->fetch_assoc();
                $support_percentage = $row['support'];
                $core_percentage = $row['core'];
                $strategic_percentage = $row['strategic'];
            }
            
            // Close the statement
            $stmt->close();
        } 
    }

    // Additional condition for Associate Professors and Professors
    if ($role === 'IPCR' && 
        (preg_match('/^associate-professor-[1-4]$/', $user_position) || 
        preg_match('/^professor-[1-5]$/', $user_position) || 
        $user_position === 'university-professor-1')) {

        // SQL query to fetch values for "Associate Professors to Professors"
        $sql_rdm = "SELECT support, core, strategic FROM rdm WHERE position = 'Associate Professors to Professors'";

        // Prepare and execute the statement
        if ($stmt = $conn->prepare($sql_rdm)) {
            $stmt->execute();
            $result_rdm = $stmt->get_result();

            // Check if results exist
            if ($result_rdm->num_rows > 0) {
                $row = $result_rdm->fetch_assoc();
                $support_percentage = $row['support'];
                $core_percentage = $row['core'];
                $strategic_percentage = $row['strategic'];
            }
            
            // Close the statement
            $stmt->close();
        } 
    }

    // Condition for Faculty with Designation
    if ($role === 'IPCR' && 
        $designation !== 'None' && 
        !preg_match('/^associate-professor-[1-4]$/', $user_position) && 
        !preg_match('/^professor-[1-5]$/', $user_position)) {

        // SQL query to fetch values for "Faculty with Designation"
        $sql_rdm = "SELECT support, core, strategic FROM rdm WHERE position = 'Faculty with Designation'";

        // Prepare and execute the statement
        if ($stmt = $conn->prepare($sql_rdm)) {
            $stmt->execute();
            $result_rdm = $stmt->get_result();

            // Check if results exist
            if ($result_rdm->num_rows > 0) {
                $row = $result_rdm->fetch_assoc();
                $support_percentage = $row['support'];
                $core_percentage = $row['core'];
                $strategic_percentage = $row['strategic'];
            }
            
            // Close the statement
            $stmt->close();
        } 
    }
}

// Initialize arrays to group tasks by task_type
$tasksGroupedByType = [];

// Check if both parameters are set in the URL
if (isset($_GET['group_task_id']) && isset($_GET['id_of_semester'])) {
    $group_task_id = htmlspecialchars($_GET['group_task_id']);
    $id_of_semester = htmlspecialchars($_GET['id_of_semester']);
    $userId = $_SESSION['idnumber'];

    // Prepare the SQL query to fetch semester_name, start_date, and end_date from semester_tasks
    $dateQuery = "SELECT semester_name, start_date, end_date FROM semester_tasks WHERE semester_id = ?";
    $dateStmt = $conn->prepare($dateQuery);

    if ($dateStmt === false) {
        // Handle error in preparing the statement
        echo "Error preparing statement: " . $conn->error;
        exit;
    }

    // Bind the parameter (assuming semester_id is a string)
    $dateStmt->bind_param("s", $id_of_semester);

    // Execute the statement
    $dateStmt->execute();

    // Get the result
    $dateResult = $dateStmt->get_result();

    // Fetch the dates
    if ($dateRow = $dateResult->fetch_assoc()) {
        // Convert the semester name to a safe HTML format
        $semestername = htmlspecialchars($dateRow['semester_name']); // Use fetched semester_name

        // Format the end date
        $formattedEndDate = date("F j, Y", strtotime($dateRow['end_date']));    
    } else {
        echo "No dates found for the given semester ID.";
        exit; // Stop execution if no dates are found
    }

    // Fetch College Dean (Office Head) from the same college as the logged-in user
$sql = "SELECT firstname, middlename, lastname FROM usersinfo WHERE role = 'Office Head' AND college = ? LIMIT 1";
$collegeDean_stmt = $conn->prepare($sql);
$current_user_college = $_SESSION['college']; // Assuming college is stored in session
$collegeDean_stmt->bind_param("s", $current_user_college);
$collegeDean_stmt->execute();
$collegeDean_result = $collegeDean_stmt->get_result();

$message_sql = "SELECT message FROM performance_ipcr_message WHERE idnumber = ? AND semester_id = ?";
$message_stmt = $conn->prepare($message_sql);
$message_stmt->bind_param("ss", $userId, $id_of_semester);
$message_stmt->execute();
$message_stmt->bind_result($performance_message);
$message_stmt->fetch();
$message_stmt->close();

if ($collegeDean_result === false) {
    die("Error executing query: " . $conn->error);
}

if ($collegeDean_result->num_rows > 0) {
    $row = $collegeDean_result->fetch_assoc();
    $collegeDean = htmlspecialchars(strtoupper($row["firstname"] . " " . $row["middlename"] . " " . $row["lastname"]), ENT_QUOTES);
    
    // Check if the user's ID number and semester ID exist in the to_ipcr_signature table
    $id_of_semester = htmlspecialchars($_GET['id_of_semester']); // Ensure you have the semester ID
    $userId = $_SESSION['idnumber']; // User's ID number

    $signatureCheckQuery = "SELECT idnumber FROM to_ipcr_signature WHERE idnumber = ? AND semester_id = ?";
    $signatureCheckStmt = $conn->prepare($signatureCheckQuery);
    $signatureCheckStmt->bind_param("ss", $userId, $id_of_semester);
    $signatureCheckStmt->execute();
    $signatureCheckResult = $signatureCheckStmt->get_result();

    // Check if the record exists
    $signatureExists = $signatureCheckResult->num_rows > 0;
    $signatureCheckStmt->close();

    // Fetch College Dean's signature only if the record exists
    if ($signatureExists) {
        // Fetch the signature data for the College Dean
        $signature_stmt = $conn->prepare("SELECT data FROM signature WHERE college = ? AND role = ?");
        $role = 'Office Head';
        $signature_stmt->bind_param("ss", $current_user_college, $role);
        $signature_stmt->execute();
        $signature_stmt->bind_result($signature_data_office_head);
        $signature_stmt->fetch();
        $signature_stmt->close();
    } else {
        // If the record does not exist, set the signature data to null
        $signature_data_office_head = null;
    }
} else {
    // Handle case where no College Dean was found
    $collegeDean = "No College Dean found.";
}


    // Prepare the SQL query to fetch president approval from semester _tasks
    $presidentApprovalQuery = "SELECT presidentapproval FROM semester_tasks WHERE semester_id = ?";
    $presidentApprovalStmt = $conn->prepare($presidentApprovalQuery);
    $presidentApprovalStmt->bind_param("i", $id_of_semester); // Bind as integer
    $presidentApprovalStmt->execute();
    $presidentApprovalStmt->bind_result($presidentApproval);
    $presidentApprovalStmt->fetch();
    $presidentApprovalStmt->close();

    // Example SQL query to fetch semester data
    $semester_id = $id_of_semester; // Assuming you have the semester ID from earlier code
    $semester_query = "SELECT vpapproval FROM semester_tasks WHERE semester_id = ?";
    $semester_stmt = $conn->prepare($semester_query);
    $semester_stmt->bind_param("i", $semester_id);
    $semester_stmt->execute();
    $semester_stmt->bind_result($vpapproval);
    $semester_stmt->fetch();
    $semester_stmt->close();

    // Store the result in an array or directly use it
    $semester = ['vpapproval' => $vpapproval]; // Create an array with the fetched data

    // First query: Fetch tasks from ipcrsubmittedtask including the signing_code column
    $query = "SELECT name_of_semester, documents_required, documents_uploaded, task_name, description, task_type, average, quality, efficiency, timeliness, sibling_code 
            FROM ipcrsubmittedtask 
            WHERE group_task_id = ? AND idnumber = ? 
            ORDER BY task_type"; 

    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $group_task_id, $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $strategic_tasks = [];
    $core_tasks = [];
    $support_tasks = [];

    // Group tasks by task_type
    while ($row = $result->fetch_assoc()) {
        $taskType = $row['task_type'];
        if ($taskType == 'strategic') {
            $strategic_tasks[] = $row;
        } elseif ($taskType == 'core') {
            $core_tasks[] = $row;
        } elseif ($taskType == 'support') {
            $support_tasks[] = $row;
        }
    }


    // Second query: Fetch assigned tasks from task_assignments
    $taskAssignQuery = "SELECT target, num_file, task_name, task_description, newtask_type, quality, efficiency, timeliness, average, sibling_code 
                        FROM task_assignments 
                        WHERE semester_id = ? AND assignuser = ? AND status = 'approved'";

    $taskAssignStmt = $conn->prepare($taskAssignQuery);
    $taskAssignStmt->bind_param("ss", $id_of_semester, $userId);
    $taskAssignStmt->execute();
    $taskAssignResult = $taskAssignStmt->get_result();

    // Initialize arrays to store categorized assigned tasks
    $strategic_assigned_tasks = [];
    $core_assigned_tasks = [];
    $support_assigned_tasks = [];

    // Fetch the assigned tasks and categorize them
    while ($taskAssignRow = $taskAssignResult->fetch_assoc()) {
        $taskType = $taskAssignRow['newtask_type'];
        if ($taskType == 'strategic') {
            $strategic_assigned_tasks[] = $taskAssignRow;
        } elseif ($taskType == 'core') {
            $core_assigned_tasks[] = $taskAssignRow;
        } elseif ($taskType == 'support') {
            $support_assigned_tasks[] = $taskAssignRow;
        }
    }

    // Close the statements
    $stmt->close();
    $dateStmt->close();
    $taskAssignStmt->close();
} else {
    echo "Invalid semester ID.";
}

// Initialize the average subtotal
$average_subtotal_strategic = 0;
$average_subtotal_core = 0;
$average_subtotal_support = 0;

// Calculate Average Subtotals for Strategic Tasks
$total_strategic_average = 0;
$count_strategic_tasks = 0;

foreach ($strategic_tasks as $task) {
    if (isset($task['average'])) {
        $total_strategic_average += $task['average'];
        $count_strategic_tasks++;
    }
}

foreach ($strategic_assigned_tasks as $assigned_task) {
    if (isset($assigned_task['average'])) {
        $total_strategic_average += $assigned_task['average'];
        $count_strategic_tasks++;
    }
}

if ($count_strategic_tasks > 0) {
    $average_subtotal_strategic = $total_strategic_average / $count_strategic_tasks;
}

// Calculate Average Subtotals for Core Tasks
$total_core_average = 0;
$count_core_tasks = 0;

foreach ($core_tasks as $task) {
    if (isset($task['average'])) {
        $total_core_average += $task['average'];
        $count_core_tasks++;
    }
}

foreach ($core_assigned_tasks as $assigned_task) {
    if (isset($assigned_task['average'])) {
        $total_core_average += $assigned_task['average'];
        $count_core_tasks++;
    }
}

if ($count_core_tasks > 0) {
    $average_subtotal_core = $total_core_average / $count_core_tasks;
}

// Calculate Average Subtotals for Support Tasks
$total_support_average = 0;
$count_support_tasks = 0;

foreach ($support_tasks as $task) {
    if (isset($task['average'])) {
        $total_support_average += $task['average'];
        $count_support_tasks++;
    }
}

foreach ($support_assigned_tasks as $assigned_task) {
    if (isset($assigned_task['average'])) {
        $total_support_average += $assigned_task['average'];
        $count_support_tasks++;
    }
}

if ($count_support_tasks > 0) {
    $average_subtotal_support = $total_support_average / $count_support_tasks;
}

// Now you can calculate the overall average subtotal
// Calculate Weighted Averages based on the task percentages
$weighted_average_strategic = 0;
$weighted_average_core = 0;
$weighted_average_support = 0;

if (isset($strategic_percentage) && $strategic_percentage > 0) {
    $weighted_average_strategic = ($average_subtotal_strategic * ($strategic_percentage / 100));
}

if (isset($core_percentage) && $core_percentage > 0) {
    $weighted_average_core = ($average_subtotal_core * ($core_percentage / 100));
}

if (isset($support_percentage) && $support_percentage > 0) {
    $weighted_average_support = ($average_subtotal_support * ($support_percentage / 100));
}

// Calculate the final average by summing the weighted averages
$final_average = $weighted_average_strategic + $weighted_average_core + $weighted_average_support;

// Assuming you have the user details stored in session variables
$userId = $_SESSION['idnumber'];
$userfirstname = $_SESSION['firstname'];
$userlastname = $_SESSION['lastname'];
$usercollege = $_SESSION['college'];

// Prepare the SQL statement to insert or update performance ratings
$insert_sql = "INSERT INTO ipcr_performance_rating 
                (idnumber, semester_id, weighted_average_strategic, weighted_average_core, weighted_average_support, final_average, 
                 average_subtotal_strategic, average_subtotal_core, average_subtotal_support, 
                 firstname, lastname, college) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE 
                weighted_average_strategic = VALUES(weighted_average_strategic), 
                weighted_average_core = VALUES(weighted_average_core), 
                weighted_average_support = VALUES(weighted_average_support), 
                final_average = VALUES(final_average), 
                average_subtotal_strategic = VALUES(average_subtotal_strategic),
                average_subtotal_core = VALUES(average_subtotal_core),
                average_subtotal_support = VALUES(average_subtotal_support),
                firstname = VALUES(firstname), 
                lastname = VALUES(lastname), 
                college = VALUES(college)";

$insert_stmt = $conn->prepare($insert_sql);
$insert_stmt->bind_param("ssssssssssss", 
    $userId, 
    $id_of_semester, $weighted_average_strategic, 
    $weighted_average_core, 
    $weighted_average_support, 
    $final_average, 
    $average_subtotal_strategic, 
    $average_subtotal_core, 
    $average_subtotal_support, 
    $userfirstname, 
    $userlastname, 
    $usercollege
);

// Execute the insert statement
if ($insert_stmt->execute()) {
    // Success message or further processing
} else {
    // Log the error message for debugging
    error_log("Error saving performance ratings: " . $insert_stmt->error);
    echo "Error saving performance ratings. Please try again.";
}

// Close the statement
$insert_stmt->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IPCR Document</title>
    <style>
    .container {
        width: 80%; /* Default width for screen display */
        margin: 0 auto;
        text-align: center;
        margin-top: 40px;
        margin-bottom: 20px; 
    }
    h1 {
        text-align: center;
        text-transform: uppercase;
        font-size: 18px;
        margin-bottom: 20px;
    }
    p {
        font-size: 16px;
        line-height: 1.6;
        text-align: justify;
        margin-bottom: 5px; /* Reduce the bottom margin of the paragraph */
    }



    .review-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }
    .review-table, .review-table th, .review-table td {
        border: 1px solid black;
    }
    .review-table th, .review-table td {
        padding: 10px;
    }
    .review-table td {  
        font-size: 14px;
    }
    .highlighted {
        font-size: 17px; /* Removed font-weight: bold; */
    }
    
    .no-border {
        border: none;
    }
    .section-title {
        text-align: left;
        padding-left: 10px;
    }
    .center-text {
        text-align: center;
    }
    .comment-box {
        padding: 20px;
        text-align: left;
    }
    .signature-section .name {
        /* Removed styles */
    }
    .underline {
        border-bottom: 1px solid black; /* Adjust color and thickness as needed */
        display: inline-block; /* Ensures underline is applied to inline text */
        padding-bottom: 1px; /* Adjust padding to align the underline */
    }

    /* Sticky Header Style */
    .print-container {
        position: sticky;
        top: 0;
        background-color: white;
        padding: 10px;
        z-index: 1000;
        text-align: right;
        border-bottom: 1px solid #ddd;
    }

    /* Button Style */
    .print-button {
        background-color: #4CAF50;
        color: white;
        border: none;
        padding: 10px 20px;
        cursor: pointer;
        font-size: 16px;
        border-radius: 5px;
    }

    .print-button:hover {
        background-color: #45a049;
    }

    .signature-container {
        text-align: center;
        margin-top: 40px;
    }
    .signature {
        display: inline-block;
        width: 300px;
        height: 150px;
        border: none;
        background-size: contain;
        background-repeat: no-repeat;
        background-position: center;
        
    }

    @media print {
    html, body {
        margin: 0; /* Reset margins */
        padding: 0; /* Reset padding */
        width: auto; /* Ensure full width for printing */
        overflow: visible; /* Ensure no overflow */
    }

    .container {
        width: 100%; /* Set the container to full width */
        margin: 0; /* Adjust left and right margins */
        box-sizing: border-box; /* Include padding and border in the element's total width and height */
    }

    @page {
        size: 14in 8in; /* Set page size to 8x13 inches in landscape */
        margin: 0.2in 0.2in 0.2in 0.2in; /* Top, Right, Bottom, Left margins */
    }

    .print-container {
        display: none; /* Hide print button */
    }

    .review-table {
        border-collapse: collapse;
        width: 100%;
    }

    .review-table th, .review-table td {
        border: 1px solid black;
        padding: 10px;
        page-break-inside: avoid; /* Prevent page breaks inside table cells */
    }

    .review-table td {
        overflow: hidden; /* Prevent overflow */
        white-space: nowrap; /* Prevent text wrapping */
        text-overflow: ellipsis; /* Add ellipsis for overflowed text */
    }

    .review-table td.for-signature {
        border-top: none; /* Ensure no top border */
        border-bottom: none; /* Ensure no bottom border */
        padding: 0; /* Optional: Adjust padding for print */
        height: auto; /* Optional: Adjust height for print */
    }

    button {
        display: none; /* Hides all button elements during print */
    }

    .no-signature {
        display: none; /* Hide the no-signature paragraph during printing */
    }
    
}

</style>
</head>
<body>
<button onclick="exportToWord()">Export to Word</button>
<script>
function exportToWord() {
    // Get the HTML content of the container
    var content = document.querySelector('.container').innerHTML;

    // Replace the image tags in the content to ensure they have the correct size
    content = content.replace(/<img[^>]+style="[^"]*"/g, '<img style="width: 90px; height: 70px;"');

    // Create a new Blob for the Word document with a full HTML structure and included styles
    var blob = new Blob(['<!DOCTYPE html><html><head><meta charset="utf-8"><title>Exported Document</title><style>' +
    'html, body { margin: 0; padding: 0; width: auto; overflow: visible; }' + // Reset margins and padding
    '.container { width: 100%; margin: 0; box-sizing: border-box; text-align: center; }' + // Full width for the container
    'h1 { text-align: center; text-transform: uppercase; font-size: 18px; margin-bottom: 20px; }' +
    'p { font-size: 16px; line-height: 1.6; text-align: justify; margin-bottom: 5px; }' +
    '.review-table { width: 100%; border-collapse: collapse; margin-top: 20px; }' +
    '.review-table, .review-table th, .review-table td { border: 1px solid black; }' +
    '.review-table th, .review-table td { padding: 10px; }' +
    '.review-table td { font-size: 14px; }' +
    '.highlighted { font-size: 17px; }' +
    '.no-border { border: none; }' +
    '.section-title { text-align: left; padding-left: 10px; }' +
    '.center-text { text-align: center; }' +
    '.comment-box { padding: 20px; text-align: left; }' +
    '.underline { border-bottom: 1px solid black; display: inline-block; padding-bottom: 1px; }' +
    // Styles for print
    '@media print { html, body { margin: 0; padding: 0; width: auto; overflow: visible; }' +
    '.container { width: 100%; margin: 0; box-sizing: border-box; }' +
    '@page { size: 13in 8in; margin: 0.5in 1in 0.5in 0.5in; }' + // Set page size and margins
    '.print-container { display: none; }' +
    'button { display: none; }' +
    '.no-signature { display: none; }' +
    // Table styles
    '.review-table td { padding: 0; }' +
    '.review-table td[colspan="2"] { text-align: center; vertical-align: top; padding: 0; height: 10px; }' +
    '.for-signature { padding: 0; height: 90px; width: 190px; overflow: hidden; border-bottom: none; }' +
    '.for-signature p { text-align: center; }' +
    '.for-signature span { font-size: 8px; text-align: left; margin: 0; padding: 0; letter-spacing: -0.5px; }' +
    '}' + // Close media print
    '</style></head><body>' + content + '</body></html>'], {
    type: 'application/msword'
});

    // Create a link element
    var link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'IPCR_Document.doc'; // Name of the exported file

    // Append to the body
    document.body.appendChild(link);

    // Programmatically click the link to trigger the download
    link.click();

    // Remove the link from the document
    document.body.removeChild(link);
}
    </script>

    <button 
        style="position: fixed; top: 20px; right: 20px; background-color: #007bff; color: white; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer; z-index: 1000;" 
        onmouseover="this.style.backgroundColor='#0056b3';" 
        onmouseout="this.style.backgroundColor='#007bff';" 
        onclick="printDocument()">
        Print
    </button>
    <div class="container" style="padding: 0;"> 
        <h1 style="padding-top: 0;">INDIVIDUAL PERFORMANCE COMMITMENT AND REVIEW (IPCR)</h1>
        <p>
            I, <span style="text-decoration: underline; line-height: 1.2; font-weight: bold;"><?php echo strtoupper(htmlspecialchars($_SESSION['firstname']) . ' ' . htmlspecialchars($_SESSION['middlename']) . ' ' . htmlspecialchars($_SESSION['lastname'])); ?></span> of the 
            <span style="text-decoration: underline; line-height: 1.2; font-weight: bold;"><?php echo strtoupper(htmlspecialchars($_SESSION['college'])); ?></span>
            commit to deliver and agree to be rated on the attainment of the following targets 
            in accordance with the indicated measures for the period 
            <strong style="text-decoration: underline; line-height: 1.2; font-weight: bold;"><?php echo isset($semestername) ? $semestername : 'N/A'; ?></strong>
        </p>
        <div class="signatures">
            <div class="ratee" style="margin-left : 60%;">
                <p style="text-align:center; ">
                    <span style="text-decoration: underline; line-height: 2; font-weight: bold; text-align : center;">
                        <strong><?php echo strtoupper(htmlspecialchars($_SESSION['firstname']) . ' ' . htmlspecialchars($_SESSION['middlename']) . ' ' . htmlspecialchars($_SESSION['lastname'])); ?></strong>
                    </span><br>
                    <span style="display: block; text-align: center;">Ratee</span>
                </p>
                <p style="text-align:center; ">Date : <span class="underline" style="margin-left: 5px; line-height: 0.8;"><?php echo date('F j, Y'); ?></span></p>
            </div>
        </div>
        <table class="review-table">
                <tr>
                    <th>Reviewed by:</th>
                    <th style="text-align: center;" >Date</th>
                    <th>Approved by:</th>
                    <th>Date</th>
                </tr>
                <tr>
                    <td><strong class="highlighted"><?php echo htmlspecialchars($collegeDean); ?></strong><br>Immediate Supervisor</td>
                    <td></td>
                    <td><strong class="highlighted"><?php echo htmlspecialchars($collegePresident); ?></strong><br>College President</td>
                    <td></td>
                </tr>
            </table>
        <table class="review-table">
            <div class="row header" style="width: 100%; text-align: center; font-size: 13px;">
            <tr>
                <th rowspan="2" style="font-weight: normal;">Outputs</th>
                <th rowspan="2" style="font-weight: normal;">Success Indicator<br> (Target + Measures)</th>
                <th rowspan="2" style="font-weight: normal;">Actual Accomplishment</th>
                <th colspan="4" class="center-text" style="font-weight: normal;">Rating</th>
                <th rowspan="2" style="font-weight: normal;">Remarks</th>
            </tr>
            <tr>
                <th class="center-text" style="font-weight: normal;">Q</th>
                <th class="center-text" style="font-weight: normal;">E</th>
                <th class="center-text" style="font-weight: normal;">T</th>
                <th class="center-text" style="font-weight: normal;">A</th>
            </tr>
            </div>
            <tbody>
                <?php
                // Determine whether to include strategic in the calculation
                $include_strategic = !($role === 'IPCR' && $designation === 'None' && 
                    (preg_match('/^instructor-[1-3]$/', $user_position) || 
                    preg_match('/^assistant-professor-[1-4]$/', $user_position)));

                // Only display the Strategic Priority section if the condition is not met
                if ($include_strategic): ?>
                    <tr>
                        <td class="section-title" colspan="8" style="padding: 0;">Strategic Priority (<?php echo htmlspecialchars($strategic_percentage); ?>%)</td>
                    </tr>
                    <tr>
                        <td style="padding: 0; text-align: left;">Output I Learning and Development</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <?php
// Group tasks by task_name and sibling_code
$grouped_tasks = [];
foreach ($strategic_tasks as $task) {
    $key = $task['task_name'] . '|' . $task['sibling_code']; // Create a unique key for each task
    if (!isset($grouped_tasks[$key])) {
        $grouped_tasks[$key] = [
            'task_name' => $task['task_name'],
            'sibling_code' => $task['sibling_code'],
            'description' => [],
            'documents_required' => [],
            'documents_uploaded' => [],
            'quality' => [],
            'efficiency' => [],
            'timeliness' => [],
            'average' => [],
        ];
    }
    // Add description and document values for each task in the group
    $grouped_tasks[$key]['description'][] = $task['description'];
    $grouped_tasks[$key]['documents_required'][] = $task['documents_required'];
    $grouped_tasks[$key]['documents_uploaded'][] = $task['documents_uploaded'];
    $grouped_tasks[$key]['quality'][] = $task['quality'];
    $grouped_tasks[$key]['efficiency'][] = $task['efficiency'];
    $grouped_tasks[$key]['timeliness'][] = $task['timeliness'];
    $grouped_tasks[$key]['average'][] = $task['average'];
}

// Now render the grouped tasks
foreach ($grouped_tasks as $group) {
    $rowspan = count($group['description']); // Count of tasks in this group

    // Loop through the tasks in this group to display each row
    for ($i = 0; $i < $rowspan; $i++) {
        // Only hide the top border for rows after the first one
        $topBorderStyle = ($i === 0) ? '' : 'border-top: none;';

        // Only hide the bottom border for rows before the last one
        $bottomBorderStyle = ($i === $rowspan - 1) ? '' : 'border-bottom: none;';

        // Render the task row
        ?>
        <tr>
            <?php if ($i === 0): // For the first row, merge the task name cell ?>
                <td rowspan="<?php echo $rowspan; ?>" style="width: 33%; vertical-align: top; text-align: left; padding-left: 10px; word-wrap: break-word; white-space: normal;">
                    <?php echo nl2br(htmlspecialchars($group['task_name'])); ?>
                </td>
            <?php endif; ?>

            <td style="width: 33%; vertical-align: top; text-align: left; padding-left: 10px; word-wrap: break-word; white-space: normal; <?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                <?php echo nl2br(htmlspecialchars($group['description'][$i])); ?>
            </td>
            <td style="width: 33%; vertical-align: top; text-align: left; padding-left: 10px; word-wrap: break-word; white-space: normal; <?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                <?php 
                    // Calculate and display the percentage for this task
                    $documents_req = $group['documents_required'][$i];
                    $documents_uploaded = $group['documents_uploaded'][$i];
                    $percentage = $documents_req > 0 ? ($documents_uploaded / $documents_req) * 100 : 0;
                    echo htmlspecialchars(round($percentage, 2 )) . '% of the target for ' .  nl2br(htmlspecialchars($group['description'][$i])) . ' has been accomplished.';
                ?>
            </td>
            <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>"><?php echo htmlspecialchars($group['quality'][$i]); ?></td>
            <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>"><?php echo htmlspecialchars($group['efficiency'][$i]); ?></td>
            <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>"><?php echo htmlspecialchars($group['timeliness'][$i]); ?></td>
            <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>"><?php echo htmlspecialchars($group['average'][$i]); ?></td>
            <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>"></td> <!-- Empty cell for the last column -->
        </tr>
        <?php
    }
}
?>

                    <!-- Loop through assigned strategic tasks -->
                    <?php
// Grouping assigned strategic tasks
$grouped_tasks = [];
foreach ($strategic_assigned_tasks as $task) {
    $key = $task['task_name'] . '|' . $task['sibling_code']; // Create a unique key for each task
    if (!isset($grouped_tasks[$key])) {
        $grouped_tasks[$key] = [
            'task_name' => $task['task_name'],
            'sibling_code' => $task['sibling_code'],
            'description' => [],
            'target' => [],  // Replaced 'documents_required' with 'target'
            'num_file' => [], // Replaced 'documents_uploaded' with 'num_file'
            'quality' => [],
            'efficiency' => [],
            'timeliness' => [],
            'average' => [],
        ];
    }
    // Add task values to the group
    $grouped_tasks[$key]['description'][] = $task['task_description'];
    $grouped_tasks[$key]['target'][] = $task['target']; // Replaced 'documents_required' with 'target'
    $grouped_tasks[$key]['num_file'][] = $task['num_file']; // Replaced 'documents_uploaded' with 'num_file'
    $grouped_tasks[$key]['quality'][] = $task['quality'];
    $grouped_tasks[$key]['efficiency'][] = $task['efficiency'];
    $grouped_tasks[$key]['timeliness'][] = $task['timeliness'];
    $grouped_tasks[$key]['average'][] = $task['average'];
}

foreach ($grouped_tasks as $group) {
    $rowspan = count($group['description']); // Number of rows for this group

    // Loop through the tasks in this group
    for ($i = 0; $i < $rowspan; $i++) {
        // Only hide the top border for rows after the first one
        $topBorderStyle = ($i === 0) ? '' : 'border-top: none;';
        // Only hide the bottom border for rows before the last one
        $bottomBorderStyle = ($i === $rowspan - 1) ? '' : 'border-bottom: none;';

        // Render the task row
        ?>
        <tr>
            <?php if ($i === 0): // For the first row, merge the task name cell ?>
                <td rowspan="<?php echo $rowspan; ?>" style="width: 33%; vertical-align: top; text-align: left; padding-left: 10px; word-wrap: break-word; white-space: normal;">
                    <?php echo nl2br(htmlspecialchars($group['task_name'])); ?>
                </td>
            <?php endif; ?>

            <!-- Task description column -->
            <td style="width: 33%; vertical-align: top; text-align: left; padding-left: 10px; word-wrap: break-word; white-space: normal; <?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                <?php echo nl2br(htmlspecialchars($group['description'][$i])); ?>
            </td>

            <!-- Task percentage completion column -->
            <td style="width: 33%; vertical-align: top; text-align: left; padding-left: 10px; word-wrap: break-word; white-space: normal; <?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                <?php 
                    $target = $group['target'][$i]; // Replaced 'documents_required' with 'target'
                    $num_file = $group['num_file'][$i]; // Replaced 'documents_uploaded' with 'num_file'
                    $percentage = $target > 0 ? ($num_file / $target) * 100 : 0;
                    echo htmlspecialchars(round($percentage, 2)) . '% of the target for ' . 
                    nl2br(htmlspecialchars($group['description'][$i])) . ' has been accomplished.';
                ?>
            </td>

            <!-- Quality column -->
            <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>"><?php echo htmlspecialchars($group['quality'][$i] ?? '0'); ?></td>

            <!-- Efficiency column -->
            <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>"><?php echo htmlspecialchars($group['efficiency'][$i] ?? '0'); ?></td>

            <!-- Timeliness column -->
            <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>"><?php echo htmlspecialchars($group['timeliness'][$i] ?? '0'); ?></td>

            <!-- Average column -->
            <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>"><?php echo htmlspecialchars($group['average'][$i] ?? '0'); ?></td>
        </tr>
        <?php
    }
}
?>

                    <!-- Calculate combined average sub-total -->
                    <tr>
                        <td colspan="6" style="text-align: right;"><span>Average Sub-total</span></td>
                        <td>
                            <?php 
                                $total_average = 0; 
                                $task_count = 0;

                                // Loop through strategic tasks to sum the average values
                                foreach ($strategic_tasks as $task) {
                                    if (isset($task['average'])) {
                                        $total_average += $task['average'];
                                        $task_count++; // Count each task with an average
                                    }
                                }

                                // Loop through assigned strategic tasks to sum the average values
                                foreach ($strategic_assigned_tasks as $assigned_task) {
                                    if (isset($assigned_task['average'])) {
                                        $total_average += $assigned_task['average'];
                                        $task_count++; // Count each assigned task with an average
                                    }
                                }

                                // Calculate and display the average sub-total
                                if ($task_count > 0) {
                                    $average_subtotal = $total_average / $task_count;
                                    echo htmlspecialchars(round($average_subtotal, 2));
                                } else {
                                    echo "N/A";
                                }
                            ?>
                        </td>
                        <td></td>
                    </tr>
                    <tr>
                        <td colspan="6" style="text-align: right;"><span>Weighted Average (Average Sub-total * Strategic %)</span></td>
                        <td>
                            <?php 
                                // Ensure that $strategic_percentage is treated as a percentage and that $task_count is greater than 0
                                if (isset($strategic_percentage) && $task_count > 0) {
                                    // Convert the strategic percentage to a decimal (e.g., 35 becomes 0.35)
                                    $strategic_percentage_decimal = $strategic_percentage / 100;

                                    // Calculate the weighted average by multiplying the average subtotal by the strategic percentage
                                    $weighted_average_strategic = $average_subtotal * $strategic_percentage_decimal;

                                    // Display the weighted average, rounded to 2 decimal places
                                    echo htmlspecialchars(round($weighted_average_strategic, 2));
                                } else {
                                    echo "N/A"; // Display N/A if there's no strategic percentage or no tasks
                                }
                            ?>
                        </td>
                        <td></td>
                    </tr>
                <?php endif; ?>
                <!-- Core Tasks -->
                <tr>
                    <td class="section-title" colspan="8" style="padding: 0;">Core Priority (<?php echo htmlspecialchars($core_percentage); ?>%)</td>
                </tr>
                <!-- Loop through fetched core tasks -->
                <?php
// Group tasks by task_name and sibling_code
$grouped_tasks = [];
foreach ($core_tasks as $task) {
    $key = $task['task_name'] . '|' . $task['sibling_code']; // Create a unique key for each task
    if (!isset($grouped_tasks[$key])) {
        $grouped_tasks[$key] = [
            'task_name' => $task['task_name'],
            'sibling_code' => $task['sibling_code'],
            'description' => [],
            'documents_required' => [],
            'documents_uploaded' => [],
            'quality' => [],
            'efficiency' => [],
            'timeliness' => [],
            'average' => [],
        ];
    }
    // Add description and document values for each task in the group
    $grouped_tasks[$key]['description'][] = $task['description'];
    $grouped_tasks[$key]['documents_required'][] = $task['documents_required'];
    $grouped_tasks[$key]['documents_uploaded'][] = $task['documents_uploaded'];
    $grouped_tasks[$key]['quality'][] = $task['quality'];
    $grouped_tasks[$key]['efficiency'][] = $task['efficiency'];
    $grouped_tasks[$key]['timeliness'][] = $task['timeliness'];
    $grouped_tasks[$key]['average'][] = $task['average'];
}

foreach ($grouped_tasks as $group) {
    $rowspan = count($group['description']); // Count of tasks in this group

    // Loop through the tasks in this group to display each row
    for ($i = 0; $i < $rowspan; $i++) {
        // Only hide the top border for rows after the first one
        $topBorderStyle = ($i === 0) ? '' : 'border-top: none;';

        // Only hide the bottom border for rows before the last one
        $bottomBorderStyle = ($i === $rowspan - 1) ? '' : 'border-bottom: none;';

        // Render the task row
        ?>
        <tr>
            <?php if ($i === 0): // For the first row, merge the task name cell ?>
                <td rowspan="<?php echo $rowspan; ?>" style="width: 33%; vertical-align: top; text-align: left; padding-left: 10px; word-wrap: break-word; white-space: normal;">
                    <?php echo nl2br(htmlspecialchars($group['task_name'])); ?>
                </td>
            <?php endif; ?>

            <td style="width: 33%; vertical-align: top; text-align: left; padding-left: 10px; word-wrap: break-word; white-space: normal; <?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                <?php echo nl2br(htmlspecialchars($group['description'][$i])); ?>
            </td>
            <td style="width: 33%; vertical-align: top; text-align: left; padding-left: 10px; word-wrap: break-word; white-space: normal; <?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                <?php 
                    // Calculate and display the percentage for this task
                    $documents_req = $group['documents_required'][$i];
                    $documents_uploaded = $group['documents_uploaded'][$i];
                    $percentage = $documents_req > 0 ? ($documents_uploaded / $documents_req) * 100 : 0;
                    echo htmlspecialchars(round($percentage, 2 )) . '% of the target for ' .  nl2br(htmlspecialchars($group['description'][$i])) . ' has been accomplished.';
                ?>
            </td>
            <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>"><?php echo htmlspecialchars($group['quality'][$i]); ?></td>
            <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>"><?php echo htmlspecialchars($group['efficiency'][$i]); ?></td>
            <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>"><?php echo htmlspecialchars($group['timeliness'][$i]); ?></td>
            <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>"><?php echo htmlspecialchars($group['average'][$i]); ?></td>
            <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>"></td> <!-- Empty cell for the last column -->
        </tr>
        <?php
    }
}
?>

                <!-- Loop through assigned core tasks -->
                <?php
$grouped_tasks = [];
foreach ($core_assigned_tasks as $task) {
    $key = $task['task_name'] . '|' . $task['sibling_code']; // Create a unique key for each task
    if (!isset($grouped_tasks[$key])) {
        $grouped_tasks[$key] = [
            'task_name' => $task['task_name'],
            'sibling_code' => $task['sibling_code'],
            'description' => [],
            'target' => [],  // Replaced 'documents_required' with 'target'
            'num_file' => [], // Replaced 'documents_uploaded' with 'num_file'
            'quality' => [],
            'efficiency' => [],
            'timeliness' => [],
            'average' => [],
        ];
    }
    // Add task values to the group
    $grouped_tasks[$key]['description'][] = $task['task_description'];
    $grouped_tasks[$key]['target'][] = $task['target']; // Replaced 'documents_required' with 'target'
    $grouped_tasks[$key]['num_file'][] = $task['num_file']; // Replaced 'documents_uploaded' with 'num_file'
    $grouped_tasks[$key]['quality'][] = $task['quality'];
    $grouped_tasks[$key]['efficiency'][] = $task['efficiency'];
    $grouped_tasks[$key]['timeliness'][] = $task['timeliness'];
    $grouped_tasks[$key]['average'][] = $task['average'];
}

foreach ($grouped_tasks as $group) {
    $rowspan = count($group['description']); // Number of rows for this group

    // Loop through the tasks in this group
    for ($i = 0; $i < $rowspan; $i++) {
        // Only hide the top border for rows after the first one
        $topBorderStyle = ($i === 0) ? '' : 'border-top: none;';
        // Only hide the bottom border for rows before the last one
        $bottomBorderStyle = ($i === $rowspan - 1) ? '' : 'border-bottom: none;';

        // Render the task row
        ?>
        <tr>
            <?php if ($i === 0): // For the first row, merge the task name cell ?>
                <td rowspan="<?php echo $rowspan; ?>" style="width: 33%; vertical-align: top; text-align: left; padding-left: 10px; word-wrap: break-word; white-space: normal;">
                    <?php echo nl2br(htmlspecialchars($group['task_name'])); ?>
                </td>
            <?php endif; ?>

            <!-- Task description column -->
            <td style="width: 33%; vertical-align: top; text-align: left; padding-left: 10px; word-wrap: break-word; white-space: normal; <?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                <?php echo nl2br(htmlspecialchars($group['description'][$i])); ?>
            </td>

            <!-- Task percentage completion column -->
            <td style="width: 33%; vertical-align: top; text-align: left; padding-left: 10px; word-wrap: break-word; white-space: normal; <?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                <?php 
                    $target = $group['target'][$i]; // Replaced 'documents_required' with 'target'
                    $num_file = $group['num_file'][$i]; // Replaced 'documents_uploaded' with 'num_file'
                    $percentage = $target > 0 ? ($num_file / $target) * 100 : 0;
                    echo htmlspecialchars(round($percentage, 2)) . '% of the target for ' . nl2br(htmlspecialchars($group['description'][$i])) . ' has been accomplished.';
                ?>
            </td>

            <!-- Quality column -->
            <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>"><?php echo htmlspecialchars($group['quality'][$i] ?? '0'); ?></td>

            <!-- Efficiency column -->
            <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>"><?php echo htmlspecialchars($group['efficiency'][$i] ?? '0'); ?></td>

            <!-- Timeliness column -->
            <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>"><?php echo htmlspecialchars($group['timeliness'][$i] ?? '0'); ?></td>

            <!-- Average column -->
            <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>"><?php echo htmlspecialchars($group['average'][$i] ?? '0'); ?></td>
        </tr>
        <?php
    }
}
?>  

                <tr>
                    <td colspan="6" style="text-align: right;"><span>Average Sub-total</span></td>
                    <td>
                        <?php 
                            $total_average = 0; 
                            $task_count = 0;

                            // Loop through core tasks to sum the average values
                            foreach ($core_tasks as $task) {
                                if (isset($task['average'])) {
                                    $total_average += $task['average'];
                                    $task_count++; // Count each task with an average
                                }
                            }

                            // Loop through assigned core tasks to sum the average values
                            foreach ($core_assigned_tasks as $assigned_task) {
                                if (isset($assigned_task['average'])) {
                                    $total_average += $assigned_task['average'];
                                    $task_count++; // Count each assigned task with an average
                                }
                            }

                            // Calculate and display the average sub-total
                            if ($task_count > 0) {
                                $average_subtotal = $total_average / $task_count;
                                echo htmlspecialchars(round($average_subtotal, 2));
                            } else {
                                echo "N/A";
                            }
                        ?>
                    </td>
                    <td></td>
                </tr>
                <tr>
                    <td colspan="6" style="text-align: right;"><span>Weighted Average (Average Sub-total * Core %)</span></td>
                    <td>
                        <?php 
                            // Ensure that $core_percentage is treated as a percentage and that $task_count is greater than 0
                            if (isset($core_percentage) && $task_count > 0) {
                                // Convert the core percentage to a decimal (e.g., 40 becomes 0.40)
                                $core_percentage_decimal = $core_percentage / 100;

                                // Calculate the weighted average by multiplying the average subtotal by the core percentage
                                $weighted_average_core = $average_subtotal * $core_percentage_decimal;

                                // Display the weighted average, rounded to 2 decimal places
                                echo htmlspecialchars(round($weighted_average_core, 2));
                            } else {
                                echo "N/A";
                            }
                        ?>
                    </td>
                    <td></td>
                </tr>
                <!-- Support Tasks -->
                <tr>
                    <td class="section-title" colspan="8" style="padding: 0;">Support Priority (<?php echo htmlspecialchars($support_percentage); ?>%)</td>
                </tr>
                <?php
// Group tasks by task_name and sibling_code
$grouped_tasks = [];
foreach ($support_tasks as $task) {
    $key = $task['task_name'] . '|' . $task['sibling_code']; // Create a unique key for each task
    if (!isset($grouped_tasks[$key])) {
        $grouped_tasks[$key] = [
            'task_name' => $task['task_name'],
            'sibling_code' => $task['sibling_code'],
            'description' => [],
            'documents_required' => [],
            'documents_uploaded' => [],
            'quality' => [],
            'efficiency' => [],
            'timeliness' => [],
            'average' => [],
        ];
    }
    // Add description and document values for each task in the group
    $grouped_tasks[$key]['description'][] = $task['description'];
    $grouped_tasks[$key]['documents_required'][] = $task['documents_required'];
    $grouped_tasks[$key]['documents_uploaded'][] = $task['documents_uploaded'];
    $grouped_tasks[$key]['quality'][] = $task['quality'];
    $grouped_tasks[$key]['efficiency'][] = $task['efficiency'];
    $grouped_tasks[$key]['timeliness'][] = $task['timeliness'];
    $grouped_tasks[$key]['average'][] = $task['average'];
}

// Now render the grouped tasks

foreach ($grouped_tasks as $group) {
    $rowspan = count($group['description']); // Count of tasks in this group

    // Loop through the tasks in this group to display each row
    for ($i = 0; $i < $rowspan; $i++) {
        // Only hide the top border for rows after the first one
        $topBorderStyle = ($i === 0) ? '' : 'border-top: none;';

        // Only hide the bottom border for rows before the last one
        $bottomBorderStyle = ($i === $rowspan - 1) ? '' : 'border-bottom: none;';

        // Render the task row
        ?>
        <tr>
            <?php if ($i === 0): // For the first row, merge the task name cell ?>
                <td rowspan="<?php echo $rowspan; ?>" style="width: 33%; vertical-align: top; text-align: left; padding-left: 10px; word-wrap: break-word; white-space: normal;">
                    <?php echo nl2br(htmlspecialchars($group['task_name'])); ?>
                </td>
            <?php endif; ?>

            <td style="width: 33%; vertical-align: top; text-align: left; padding-left: 10px; word-wrap: break-word; white-space: normal; <?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                <?php echo nl2br(htmlspecialchars($group['description'][$i])); ?>
            </td>
            <td style="width: 33%; vertical-align: top; text-align: left; padding-left: 10px; word-wrap: break-word; white-space: normal; <?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                <?php 
                    // Calculate and display the percentage for this task
                    $documents_req = $group['documents_required'][$i];
                    $documents_uploaded = $group['documents_uploaded'][$i];
                    $percentage = $documents_req > 0 ? ($documents_uploaded / $documents_req) * 100 : 0;
                    echo htmlspecialchars(round($percentage, 2 )) . '% of the target for ' .  nl2br(htmlspecialchars($group['description'][$i])) . ' has been accomplished.';
                ?>
            </td>
            <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>"><?php echo htmlspecialchars($group['quality'][$i]); ?></td>
            <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>"><?php echo htmlspecialchars($group['efficiency'][$i]); ?></td>
            <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>"><?php echo htmlspecialchars($group['timeliness'][$i]); ?></td>
            <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>"><?php echo htmlspecialchars($group['average'][$i]); ?></td>
            <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>"></td> <!-- Empty cell for the last column -->
        </tr>
        <?php
    }
}
?>

<?php
// Group the tasks by task name and sibling code
$grouped_support_tasks = [];
foreach ($support_assigned_tasks as $task) {
    $key = $task['task_name'] . '|' . $task['sibling_code']; // Unique key for grouping tasks
    if (!isset($grouped_support_tasks[$key])) {
        $grouped_support_tasks[$key] = [
            'task_name' => $task['task_name'],
            'sibling_code' => $task['sibling_code'],
            'description' => [],
            'target' => [],
            'num_file' => [],
            'quality' => [],
            'efficiency' => [],
            'timeliness' => [],
            'average' => [],
        ];
    }
    // Add task values to the group
    $grouped_support_tasks[$key]['description'][] = $task['task_description'];
    $grouped_support_tasks[$key]['target'][] = $task['target'];
    $grouped_support_tasks[$key]['num_file'][] = $task['num_file'];
    $grouped_support_tasks[$key]['quality'][] = $task['quality'];
    $grouped_support_tasks[$key]['efficiency'][] = $task['efficiency'];
    $grouped_support_tasks[$key]['timeliness'][] = $task['timeliness'];
    $grouped_support_tasks[$key]['average'][] = $task['average'];
}

// Loop through the grouped tasks
foreach ($grouped_support_tasks as $group) {
    $rowspan = count($group['description']); // Number of rows for each grouped task

    // Loop through the tasks within this group
    for ($i = 0; $i < $rowspan; $i++) {
        // Set top and bottom border styles for each row
        $topBorderStyle = ($i === 0) ? '' : 'border-top: none;';
        $bottomBorderStyle = ($i === $rowspan - 1) ? '' : 'border-bottom: none;';

        ?>
        <tr>
            <?php if ($i === 0): // Merge the task name cell only for the first row ?>
                <td rowspan="<?php echo $rowspan; ?>" style="width: 33%; vertical-align: top; text-align: left; padding-left: 10px; word-wrap: break-word; white-space: normal;">
                    <?php echo nl2br(htmlspecialchars($group['task_name'])); ?>
                </td>
            <?php endif; ?>

            <!-- Task description column -->
            <td style="width: 33%; vertical-align: top; text-align: left; padding-left: 10px; word-wrap: break-word; white-space: normal; <?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                <?php echo nl2br(htmlspecialchars($group['description'][$i])); ?>
            </td>

            <!-- Task percentage completion column -->
            <td style="width: 33%; vertical-align: top; text-align: left; padding-left: 10px; word-wrap: break-word; white-space: normal; <?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                <?php 
                    $target = $group['target'][$i];
                    $num_file = $group['num_file'][$i];
                    $percentage = $target > 0 ? ($num_file / $target) * 100 : 0;
                    echo htmlspecialchars(round($percentage, 2)) . '% of the target for ' . 
                    nl2br(htmlspecialchars($group['description'][$i])) . ' has been accomplished.';
                ?>
            </td>

            <!-- Quality column -->
            <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>"><?php echo htmlspecialchars($group['quality'][$i] ?? '0'); ?></td>

            <!-- Efficiency column -->
            <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>"><?php echo htmlspecialchars($group['efficiency'][$i] ?? '0'); ?></td>

            <!-- Timeliness column -->
            <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>"><?php echo htmlspecialchars($group['timeliness'][$i] ?? '0'); ?></td>

            <!-- Average column -->
            <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>"><?php echo htmlspecialchars($group['average'][$i] ?? '0'); ?></td>
        </tr>
        <?php
    }
}
?>

                <tr>
                    <td colspan="6" style="text-align: right;"><span>Average Sub-total</span></td>
                    <td>
                        <?php 
                            $total_average = 0; 
                            $task_count = 0;

                            // Loop through support tasks to sum the average values
                            foreach ($support_tasks as $task) {
                                if (isset($task['average'])) {
                                    $total_average += $task['average'];
                                    $task_count++;
                                }
                            }

                            // Loop through assigned support tasks to sum the average values
                            foreach ($support_assigned_tasks as $assigned_task) {
                                if (isset($assigned_task['average'])) {
                                    $total_average += $assigned_task['average'];
                                    $task_count++;
                                }
                            }

                            // Calculate and display the average sub-total
                            if ($task_count > 0) {
                                $average_subtotal = $total_average / $task_count;
                                echo htmlspecialchars(round($average_subtotal, 2));
                            } else {
                                echo "N/A";
                            }
                        ?>
                    </td>
                    <td></td>
                </tr>
                <!-- Weighted Average for support tasks -->
                <tr>
                    <td colspan="6" style="text-align: right;"><span>Weighted Average (Average Sub-total * Support %)</span></td>
                    <td>
                        <?php 
                            if ($task_count > 0) {
                                $support_percentage_decimal = $support_percentage / 100;
                                $weighted_average_support = $average_subtotal * $support_percentage_decimal;
                                echo htmlspecialchars(round($weighted_average_support, 2));
                            } else {
                                echo "N/A";
                            }
                        ?>
                    </td>
                    <td></td>
                </tr>
                <tr>
                    <td colspan="6" style="text-align: right;"><span>Final Average Rating (Strategic % + Core % + Support %)</span></td>
                    <td>
                        <?php
                            // Ensure the variables exist and assign a default value of 0 if they are not set
                            $weighted_average_strategic = isset($weighted_average_strategic) ? $weighted_average_strategic : 0;
                            $weighted_average_core = isset($weighted_average_core) ? $weighted_average_core : 0;
                            $weighted_average_support = isset($weighted_average_support) ? $weighted_average_support : 0;

                            // Function to calculate the final average rating
                            function calculateFinalAverageRating($weighted_average_core, $weighted_average_support, $include_strategic = true, $weighted_average_strategic = 0) {
                                if ($include_strategic) {
                                    return ($weighted_average_strategic + $weighted_average_core + $weighted_average_support) / 3; // Include strategic
                                } else {
                                    return ($weighted_average_core + $weighted_average_support) / 2; // Exclude strategic
                                }
                            }

                            // Determine whether to include strategic in the calculation
                            $include_strategic = !($role === 'IPCR' && $designation === 'None' && 
                                (preg_match('/^instructor-[1-3]$/', $user_position) || 
                                preg_match('/^assistant-professor-[1-4]$/', $user_position)));

                            // Calculate the final average rating based on weighted averages
                            $final_average_rating = calculateFinalAverageRating(
                                $weighted_average_core, 
                                $weighted_average_support, 
                                !$include_strategic, // Pass the negation of include_strategic
                                $weighted_average_strategic // Pass the strategic average only if needed
                            );

                            // Calculate the final average by summing the weighted averages
                            $final_average = $weighted_average_strategic + $weighted_average_core + $weighted_average_support;

                            // Display the final average, rounded to 2 decimal places
                            echo number_format($final_average, 2);
                        ?>
                    </td>
                    <td></td>
                </tr>
                <tr>
                    <td colspan="6" style="text-align: right;"><span>Final Average Rating (O, VS, S, US, OR P)</span></td>
                    <td>
                        <?php
                            // Determine the final rating based on the final average
                            $finalRating = '';
                            if ($final_average >= 4.20) {
                                $finalRating = 'O'; // Outstanding
                            } elseif ($final_average >= 3.40) {
                                $finalRating = 'VS'; // Very Satisfactory
                            } elseif ($final_average >= 2.60) {
                                $finalRating = 'S'; // Satisfactory
                            } elseif ($final_average >= 1.80) {
                                $finalRating = 'U'; // Unsatisfactory
                            } else {
                                $finalRating = 'P'; // Poor
                            }

                            // Display the final rating
                            echo htmlspecialchars($finalRating);
                        ?>

                    </td>
                    <td></td>
                </tr>
                <td colspan="8" style="text-align: left; vertical-align: top; padding: 0; height: 100px;">
                    <p style="margin: 0 0 0 20px; font-size: 12px;"><span>Comments and Recommendations for Development Purposes :</span></p>
                    <p style="margin: 0 0 0 20px; font-size: 12px;">(Includes behavioral competencies)</p>
                    <p style="margin: 0 0 0 20px; font-size: 10px;"><?php echo htmlspecialchars($performance_message ?? ''); ?></p>
                </td>
            </tbody>
        </table>
    
        <table class="review-table">
            <tr>
                <td colspan="2" style="text-align: center; vertical-align: top; padding: 0; height:10px;">Discussed with</td>
                <td colspan="4" style="text-align: center; vertical-align: top; padding: 0; height: 10px;">Assessed by</td>
                <td colspan="2" style="text-align: center; vertical-align: top; padding: 0; height: 10px;">Final Rating by</td>
            </tr>
            <tr>
            <td class="for-signature" style="padding: 0; height: 140px; width: 190px; overflow: hidden; border-bottom: none;">
                    <?php if ($signature_data): ?>
                        <img src="data:image/png;base64,<?php echo base64_encode($signature_data); ?>" alt="Signature" style="width: 90px; height: 70px; border:none; background-color: transparent;">
                    <?php else: ?>
                        <p style="text-align: center;"></p>
                    <?php endif; ?>
                </td>
                <td rowspan="3" style="text-align: center; vertical-align: top;">Date</td>
                <td class="for-signature" style="padding: 0; height: 140px; width: 190px; overflow: hidden; border-bottom: none; vertical-align: top; white-space: normal;">
                <p style="font-size: 12px; text-align: left; margin: 2px 0; padding: 0; letter-spacing: -0.5px; overflow-wrap: break-word;">
                    I certify that I discussed my assessment of the performance with the employee
                </p>
                    <?php if (isset($signature_data_office_head) && $signature_data_office_head): ?>
                        <img src="data:image/png;base64,<?php echo base64_encode($signature_data_office_head ); ?>"  alt="Signature" style="width: 90px; height: 70px; border:none; background-color: transparent;">
                    <?php else: ?>
                        <p style="text-align: center;"></p>
                    <?php endif; ?>
                </td>
                <td rowspan="3" style="text-align: center; vertical-align: top;">Date</td>
                <td class="for-signature" style="padding: 0; height: 140px; width: 190px; overflow: hidden; border-bottom: none; vertical-align: top;">
                    <?php 
                    if ($semester['vpapproval'] == 1 && $signature_data_vpaaqa): ?>
                        <img src="data:image/png;base64,<?php echo base64_encode($signature_data_vpaaqa); ?>" alt="Signature" style="width: 90px; height: 70px; border:none; background-color: transparent;">
                    <?php elseif ($semester['vpapproval'] == null): ?>
                        <p class="no-signature" style="text-align: center; margin: 0; padding: 0;"></p>
                    <?php endif; ?>
                </td>
                <td rowspan="3" style="text-align: center; vertical-align: top;">Date</td>
                <td class="for-signature" style="padding: 0; height: 140px; width: 190px; overflow: hidden; border-bottom: none;">
                    <?php if ($presidentApproval === 1 && isset($signature_data_president) && $signature_data_president): ?>
                        <img src="data:image/png;base64,<?php echo base64_encode($signature_data_president); ?>"  alt="Signature" style="width: 90px; height: 70px; border:none; background-color: transparent;">
                    <?php elseif ($presidentApproval === null): ?>
                        <p style="text-align: center;"></p>
                    <?php endif; ?>
                </td>
                <td rowspan="3" style="text-align: center; vertical-align: top;">Date</td>
            </tr>
            <tr>
            <td style="border-top: none; border-left: 1px solid black; border-right: 1px solid black; padding: 0;">
                <span><?php echo strtoupper(htmlspecialchars($_SESSION['firstname']) . ' ' . htmlspecialchars($_SESSION['middlename']) . ' ' . htmlspecialchars($_SESSION['lastname'])); ?></span>
            </td>
            <td style="border-top: none; border-left: 1px solid black; border-right: 1px solid black; padding: 0;">
                <span><?php echo htmlspecialchars($collegeDean); ?></span>
            </td>
            <td style="border-top: none; border-left: 1px solid black; border-right: 1px solid black; padding: 0;">
                <span><?php echo htmlspecialchars($vpaaqa); ?></span>
            </td>
            <td style="border-top: none; border-left: 1px solid black; border-right: 1px solid black; padding: 0;">
                <span><?php echo htmlspecialchars($collegePresident); ?></span>
            </td>
            </tr>
            <tr>
                <td style="border-top: none; border-left: 1px solid black; border-right: 1px solid black; padding: 0;">Ratee</td>
                <td style="border-top: none; border-left: 1px solid black; border-right: 1px solid black; padding: 0;">Immediate Supervisor</td>
                <td style="border-top: none; border-left: 1px solid black; border-right: 1px solid black; padding: 0;">VP for Academic Affairs and<br> Quality Assurance</td>
                <td style="border-top: none; border-left: 1px solid black; border-right: 1px solid black; padding: 0;">College President</td>
            </tr>
        </table>
        <p style="font-style: italic; font-size: 8px;">Legend 1 - Quality 2 - Efficiency 3 - Timeliness 4 - Average</p>
    </div>
</body>
    </html>
    
    <script>
    function printDocument() {
        window.print();
    }
</script>
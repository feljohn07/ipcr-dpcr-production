<?php
session_start();

// fetching the signature
include '../../dbconnections/config.php'; // Ensure this path is correct and the file sets up $conn

// Fetch parameters from the GET request
$semester_id = isset($_GET['id_of_semester']) ? htmlspecialchars($_GET['id_of_semester']) : null;
$idnumber = isset($_SESSION['idnumber']) ? htmlspecialchars($_SESSION['idnumber']) : null; // Use session for idnumber
$group_task_id = isset($_GET['group_task_id']) ? htmlspecialchars($_GET['group_task_id']) : null;

function getUserFullName($conn)
{
    $idnumber = $_SESSION['idnumber'] ?? null;
    if ($idnumber) {
        $sql = "SELECT firstname, middlename, lastname, suffix, college FROM usersinfo WHERE idnumber = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $idnumber);
            $stmt->execute();
            $stmt->bind_result($firstname, $middlename, $lastname, $suffix, $college);
            if ($stmt->fetch()) {
                // Set session variables for full name and other details
                $_SESSION['fullname'] = mb_strtoupper("$firstname $middlename $lastname");
                $_SESSION['fullnamewithsuffix'] = mb_strtoupper("$firstname $middlename $lastname") . ($suffix ? ", " . ucfirst($suffix) : '');
                $_SESSION['collegeofuser'] = $college;

                return [
                    'fullname' => $_SESSION['fullname'],
                    'fullnamewithsuffix' => $_SESSION['fullnamewithsuffix'],
                    'collegeofuser' => $_SESSION['collegeofuser']
                ];
            }
        }
    }
    return null;
}
// Call the function to populate session data
getUserFullName($conn);


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
$sql = "SELECT firstname, middlename, lastname, suffix FROM usersinfo WHERE role = 'College President' LIMIT 1";
$result = $conn->query($sql);
if ($result === false) {
    die("Error executing query: " . $conn->error);
}
$collegePresident = ''; // Initialize variable
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();

    // Build the name
    $collegePresident = strtoupper($row["firstname"]) . " " . strtoupper($row["middlename"]) . " " . strtoupper($row["lastname"]);

    // Check if suffix is not null or empty
    if (!empty($row["suffix"])) {
        $collegePresident .= ", " . $row["suffix"]; // Keep the suffix in its original case
    }

    // Apply htmlspecialchars to the final result
    $collegePresident = htmlspecialchars($collegePresident, ENT_QUOTES);
}

// Fetch VPAA
$sql = "SELECT firstname, middlename, lastname, suffix FROM usersinfo WHERE role = 'VPAAQA' LIMIT 1";
$result = $conn->query($sql);
if ($result === false) {
    die("Error executing query: " . $conn->error);
}
$vpaaqa = ''; // Initialize variable
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();

    // Build the name
    $vpaaqa = strtoupper($row["firstname"]) . " " . strtoupper($row["middlename"]) . " " . strtoupper($row["lastname"]);

    // Check if suffix is not null or empty
    if (!empty($row["suffix"])) {
        $vpaaqa .= ", " . $row["suffix"]; // Keep the suffix in its original case
    }

    // Apply htmlspecialchars to the final result
    $vpaaqa = htmlspecialchars($vpaaqa, ENT_QUOTES);
}

// Fetch user signature from the table "signature" database "01_users"
// Fetch user signature from the table "signature" in database "01_users"
$idnumber = isset($_SESSION['idnumber']) ? htmlspecialchars($_SESSION['idnumber']) : null;
$semester_id = isset($_GET['id_of_semester']) ? htmlspecialchars($_GET['id_of_semester']) : null;

// Initialize variables to hold signature data
$signature_data = null;
$final_signature_data = null;
$user_final_signature_created_at = null;

// Prepare the statement only if idnumber is not null
if ($idnumber !== null) {
    // First query: Fetch signature based on idnumber
    $signature_stmt = $conn->prepare("SELECT data FROM signature WHERE idnumber = ?");
    $signature_stmt->bind_param("s", $idnumber);
    $signature_stmt->execute();
    $signature_stmt->bind_result($signature_data);
    $signature_stmt->fetch();
    $signature_stmt->close();
}

// Prepare the statement only if idnumber and semester_id are not null
if ($idnumber !== null && $semester_id !== null) {
    // Second query: Fetch signature and created_at from user_semesters based on idnumber and semester_id
    $signature_stmt = $conn->prepare("
        SELECT s.data, us.created_at 
        FROM signature s 
        JOIN user_semesters us ON s.idnumber = us.idnumber 
        WHERE us.semester_id = ? AND us.idnumber = ?
    ");

    $signature_stmt->bind_param("ss", $semester_id, $idnumber);
    $signature_stmt->execute();
    $signature_stmt->bind_result($final_signature_data, $user_final_signature_created_at);
    $signature_stmt->fetch();
    $signature_stmt->close();
}


// Assuming you have already fetched $idnumber and $semester_id as shown earlier
// Fetch parameters from the GET request
$semester_id = isset($_GET['id_of_semester']) ? htmlspecialchars($_GET['id_of_semester']) : null;
$idnumber = isset($_SESSION['idnumber']) ? htmlspecialchars($_SESSION['idnumber']) : null; // Use session for idnumber

// Fetch user signature from the table "for_ipcr_final_signature" to check if a record exists
$final_signature_stmt = $conn->prepare("SELECT idnumber, signature_created_at FROM for_ipcr_final_signature WHERE idnumber = ? AND semester_id = ?");
if (!$final_signature_stmt) {
    // Handle error
    die("Error preparing statement: " . $conn->error);
}

$final_signature_stmt->bind_param("ss", $idnumber, $semester_id);
$final_signature_stmt->execute();
$final_signature_stmt->store_result(); // Store the result for checking if any rows exist

// Check if a record exists
if ($final_signature_stmt->num_rows > 0) {
    // Record exists, now fetch the user's signature created date from the "for_ipcr_final_signature" table
    $final_signature_stmt->bind_result($idnumber, $deans_final_signature_date); // Bind idnumber and signature_created_at
    $final_signature_stmt->fetch();

    // Now fetch the user's signature from the "signature" table
    $signature_stmt = $conn->prepare("SELECT data FROM signature WHERE idnumber = ?");
    if (!$signature_stmt) {
        // Handle error
        die("Error preparing statement: " . $conn->error);
    }

    $signature_stmt->bind_param("s", $idnumber); // Use $idnumber instead of $_SESSION['idnumber']
    $signature_stmt->execute();
    $signature_stmt->bind_result($deans_final_sign_to_ipcr); // Change the variable name to $deans_final_sign_to_ipcr
    $signature_stmt->fetch();
    $signature_stmt->close();
} else {
    // Handle the case where no record exists in for_ipcr_final_signature
    $deans_final_sign_to_ipcr = null; // Or set to a default value/message
    $deans_final_signature_date = null; // Also set the date to null if no record exists
}

// Fetch user signature from the table "for_ipcr_final_signature" to check if a record exists
$final_signature_stmt = $conn->prepare("SELECT idnumber, created_at FROM to_ipcr_first_signature WHERE idnumber = ? AND semester_id = ?");
if (!$final_signature_stmt) {
    // Handle error
    die("Error preparing statement: " . $conn->error);
}

$final_signature_stmt->bind_param("ss", $idnumber, $semester_id);
$final_signature_stmt->execute();
$final_signature_stmt->store_result(); // Store the result for checking if any rows exist

// Check if a record exists
if ($final_signature_stmt->num_rows > 0) {
    // Record exists, now fetch the user's signature created date from the "for_ipcr_final_signature" table
    $final_signature_stmt->bind_result($idnumber, $deans_first_signature_date); // Bind idnumber and signature_created_at
    $final_signature_stmt->fetch();

    // Now fetch the user's signature from the "signature" table
    $signature_stmt = $conn->prepare("SELECT data FROM signature WHERE idnumber = ?");
    if (!$signature_stmt) {
        // Handle error
        die("Error preparing statement: " . $conn->error);
    }

    $signature_stmt->bind_param("s", $idnumber); // Use $idnumber instead of $_SESSION['idnumber']
    $signature_stmt->execute();
    $signature_stmt->bind_result($deans_first_sign_to_ipcr); // Change the variable name to $deans_final_sign_to_ipcr
    $signature_stmt->fetch();
    $signature_stmt->close();
} else {
    // Handle the case where no record exists in for_ipcr_final_signature
    $deans_final_sign_to_ipcr = null; // Or set to a default value/message
    $deans_final_signature_date = null; // Also set the date to null if no record exists
}

// Close the statement for final_signature
$final_signature_stmt->close();

// Fetch VPAA signature
// Get semester_id and idnumber from request and session
$semester_id = isset($_GET['id_of_semester']) ? htmlspecialchars($_GET['id_of_semester']) : null;
$idnumber = isset($_SESSION['idnumber']) ? htmlspecialchars($_SESSION['idnumber']) : null;

// Check if idnumber and semester_id are not null
if ($idnumber && $semester_id) {
    // Prepare a statement to check if there's a row in vpaa_to_ipcr_finalsign
    $check_stmt = $conn->prepare("SELECT idnumber, signature_created_at FROM vpaa_to_ipcr_finalsign WHERE idnumber = ? AND semester_id = ?");
    $check_stmt->bind_param("ss", $idnumber, $semester_id);
    $check_stmt->execute();
    $check_stmt->store_result();

    // Check if any rows were returned
    if ($check_stmt->num_rows > 0) {
        // Fetch VPAA signature
        $signature_stmt = $conn->prepare("SELECT data FROM signature WHERE role = ?");
        $role = 'VPAAQA'; // Define the role you are looking for
        $signature_stmt->bind_param("s", $role);
        $signature_stmt->execute();
        $signature_stmt->bind_result($signature_data_vpaaqa);
        $signature_stmt->fetch();
        $signature_stmt->close();

        // Fetch the date created
        $check_stmt->bind_result($fetched_idnumber, $vpaa_final_sign_created);
        $check_stmt->fetch(); // Fetch the result into variables
    }

    $check_stmt->close();
} else {
    // Handle the case where idnumber or semester_id is not set
    echo "Invalid input.";
}

// Fetch college president signature from the table "signature" database "01_users"
// Get semester_id and idnumber from request and session
$semester_id = isset($_GET['id_of_semester']) ? htmlspecialchars($_GET['id_of_semester']) : null;
$idnumber = isset($_SESSION['idnumber']) ? htmlspecialchars($_SESSION['idnumber']) : null;

// Check if idnumber and semester_id are not null
if ($idnumber && $semester_id) {
    // Prepare a statement to check if there's a row in vpaa_to_ipcr_finalsign
    $check_stmt = $conn->prepare("SELECT idnumber, signature_created_at FROM president_final_signature_to_ipcr WHERE idnumber = ? AND semester_id = ?");
    $check_stmt->bind_param("ss", $idnumber, $semester_id);
    $check_stmt->execute();
    $check_stmt->store_result();

    // Check if any rows were returned
    if ($check_stmt->num_rows > 0) {
        // Fetch College President signature
        $signature_stmt_president = $conn->prepare("SELECT data FROM signature WHERE role = ?");
        $role_president = 'College president'; // Define the role you are looking for
        $signature_stmt_president->bind_param("s", $role_president);
        $signature_stmt_president->execute();
        $signature_stmt_president->bind_result($signature_final_data_president);
        $signature_stmt_president->fetch();
        $signature_stmt_president->close();

        // Fetch the date created
        $check_stmt->bind_result($fetched_idnumber, $president_final_sign_created);
        $check_stmt->fetch(); // Fetch the result into variables
    }

    // Prepare a statement to check if there's a row in vpaa_to_ipcr_finalsign
    $check_stmt = $conn->prepare("SELECT idnumber, signature_created_at FROM president_first_signature_to_ipcr WHERE idnumber = ? AND semester_id = ?");
    $check_stmt->bind_param("ss", $idnumber, $semester_id);
    $check_stmt->execute();
    $check_stmt->store_result();

    // Check if any rows were returned
    if ($check_stmt->num_rows > 0) {
        // Fetch College President signature
        $signature_stmt_president = $conn->prepare("SELECT data FROM signature WHERE role = ?");
        $role_president = 'College president'; // Define the role you are looking for
        $signature_stmt_president->bind_param("s", $role_president);
        $signature_stmt_president->execute();
        $signature_stmt_president->bind_result($signature_first_president);
        $signature_stmt_president->fetch();
        $signature_stmt_president->close();

        // Fetch the date created
        $check_stmt->bind_result($fetched_idnumber, $president_first_sign_created);
        $check_stmt->fetch(); // Fetch the result into variables
    }

    $check_stmt->close();
} else {
    // Handle the case where idnumber or semester_id is not set
    echo "Invalid input.";
}



// Fetch the user's role, designation, and position from the session
$role = $_SESSION['role']; // User's role
$designation = $_SESSION['designation']; // User's designation
$user_position = isset($_SESSION['position']) ? $_SESSION['position'] : null; // Check if position is set

// Initialize the values
$support_percentage = 'Not Defined';
$core_percentage = 'Not Defined';
$strategic_percentage = 'Not Defined';

// Check if position and designation are not empty
if (!empty($user_position) && !empty($designation)) {
    // Check conditions for IPCR with None designation or Instructor with SO and instructor or assistant professor positions
    if (
        $role === 'IPCR' && 
        ($designation === 'None' || $designation === 'Instructor with SO') &&
        (preg_match('/^instructor-[1-3]$/', $user_position) ||
         preg_match('/^assistant-professor-[1-4]$/', $user_position))
    ) {
        // SQL query to fetch values for "Instructor to Assistant Professors"
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
    if (
        $role === 'IPCR' &&
        (preg_match('/^associate-professor-[1-4]$/', $user_position) ||
            preg_match('/^professor-[1-5]$/', $user_position) ||
            $user_position === 'university-professor-1')
    ) {

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
    if (
        $role === 'IPCR' &&
        $designation !== 'None' &&
        !preg_match('/^associate-professor-[1-4]$/', $user_position) &&
        !preg_match('/^professor-[1-5]$/', $user_position) &&
        !($designation === 'Instructor with SO' && 
          (preg_match('/^instructor-[1-3]$/', $user_position) || 
           preg_match('/^assistant-professor-[1-4]$/', $user_position)))
    ) {

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
    $sql = "SELECT firstname, middlename, lastname, suffix FROM usersinfo WHERE role = 'Office Head' AND college = ? LIMIT 1";
    $collegeDean_stmt = $conn->prepare($sql);
    $current_user_college = $_SESSION['college']; // Assuming college is stored in session
    $collegeDean_stmt->bind_param("s", $current_user_college);
    $collegeDean_stmt->execute();
    $collegeDean_result = $collegeDean_stmt->get_result();

    // Fetch performance message
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

        // Build the College Dean's name with the suffix
        $fullName = strtoupper($row["firstname"] . " " . $row["middlename"] . " " . $row["lastname"]);

        // Append suffix if it exists and is not empty
        if (!empty($row["suffix"])) {
            $fullName .= ", " . $row["suffix"];
        }

        // Sanitize the full name
        $collegeDean = htmlspecialchars($fullName, ENT_QUOTES);

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

    // Check if the user's ID number and semester ID exist in the for_ipcr_final_signature table
    $finalSignatureCheckQuery = "SELECT idnumber, signature_created_at FROM for_ipcr_final_signature WHERE idnumber = ? AND semester_id = ?";
    $finalSignatureCheckStmt = $conn->prepare($finalSignatureCheckQuery);
    $finalSignatureCheckStmt->bind_param("ss", $userId, $id_of_semester);
    $finalSignatureCheckStmt->execute();
    $finalSignatureCheckResult = $finalSignatureCheckStmt->get_result();

    // Check if the record exists
    $finalSignatureExists = $finalSignatureCheckResult->num_rows > 0;
    $finalSignatureCheckStmt->close();

    // Fetch College Dean's signature only if the record exists
    if ($finalSignatureExists) {
        // Fetch the signature data for the College Dean
        $signature_stmt_final = $conn->prepare("SELECT data FROM signature WHERE college = ? AND role = ?");
        $role = 'Office Head'; // Assuming 'Office Head' is the role for the College Dean
        $signature_stmt_final->bind_param("ss", $current_user_college, $role);
        $signature_stmt_final->execute();
        $signature_stmt_final->bind_result($signature_data_office_head_final);
        $signature_stmt_final->fetch();
        $signature_stmt_final->close();

        // Fetch the created_at timestamp from the for_ipcr_final_signature result
        $finalSignatureCheckResult->data_seek(0); // Reset the result pointer to the first row
        $row = $finalSignatureCheckResult->fetch_assoc(); // Fetch the row
        $dean_final_sign_created_at = $row['signature_created_at']; // Get the timestamp
    } else {
        // If the record does not exist, set the signature data to null
        $signature_data_office_head_final = null;
        $dean_final_sign_created_at = null; // Also set created_at to null
    }

    // Handle case where no College Dean was found
    if ($signature_data_office_head_final === null) {
        $collegeDeanFinal = "No College Dean found for final signature.";
    } else {

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


// Assuming you have the user details stored in session variables
$userId = $_SESSION['idnumber'];
$userfirstname = $_SESSION['firstname'];
$userlastname = $_SESSION['lastname'];
$usercollege = $_SESSION['college'];

// Prepare the SQL statement to insert or update performance ratings
$insert_sql = "INSERT INTO ipcr_performance_rating 
                (idnumber, semester_id, weighted_average_strategic, weighted_average_core, weighted_average_support, 
                 final_average, final_rating, 
                 average_subtotal_strategic, average_subtotal_core, average_subtotal_support, 
                 firstname, lastname, college) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE 
                weighted_average_strategic = VALUES(weighted_average_strategic), 
                weighted_average_core = VALUES(weighted_average_core), 
                weighted_average_support = VALUES(weighted_average_support), 
                final_average = VALUES(final_average), 
                final_rating = VALUES(final_rating), 
                average_subtotal_strategic = VALUES(average_subtotal_strategic),
                average_subtotal_core = VALUES(average_subtotal_core),
                average_subtotal_support = VALUES(average_subtotal_support),
                firstname = VALUES(firstname), 
                lastname = VALUES(lastname), 
                college = VALUES(college)";

$insert_stmt = $conn->prepare($insert_sql);
$insert_stmt->bind_param(
    "sssssssssssss",
    $userId,
    $id_of_semester,
    $weighted_average_strategic,
    $weighted_average_core,
    $weighted_average_support,
    $final_average,
    $finalRating, // Binding $final_average to final_rating
    $average_subtotal_strategic,
    $average_subtotal_core,
    $average_subtotal_support,
    $userfirstname,
    $userlastname,
    $usercollege
);

// Execute the prepared statement
if ($insert_stmt->execute()) {

} else {
    echo "Error: " . $conn->error;
}


// Fetch semester details
$semester_stmt = $conn->prepare("SELECT semester_name,start_date, end_date, vpapproval, presidentapproval, final_approval_vpaa, final_approval_press, vp_first_created_at, vp_final_created_at, press_first_created_at, press_final_created_at, userapproval, users_final_approval FROM semester_tasks WHERE semester_id = ?");
$semester_stmt->bind_param("i", $semester_id);
$semester_stmt->execute();
$semester_result = $semester_stmt->get_result();
$semester = $semester_result->fetch_assoc();
$semester_stmt->close();

// Fetch the user's ID number and semester ID
$userId = $_SESSION['idnumber'];
$id_of_semester = htmlspecialchars($_GET['id_of_semester']); // Ensure you have the semester ID

// Query to fetch the signature_created_at value
$signatureQuery = "SELECT signature_created_at FROM to_ipcr_signature WHERE idnumber = ? AND semester_id = ?";
$signatureStmt = $conn->prepare($signatureQuery);
$signatureStmt->bind_param("ss", $userId, $id_of_semester);
$signatureStmt->execute();
$signatureResult = $signatureStmt->get_result();

// Initialize variable
$signatureCreatedAt = '';

// Check if the record exists
if ($signatureResult->num_rows > 0) {
    $row = $signatureResult->fetch_assoc();
    // Format the date as m/d/Y
    $signatureCreatedAt = htmlspecialchars((new DateTime($row['signature_created_at']))->format('m/d/Y'));
}
$signatureStmt->close();


// Fetch the user's ID number and semester ID
$userId = $_SESSION['idnumber'];
$id_of_semester = htmlspecialchars($_GET['id_of_semester']); // Ensure you have the semester ID

// Query to check if the user has a record in user_semesters for the current semester and fetch created_at
$checkSemesterQuery = "SELECT created_at FROM user_semesters WHERE idnumber = ? AND semester_id = ?";
$checkSemesterStmt = $conn->prepare($checkSemesterQuery);
$checkSemesterStmt->bind_param("ss", $userId, $id_of_semester);
$checkSemesterStmt->execute();
$checkSemesterResult = $checkSemesterStmt->get_result();

// Determine if the user has a record for the semester and fetch created_at
if ($checkSemesterResult->num_rows > 0) {
    // Fetch the created_at value
    $semesterRecord = $checkSemesterResult->fetch_assoc();
    $createdAt = $semesterRecord['created_at']; // Get the created_at value
    $hasSemesterRecord = true; // User has a record for the semester
} else {
    $createdAt = null; // No record found
    $hasSemesterRecord = false; // User does not have a record for the semester
}

$checkSemesterStmt->close();
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
                width: 100%;
                /* Set the container to full width */
                margin: 0;
                /* Adjust left and right margins */
                box-sizing: border-box;
                /* Include padding and border in the element's total width and height */
                text-align: center;

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
                margin-bottom: 5px;
                /* Reduce the bottom margin of the paragraph */
            }



            .review-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
            }

            .review-table,
            .review-table th,
            .review-table td {
                border: 1px solid black;
            }

            .review-table th,
            .review-table td {
                padding: 10px;
            }

            .review-table td {
                font-size: 14px;
            }

            .highlighted {
                font-size: 17px;
                /* Removed font-weight: bold; */
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


            .underline {
                border-bottom: 1px solid black;
                /* Adjust color and thickness as needed */
                display: inline-block;
                /* Ensures underline is applied to inline text */
                padding-bottom: 1px;
                /* Adjust padding to align the underline */
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

                html,
                body {
                    margin: 0;
                    /* Reset margins */
                    padding: 0;
                    /* Reset padding */
                    width: auto;
                    /* Ensure full width for printing */
                    overflow: visible;
                    /* Ensure no overflow */
                }

                .container {
                    width: 100%;
                    /* Set the container to full width */
                    margin: 0;
                    /* Adjust left and right margins */
                    box-sizing: border-box;
                    /* Include padding and border in the element's total width and height */
                }

                @page {
                    size: 14in 8in;
                    /* Set page size to 8x13 inches in landscape */
                    margin: 0.2in 0.2in 0.2in 0.2in;
                    /* Top, Right, Bottom, Left margins */
                }

                .print-container {
                    display: none;
                    /* Hide print button */
                }

                .review-table {
                    border-collapse: collapse;
                    width: 100%;
                }

                .review-table th,
                .review-table td {
                    border: 1px solid black;
                    padding: 10px;
                }

                .review-table td {
                    overflow: hidden;
                    /* Prevent overflow */
                    white-space: nowrap;
                    /* Prevent text wrapping */
                    text-overflow: ellipsis;
                    /* Add ellipsis for overflowed text */
                }

                .review-table td.for-signature {
                    border-top: none;
                    /* Ensure no top border */
                    border-bottom: none;
                    /* Ensure no bottom border */
                    padding: 0;
                    /* Optional: Adjust padding for print */
                    height: auto;
                    /* Optional: Adjust height for print */
                }

                button {
                    display: none;
                    /* Hides all button elements during print */
                }

                .no-signature {
                    display: none;
                    /* Hide the no-signature paragraph during printing */
                }

                .background-image,
                .background-image-president {
                    position: absolute;
                    width: 50%;
                    height: 60%;
                    z-index: -1;
                    border: solid;
                    /* You can specify border properties here */
                }

                .background-image-user {
                    top: 40px !important;
                    left: 0;
                    /* Align to the left */
                }

                .background-image {
                    top: 30%;
                    /* Adjusted to move it higher */
                    left: 0;
                    /* Align to the left */
                }

                .background-image-president {
                    top: 30%;
                    /* Adjusted to move it higher */
                    right: 0;
                    /* Align to the right */
                }

                .background-image img,
                .background-image-president img {
                    position: absolute;
                    top: 50%;
                    left: 15%;
                    /* Adjust this value as necessary */
                    width: 40%;
                    height: 10%;
                    object-fit: cover;
                    border: none;
                    background-color: transparent;
                }

                .background-image-president img {
                    position: absolute;
                    top: 0;
                    left: 15%;
                    /* Adjust this value as necessary */
                    width: 40%;
                    height: 30%;
                    object-fit: cover;
                    border: none;
                    background-color: transparent;
                }

                .review-table-container .review-table {
                    page-break-inside: avoid; /* Prevent page breaks inside this table */
                }
                .review-table-container .for-signature {
                    page-break-inside: avoid; /* Prevent page breaks inside signature cells */
                }

                /* Media Queries for Responsive Design */
                @media (max-width: 768px) {

                    .background-image,
                    .background-image-president {
                        width: 100%;
                        /* Full width on smaller screens */
                        height: auto;
                        /* Adjust height as necessary */
                    }

                    .background-image img,
                    .background-image-president img {
                        width: 80%;
                        /* Adjust image width on smaller screens */
                        height: auto;
                        /* Maintain aspect ratio */
                        left: 10%;
                        /* Center the image */
                    }
                }

            }
        </style>
    </head>

    <body>

        <!--
<button onclick="exportToWord()">Export to Word</button>
-->
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
            onmouseover="this.style.backgroundColor='#0056b3';" onmouseout="this.style.backgroundColor='#007bff';"
            onclick="printDocument()">
            Print
        </button>
        <div class="container" style="padding: 0;">
            <h1 style="padding-top: 0;">INDIVIDUAL PERFORMANCE COMMITMENT AND REVIEW (IPCR)</h1>
            <p>
                I, <span
                    style="text-decoration: underline; line-height: 1.2; font-weight: bold;"><?php echo htmlspecialchars($_SESSION['fullname']); ?></span>
                of the
                <span
                    style="text-decoration: underline; line-height: 1.2; font-weight: bold;"><?php echo strtoupper(htmlspecialchars($_SESSION['college'])); ?></span>
                commit to deliver and agree to be rated on the attainment of the following targets
                in accordance with the indicated measures for the period
                <strong
                    style="text-decoration: underline; line-height: 1.2; font-weight: bold;"><?php echo isset($semestername) ? $semestername : 'N/A'; ?></strong>
            </p>
            <div class="signatures">
                <div class="ratee" style="margin-left : 60%;">
                    <p style="text-align:center; ">
                        <span
                            style="text-decoration: underline; line-height: 2; font-weight: bold; text-align : center;">
                            <strong><?php echo htmlspecialchars($_SESSION['fullnamewithsuffix']); ?></strong>
                        </span><br>
                        <span style="display: block; text-align: center;">Ratee</span>
                    </p>
                    <p style="text-align:center; ">Date : <span class="underline"
                            style="margin-left: 5px; line-height: 0.8;"><?php echo date('F j, Y'); ?></span></p>
                </div>
            </div>
            <table class="review-table"
                style="position: relative; width: 100%; height: auto; border-collapse: collapse;">
                <!-- Image container for the background -->
                <div class="background-image-user"
                    style="position: absolute; top: 120px; left: 40%; width: 50%; height: 60%; z-index: -1; border:none;">
                    <?php if (isset($signature_data) && $signature_data): ?>
                        <img src="data:image/png;base64,<?php echo base64_encode($signature_data); ?>" alt="Signature"
                            style="position: absolute; top: 0; left: 60%; width: 40%; height: 30%; object-fit: cover; border: none; background-color: transparent;">
                    <?php else: ?>
                        <p style="text-align: center;"></p>
                    <?php endif; ?>
                </div>

                <!-- Image container for the background -->
                <div class="background-image"
                    style="position: absolute; top: 50; left: 0%; width: 50%; height: 60%; z-index: -1; border: none;">
                    <?php if ($signatureExists && isset($signature_data_office_head) && $signature_data_office_head): ?>
                        <img src="data:image/png;base64,<?php echo base64_encode($signature_data_office_head); ?>"
                            alt="Signature"
                            style="position: absolute; top: 0; left: 15%; width: 40%; height: 30%; object-fit: cover; border: none; background-color: transparent;">
                    <?php else: ?>
                        <p style="text-align: center;"></p> <!-- Optional placeholder -->
                    <?php endif; ?>
                </div>

                <!-- Image container for the background -->
                <div class="background-image-president"
                    style="position: absolute; top: 53; right: 0%; width: 50%; height: 60%; z-index: -1; border: none ;">
                    <?php if (isset($signature_first_president) && $signature_first_president): ?>
                        <img src="data:image/png;base64,<?php echo base64_encode($signature_first_president); ?>"
                            alt="Signature"
                            style="position: absolute; top: 10px; left: 100px; width: 40%; height: 30%; object-fit: cover; border: none; background-color: transparent;">
                    <?php else: ?>
                        <p style="text-align: center;"></p>
                    <?php endif; ?>
                </div>
                <!-- Table content -->
                <tr>
                    <th>Reviewed by:</th>
                    <th style="text-align: center;">Date</th>
                    <th>Approved by:</th>
                    <th>Date</th>
                </tr>
                <tr>
                    <td style="position: relative; padding: 0 0 0 0; height: 75px; border-bottom: none;">
                        <div style="position: absolute; bottom: 2px; width: 100%; text-align: center;">
                            <strong class="highlighted"><?php echo htmlspecialchars($collegeDean); ?></strong>
                        </div>
                    </td>

                    <td rowspan="2" style="padding: 0; margin: 0; ">
                        <?php
                        echo !empty($signatureCreatedAt) ? $signatureCreatedAt : '&nbsp;'; // Use a non-breaking space to maintain width
                        ?>
                    </td>
                    <td style="position: relative; padding: 0 0 0 0; height: 75px; border-bottom: none;">
                        <div style="position: absolute; bottom: 0; width: 100%; text-align: center;">
                            <strong class="highlighted"><?php echo htmlspecialchars($collegePresident); ?></strong>
                        </div>
                    </td>

                    <td rowspan="2" style="padding: 0; margin: 0; ">
                        <?php
                        echo !empty($president_first_sign_created) ? $president_first_sign_created : '&nbsp;'; // Use a non-breaking space to maintain width
                        ?>
                    </td>
                </tr>
                <tr>
                    <th style="font-size: 14px; padding: 0; font-weight: normal;">Immediate Supervisor</th>

                    <th style="font-size: 14px; padding: 0; font-weight: normal;">College President</th>
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
                        // Determine whether to include strategic in the calculation
                        $include_strategic = !($role === 'IPCR' && 
                            ($designation === 'None' || $designation === 'Instructor with SO') && 
                            (preg_match('/^instructor-[1-3]$/', $user_position) || 
                            preg_match('/^assistant-professor-[1-4]$/', $user_position)));

                        // Only display the Strategic Priority section if the condition is not met
                        if ($include_strategic): ?>
                        <tr>
                            <td class="section-title" colspan="8" style="padding: 0;">Strategic Priority
                                (<?php echo htmlspecialchars($strategic_percentage); ?>%)</td>
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
                                        <td rowspan="<?php echo $rowspan; ?>"
                                            style="width: 33%; vertical-align: top; text-align: left; padding-left: 10px; word-wrap: break-word; white-space: normal;">
                                            <?php echo nl2br(htmlspecialchars($group['task_name'])); ?>
                                        </td>
                                    <?php endif; ?>

                                    <td
                                        style="width: 33%; vertical-align: top; text-align: left; padding-left: 10px; word-wrap: break-word; white-space: normal; <?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                                        <?php echo nl2br(htmlspecialchars($group['description'][$i])); ?>
                                    </td>
                                    <td
                                        style="width: 33%; vertical-align: top; text-align: left; padding-left: 10px; word-wrap: break-word; white-space: normal; <?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                                        <?php
                                        // Calculate and display the percentage for this task
                                        $documents_req = $group['documents_required'][$i];
                                        $documents_uploaded = $group['documents_uploaded'][$i];
                                        $percentage = $documents_req > 0 ? ($documents_uploaded / $documents_req) * 100 : 0;
                                        echo !empty($final_signature_data) 
                                        ? htmlspecialchars(round($percentage, 2)) . '% of the target for ' . nl2br(htmlspecialchars($group['description'][$i])) . ' has been accomplished.'
                                        : ''; // Leave the cell empty when no data is available
                                        ?>
                                    </td>
                                    <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                                        <?php echo !empty($final_signature_data) ? htmlspecialchars($group['quality'][$i] ?? '') : ''; ?>
                                    </td>
                                    <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                                        <?php echo !empty($final_signature_data) ? htmlspecialchars($group['efficiency'][$i] ?? '') : ''; ?>
                                    </td>
                                    <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                                        <?php echo !empty($final_signature_data) ? htmlspecialchars($group['timeliness'][$i] ?? '') : ''; ?>
                                    </td>
                                    <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                                        <?php echo !empty($final_signature_data) ? htmlspecialchars($group['quality'][$i] ?? '') : ''; ?>
                                    </td>
                                    <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>"></td>
                                    <!-- Empty cell for the last column -->
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
                                        <td rowspan="<?php echo $rowspan; ?>"
                                            style="width: 33%; vertical-align: top; text-align: left; padding-left: 10px; word-wrap: break-word; white-space: normal;">
                                            <?php echo nl2br(htmlspecialchars($group['task_name'])); ?>
                                        </td>
                                    <?php endif; ?>

                                    <!-- Task description column -->
                                    <td
                                        style="width: 33%; vertical-align: top; text-align: left; padding-left: 10px; word-wrap: break-word; white-space: normal; <?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                                        <?php echo nl2br(htmlspecialchars($group['description'][$i])); ?>
                                    </td>

                                    <!-- Task percentage completion column -->
                                    <td
                                        style="width: 33%; vertical-align: top; text-align: left; padding-left: 10px; word-wrap: break-word; white-space: normal; <?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                                        <?php
                                        $target = $group['target'][$i]; // Replaced 'documents_required' with 'target'
                                        $num_file = $group['num_file'][$i]; // Replaced 'documents_uploaded' with 'num_file'
                                        $percentage = $target > 0 ? ($num_file / $target) * 100 : 0;
                                        echo !empty($final_signature_data) 
                                        ? htmlspecialchars(round($percentage, 2)) . '% of the target for ' . nl2br(htmlspecialchars($group['description'][$i])) . ' has been accomplished.'
                                        : ''; // Leave the cell empty when no data is available
                                        ?>
                                    </td>

                                    <!-- Quality column -->
                                    <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                                        <?php echo !empty($final_signature_data) ? htmlspecialchars($group['quality'][$i] ?? '') : ''; ?>
                                    </td>
                                    <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                                        <?php echo !empty($final_signature_data) ? htmlspecialchars($group['efficiency'][$i] ?? '') : ''; ?>
                                    </td>
                                    <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                                        <?php echo !empty($final_signature_data) ? htmlspecialchars($group['timeliness'][$i] ?? '') : ''; ?>
                                    </td>
                                    <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                                        <?php echo !empty($final_signature_data) ? htmlspecialchars($group['quality'][$i] ?? '') : ''; ?>
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
                                
                                    // Check if $final_signature_data is not empty
                                    if (!empty($final_signature_data)) {
                                        echo htmlspecialchars(number_format($average_subtotal, 2)); // Ensures two decimal places
                                    } else {
                                        echo ''; // Temporary null when there is no final signature data
                                    }
                                } else {
                                    echo ''; // Leave the cell empty when there are no tasks
                                }
                                ?>
                            </td>
                            <td></td>
                        </tr>
                        <tr>
                            <td colspan="6" style="text-align: right;"><span>Weighted Average (Average Sub-total * Strategic
                                    %)</span></td>
                            <td>
                                <?php
                                // Ensure that $strategic_percentage is treated as a percentage and that $task_count is greater than 0
                                if (isset($strategic_percentage) && $task_count > 0) {
                                    // Convert the strategic percentage to a decimal (e.g., 35 becomes 0.35)
                                    $strategic_percentage_decimal = $strategic_percentage / 100;

                                    // Calculate the weighted average by multiplying the average subtotal by the strategic percentage
                                    $weighted_average_strategic = $average_subtotal * $strategic_percentage_decimal;

                                    // Check if $final_signature_data is not empty before displaying
                                    if (!empty($final_signature_data)) {
                                        // Display the weighted average, rounded to 2 decimal places
                                        echo htmlspecialchars(number_format($weighted_average_strategic, 2));
                                    } else {
                                        echo ''; // Temporary null when there is no final signature data
                                    }
                                } else {
                                    echo ''; // Leave the cell empty if there's no strategic percentage or no tasks
                                }
                                ?>
                            </td>
                            <td></td>
                        </tr>
                    <?php endif; ?>
                    <!-- Core Tasks -->
                    <tr>
                        <td class="section-title" colspan="8" style="padding: 0;">Core Priority
                            (<?php echo htmlspecialchars($core_percentage); ?>%)</td>
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
                                    <td rowspan="<?php echo $rowspan; ?>"
                                        style="width: 33%; vertical-align: top; text-align: left; padding-left: 10px; word-wrap: break-word; white-space: normal;">
                                        <?php echo nl2br(htmlspecialchars($group['task_name'])); ?>
                                    </td>
                                <?php endif; ?>

                                <td
                                    style="width: 33%; vertical-align: top; text-align: left; padding-left: 10px; word-wrap: break-word; white-space: normal; <?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                                    <?php echo nl2br(htmlspecialchars($group['description'][$i])); ?>
                                </td>
                                <td
                                        style="width: 33%; vertical-align: top; text-align: left; padding-left: 10px; word-wrap: break-word; white-space: normal; <?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                                        <?php
                                        // Calculate and display the percentage for this task
                                        $documents_req = $group['documents_required'][$i];
                                        $documents_uploaded = $group['documents_uploaded'][$i];
                                        $percentage = $documents_req > 0 ? ($documents_uploaded / $documents_req) * 100 : 0;
                                        echo !empty($final_signature_data) 
                                        ? htmlspecialchars(round($percentage, 2)) . '% of the target for ' . nl2br(htmlspecialchars($group['description'][$i])) . ' has been accomplished.'
                                        : ''; // Leave the cell empty when no data is available
                                        ?>
                                    </td>
                                    <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                                        <?php echo !empty($final_signature_data) ? htmlspecialchars($group['quality'][$i] ?? '') : ''; ?>
                                    </td>
                                    <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                                        <?php echo !empty($final_signature_data) ? htmlspecialchars($group['efficiency'][$i] ?? '') : ''; ?>
                                    </td>
                                    <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                                        <?php echo !empty($final_signature_data) ? htmlspecialchars($group['timeliness'][$i] ?? '') : ''; ?>
                                    </td>
                                    <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                                        <?php echo !empty($final_signature_data) ? htmlspecialchars($group['quality'][$i] ?? '') : ''; ?>
                                    </td>
                                    <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>"></td>
                                    <!-- Empty cell for the last column -->
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
                                    <td rowspan="<?php echo $rowspan; ?>"
                                        style="width: 33%; vertical-align: top; text-align: left; padding-left: 10px; word-wrap: break-word; white-space: normal;">
                                        <?php echo nl2br(htmlspecialchars($group['task_name'])); ?>
                                    </td>
                                <?php endif; ?>

                                <!-- Task description column -->
                                <td
                                    style="width: 33%; vertical-align: top; text-align: left; padding-left: 10px; word-wrap: break-word; white-space: normal; <?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                                    <?php echo nl2br(htmlspecialchars($group['description'][$i])); ?>
                                </td>

                                <!-- Task percentage completion column -->
                                <td
                                        style="width: 33%; vertical-align: top; text-align: left; padding-left: 10px; word-wrap: break-word; white-space: normal; <?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                                        <?php
                                        $target = $group['target'][$i]; // Replaced 'documents_required' with 'target'
                                        $num_file = $group['num_file'][$i]; // Replaced 'documents_uploaded' with 'num_file'
                                        $percentage = $target > 0 ? ($num_file / $target) * 100 : 0;
                                        echo !empty($final_signature_data) 
                                        ? htmlspecialchars(round($percentage, 2)) . '% of the target for ' . nl2br(htmlspecialchars($group['description'][$i])) . ' has been accomplished.'
                                        : ''; // Leave the cell empty when no data is available
                                        ?>
                                    </td>

                                    <!-- Quality column -->
                                    <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                                        <?php echo !empty($final_signature_data) ? htmlspecialchars($group['quality'][$i] ?? '') : ''; ?>
                                    </td>
                                    <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                                        <?php echo !empty($final_signature_data) ? htmlspecialchars($group['efficiency'][$i] ?? '') : ''; ?>
                                    </td>
                                    <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                                        <?php echo !empty($final_signature_data) ? htmlspecialchars($group['timeliness'][$i] ?? '') : ''; ?>
                                    </td>
                                    <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                                        <?php echo !empty($final_signature_data) ? htmlspecialchars($group['quality'][$i] ?? '') : ''; ?>
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

                            if ($task_count > 0) {
                                $average_subtotal = $total_average / $task_count;
                            
                                // Check if $final_signature_data is not empty
                                if (!empty($final_signature_data)) {
                                    echo htmlspecialchars(number_format($average_subtotal, 2)); // Ensures two decimal places
                                } else {
                                    echo ''; // Temporary null when there is no final signature data
                                }
                            } else {
                                echo ''; // Leave the cell empty when there are no tasks
                            }
                            ?>
                        </td>
                        <td></td>
                    </tr>
                    <tr>
                        <td colspan="6" style="text-align: right;"><span>Weighted Average (Average Sub-total * Core
                                %)</span></td>
                        <td>
                            <?php
                            // Ensure that $core_percentage is treated as a percentage and that $task_count is greater than 0
                            if (isset($core_percentage) && $task_count > 0) {
                                // Convert the core percentage to a decimal (e.g., 40 becomes 0.40)
                                $core_percentage_decimal = $core_percentage / 100;

                                // Calculate the weighted average by multiplying the average subtotal by the core percentage
                                $weighted_average_core = $average_subtotal * $core_percentage_decimal;

                                 // Check if $final_signature_data is not empty before displaying
                                 if (!empty($final_signature_data)) {
                                    // Display the weighted average, rounded to 2 decimal places
                                    echo htmlspecialchars(number_format($weighted_average_core, 2));
                                } else {
                                    echo ''; // Temporary null when there is no final signature data
                                }
                            } else {
                                echo ''; // Leave the cell empty if there's no strategic percentage or no tasks
                            }
                            ?>
                        </td>
                        <td></td>
                    </tr>
                    <!-- Support Tasks -->
                    <tr>
                        <td class="section-title" colspan="8" style="padding: 0;">Support Priority
                            (<?php echo htmlspecialchars($support_percentage); ?>%)</td>
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
                                    <td rowspan="<?php echo $rowspan; ?>"
                                        style="width: 33%; vertical-align: top; text-align: left; padding-left: 10px; word-wrap: break-word; white-space: normal;">
                                        <?php echo nl2br(htmlspecialchars($group['task_name'])); ?>
                                    </td>
                                <?php endif; ?>

                                <td
                                    style="width: 33%; vertical-align: top; text-align: left; padding-left: 10px; word-wrap: break-word; white-space: normal; <?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                                    <?php echo nl2br(htmlspecialchars($group['description'][$i])); ?>
                                </td>
                                <td
                                        style="width: 33%; vertical-align: top; text-align: left; padding-left: 10px; word-wrap: break-word; white-space: normal; <?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                                        <?php
                                        // Calculate and display the percentage for this task
                                        $documents_req = $group['documents_required'][$i];
                                        $documents_uploaded = $group['documents_uploaded'][$i];
                                        $percentage = $documents_req > 0 ? ($documents_uploaded / $documents_req) * 100 : 0;
                                        echo !empty($final_signature_data) 
                                        ? htmlspecialchars(round($percentage, 2)) . '% of the target for ' . nl2br(htmlspecialchars($group['description'][$i])) . ' has been accomplished.'
                                        : ''; // Leave the cell empty when no data is available
                                        ?>
                                    </td>
                                    <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                                        <?php echo !empty($final_signature_data) ? htmlspecialchars($group['quality'][$i] ?? '') : ''; ?>
                                    </td>
                                    <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                                        <?php echo !empty($final_signature_data) ? htmlspecialchars($group['efficiency'][$i] ?? '') : ''; ?>
                                    </td>
                                    <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                                        <?php echo !empty($final_signature_data) ? htmlspecialchars($group['timeliness'][$i] ?? '') : ''; ?>
                                    </td>
                                    <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                                        <?php echo !empty($final_signature_data) ? htmlspecialchars($group['quality'][$i] ?? '') : ''; ?>
                                    </td>
                                    <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>"></td>
                                    <!-- Empty cell for the last column -->
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
                                    <td rowspan="<?php echo $rowspan; ?>"
                                        style="width: 33%; vertical-align: top; text-align: left; padding-left: 10px; word-wrap: break-word; white-space: normal;">
                                        <?php echo nl2br(htmlspecialchars($group['task_name'])); ?>
                                    </td>
                                <?php endif; ?>

                                <!-- Task description column -->
                                <td
                                    style="width: 33%; vertical-align: top; text-align: left; padding-left: 10px; word-wrap: break-word; white-space: normal; <?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                                    <?php echo nl2br(htmlspecialchars($group['description'][$i])); ?>
                                </td>

                                <!-- Task percentage completion column -->
                                <td
                                        style="width: 33%; vertical-align: top; text-align: left; padding-left: 10px; word-wrap: break-word; white-space: normal; <?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                                        <?php
                                        $target = $group['target'][$i]; // Replaced 'documents_required' with 'target'
                                        $num_file = $group['num_file'][$i]; // Replaced 'documents_uploaded' with 'num_file'
                                        $percentage = $target > 0 ? ($num_file / $target) * 100 : 0;
                                        echo !empty($final_signature_data) 
                                        ? htmlspecialchars(round($percentage, 2)) . '% of the target for ' . nl2br(htmlspecialchars($group['description'][$i])) . ' has been accomplished.'
                                        : ''; // Leave the cell empty when no data is available
                                        ?>
                                    </td>

                                    <!-- Quality column -->
                                    <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                                        <?php echo !empty($final_signature_data) ? htmlspecialchars($group['quality'][$i] ?? '') : ''; ?>
                                    </td>
                                    <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                                        <?php echo !empty($final_signature_data) ? htmlspecialchars($group['efficiency'][$i] ?? '') : ''; ?>
                                    </td>
                                    <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                                        <?php echo !empty($final_signature_data) ? htmlspecialchars($group['timeliness'][$i] ?? '') : ''; ?>
                                    </td>
                                    <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                                        <?php echo !empty($final_signature_data) ? htmlspecialchars($group['quality'][$i] ?? '') : ''; ?>
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

                            if ($task_count > 0) {
                                $average_subtotal = $total_average / $task_count;
                            
                                // Check if $final_signature_data is not empty
                                if (!empty($final_signature_data)) {
                                    echo htmlspecialchars(number_format($average_subtotal, 2)); // Ensures two decimal places
                                } else {
                                    echo ''; // Temporary null when there is no final signature data
                                }
                            } else {
                                echo ''; // Leave the cell empty when there are no tasks
                            }
                            ?>
                        </td>
                        <td></td>
                    </tr>
                    <!-- Weighted Average for support tasks -->
                    <tr>
                        <td colspan="6" style="text-align: right;"><span>Weighted Average (Average Sub-total * Support
                                %)</span></td>
                        <td>
                        <?php
                            // Ensure that $core_percentage is treated as a percentage and that $task_count is greater than 0
                            if (isset($support_percentage) && $task_count > 0) {
                                // Convert the core percentage to a decimal (e.g., 40 becomes 0.40)
                                $support_percentage_decimal = $support_percentage / 100;

                                // Calculate the weighted average by multiplying the average subtotal by the core percentage
                                $weighted_average_support = $average_subtotal * $support_percentage_decimal;

                                 // Check if $final_signature_data is not empty before displaying
                                 if (!empty($final_signature_data)) {
                                    // Display the weighted average, rounded to 2 decimal places
                                    echo htmlspecialchars(number_format($weighted_average_support, 2));
                                } else {
                                    echo ''; // Temporary null when there is no final signature data
                                }
                            } else {
                                echo ''; // Leave the cell empty if there's no strategic percentage or no tasks
                            }
                            ?>
                        </td>
                        <td></td>
                    </tr>
                    <tr>
                        <td colspan="6" style="text-align: right;"><span>Final Average Rating (Strategic % + Core % +
                                Support %)</span></td>
                        <td>
                            <?php
                            // Ensure the variables exist and assign a default value of 0 if they are not set
                            $weighted_average_strategic = isset($weighted_average_strategic) ? $weighted_average_strategic : 0;
                            $weighted_average_core = isset($weighted_average_core) ? $weighted_average_core : 0;
                            $weighted_average_support = isset($weighted_average_support) ? $weighted_average_support : 0;

                            // Function to calculate the final average rating
                            function calculateFinalAverageRating($weighted_average_core, $weighted_average_support, $include_strategic = true, $weighted_average_strategic = 0)
                            {
                                if ($include_strategic) {
                                    return ($weighted_average_strategic + $weighted_average_core + $weighted_average_support) / 3; // Include strategic
                                } else {
                                    return ($weighted_average_core + $weighted_average_support) / 2; // Exclude strategic
                                }
                            }

                           // Determine whether to include strategic in the calculation
                            $include_strategic = !($role === 'IPCR' && 
                            ($designation === 'None' || $designation === 'Instructor with SO') && 
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
                           // Check if $final_signature_data is not empty before displaying
                                if (!empty($final_signature_data)) {
                                    // Display the final average, rounded to 2 decimal places
                                    echo number_format($final_average, 2);
                                } else {
                                    echo ''; // Temporary null when there is no final signature data
                                }
                            ?>
                        </td>
                        <td></td>
                    </tr>
                    <tr>
                        <td colspan="6" style="text-align: right;"><span>Final Adjectival Rating (O, VS, S, US, OR
                                P)</span></td>
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

                         // Check if $final_signature_data is not empty before displaying
                            if (!empty($final_signature_data)) {
                                // Display the final rating
                                echo htmlspecialchars($finalRating);
                            } else {
                                echo ''; // Temporary null when there is no final signature data
                            }
                            ?>

                        </td>
                        <td></td>
                    </tr>
                    <td colspan="8" style="text-align: left; vertical-align: top; padding: 0; height: 100px;">
                        <p style="margin: 0 0 0 20px; font-size: 12px;"><span>Comments and Recommendations for
                                Development Purposes :</span></p>
                        <p style="margin: 0 0 0 20px; font-size: 12px;">(Includes behavioral competencies)</p>
                        <p style="margin: 0 0 0 20px; font-size: 10px;">
                            <?php echo htmlspecialchars($performance_message ?? ''); ?>
                        </p>
                    </td>
                </tbody>
            </table>

            <div class="review-table-container">
                <table class="review-table">
                    <tr>
                        <td colspan="2" style="text-align: center; vertical-align: top; padding: 0; height:10px;">Discussed
                            with</td>
                        <td colspan="4" style="text-align: center; vertical-align: top; padding: 0; height: 10px;">Assessed
                            by</td>
                        <td colspan="2" style="text-align: center; vertical-align: top; padding: 0; height: 10px;">Final
                            Rating by</td>
                    </tr>
                    <tr>
                        <td class="for-signature"
                            style="position: relative; padding: 0; height: 140px; width: 190px; overflow: hidden; border-bottom: none;">
                            <?php if ($final_signature_data && $final_signature_data): ?>
                                <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; overflow: hidden;">
                                    <img src="data:image/png;base64,<?php echo base64_encode($final_signature_data); ?>"
                                        alt="Signature"
                                        style="position: absolute; top: 50%; left: 50%; width: auto; height: auto; max-width: 100%; max-height: 100%; transform: translate(-50%, -50%); border: none; background-color: transparent;">
                                </div>
                            <?php else: ?>
                                <p style="text-align: center;"></p> <!-- Show nothing if no record or signature -->
                            <?php endif; ?>
                        </td>
                        <td rowspan="3"
                            style="padding: 0; margin: 0; vertical-align: top; text-align: center; width: 100px;">
                            <!-- Set vertical-align to top and text-align to center -->
                            <p style="text-align: center; margin: 0;">Date</p> <!-- Remove margin from the <p> -->
                            <div style="text-align: center; margin-top: 50%;"> <!-- Center the date value -->
                                <?php
                                // Check if created_at is not null or empty
                                if (!empty($user_final_signature_created_at)) {
                                    // Create a DateTime object and format it
                                    echo htmlspecialchars((new DateTime($user_final_signature_created_at))->format('m/d/Y'));
                                } else {
                                    echo '&nbsp;'; // Use a non-breaking space to maintain width
                                }
                                ?>
                            </div>
                        </td>
                        <td class="for-signature"
                            style="position: relative; padding: 0; height: 140px; width: 190px; overflow: hidden; border-bottom: none; vertical-align: top; white-space: normal;">
                            <p
                                style="font-size: 12px; text-align: left; margin: 2px 0; padding: 0; letter-spacing: -0.5px; overflow-wrap: break-word;">
                                I certify that I discussed my assessment of the performance with the employee
                            </p>
                            <div class="for-signature"
                                style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; overflow: hidden; z-index: 1;">
                                <?php if (isset($signature_data_office_head_final) && $signature_data_office_head_final): ?>
                                    <img src="data:image/png;base64,<?php echo base64_encode($signature_data_office_head_final); ?>"
                                        alt="Signature"
                                        style="position: absolute; top: 50%; left: 50%; width: auto; height: auto; max-width: 100%; max-height: 100%; transform: translate(-50%, -50%); z-index: -1;">
                                <?php else: ?>
                                    <p style="text-align: center;"></p>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td rowspan="3"
                            style="padding: 0; margin: 0; vertical-align: top; text-align: center; width: 100px;">
                            <!-- Set vertical-align to top and text-align to center -->
                            <p style="text-align: center; margin: 0;">Date</p> <!-- Remove margin from the <p> -->
                            <div style="text-align: center; margin-top: 50%;"> <!-- Center the date value -->
                                <?php
                                // Check if created_at is not null or empty
                                if (!empty($dean_final_sign_created_at)) {
                                    // Create a DateTime object and format it
                                    echo htmlspecialchars((new DateTime($dean_final_sign_created_at))->format('m/d/Y'));
                                } else {
                                    echo '&nbsp;'; // Use a non-breaking space to maintain width
                                }
                                ?>
                            </div>
                        </td>
                        <td class="for-signature"
                            style="position: relative; padding: 0; height: 140px; width: 190px; overflow: hidden; border-bottom: none; vertical-align: top;">
                            <?php if (isset($signature_data_vpaaqa) && $signature_data_vpaaqa): ?>
                                <img src="data:image/png;base64,<?php echo base64_encode($signature_data_vpaaqa); ?>"
                                    alt="Signature"
                                    style="position: absolute; top: 50%; left: 50%; width: auto; height: auto; max-width: 100%; max-height: 100%; transform: translate(-50%, -50%); z-index: -1;">
                            <?php else: ?>
                                <p style="text-align: center;"></p>
                            <?php endif; ?>
                        </td>
                        <td rowspan="3"
                            style="padding: 0; margin: 0; vertical-align: top; text-align: center; width: 100px;">
                            <!-- Set vertical-align to top and text-align to center -->
                            <p style="text-align: center; margin: 0;">Date</p> <!-- Remove margin from the <p> -->
                            <div style="text-align: center; margin-top: 50%;"> <!-- Center the date value -->
                                <?php
                                // Check if created_at is not null or empty
                                if (!empty($vpaa_final_sign_created)) {
                                    // Create a DateTime object and format it
                                    echo htmlspecialchars((new DateTime($vpaa_final_sign_created))->format('m/d/Y'));
                                } else {
                                    echo '&nbsp;'; // Use a non-breaking space to maintain width
                                }
                                ?>
                            </div>
                        <td class="for-signature"
                            style="position: relative; padding: 0; height: 140px; width: 190px; overflow: hidden; border-bottom: none;">
                            <?php if (isset($signature_final_data_president) && $signature_final_data_president): ?>
                                <img src="data:image/png;base64,<?php echo base64_encode($signature_final_data_president); ?>"
                                    alt="Signature"
                                    style="position: absolute; top: 50%; left: 50%; width: auto; height: auto; max-width: 100%; max-height: 100%; transform: translate(-50%, -50%); z-index: -1;">
                            <?php else: ?>
                                <p style="text-align: center;"></p>
                            <?php endif; ?>
                        </td>
                        <td rowspan="3"
                            style="padding: 0; margin: 0; vertical-align: top; text-align: center; width: 100px;">
                            <!-- Set vertical-align to top and text-align to center -->
                            <p style="text-align: center; margin: 0;">Date</p> <!-- Remove margin from the <p> -->
                            <div style="text-align: center; margin-top: 50%;"> <!-- Center the date value -->
                                <?php
                                // Check if created_at is not null or empty
                                if (!empty($president_final_sign_created)) {
                                    // Create a DateTime object and format it
                                    echo htmlspecialchars((new DateTime($president_final_sign_created))->format('m/d/Y'));
                                } else {
                                    echo '&nbsp;'; // Use a non-breaking space to maintain width
                                }
                                ?>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td
                            style="border-top: none; border-left: 1px solid black; border-right: 1px solid black; padding: 0;">
                            <span><?php echo htmlspecialchars($_SESSION['fullnamewithsuffix']); ?></span>
                        </td>
                        <td
                            style="border-top: none; border-left: 1px solid black; border-right: 1px solid black; padding: 0;">
                            <span><?php echo htmlspecialchars($collegeDean); ?></span>
                        </td>
                        <td
                            style="border-top: none; border-left: 1px solid black; border-right: 1px solid black; padding: 0;">
                            <span><?php echo htmlspecialchars($vpaaqa); ?></span>
                        </td>
                        <td
                            style="border-top: none; border-left: 1px solid black; border-right: 1px solid black; padding: 0;">
                            <span><?php echo htmlspecialchars($collegePresident); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <td
                            style="border-top: none; border-left: 1px solid black; border-right: 1px solid black; padding: 0;">
                            Ratee</td>
                        <td
                            style="border-top: none; border-left: 1px solid black; border-right: 1px solid black; padding: 0;">
                            Immediate Supervisor</td>
                        <td
                            style="border-top: none; border-left: 1px solid black; border-right: 1px solid black; padding: 0;">
                            VP for Academic Affairs and<br> Quality Assurance</td>
                        <td
                            style="border-top: none; border-left: 1px solid black; border-right: 1px solid black; padding: 0;">
                            College President</td>
                    </tr>
                </table>
            <div>
            <p style="font-style: italic; font-size: 8px;">Legend 1 - Quality 2 - Efficiency 3 - Timeliness 4 - Average
            </p>
        </div>
    </body>

</html>

<script>
    function printDocument() {
        window.print();
    }
</script>
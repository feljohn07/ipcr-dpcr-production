<?php
session_start();

include '../../dbconnections/config.php'; // Adjust the path if necessary

// Fetch the firstname, middlename, lastname, and suffix of the Office Head in the same college
$college = $_SESSION['college']; // Get the user's college from the session
$sql_office_head = "SELECT firstname, middlename, lastname, suffix FROM usersinfo WHERE college = ? AND role = 'Office Head'";
$stmt = $conn->prepare($sql_office_head);
$stmt->bind_param("s", $college);
$stmt->execute();
$result_office_head = $stmt->get_result();

$office_head_firstname = "";
$office_head_middlename = "";
$office_head_lastname = "";
$office_head_suffix = ""; // Added suffix variable

// Fetch the firstname, middlename, lastname, and suffix of the VPAAQA
$sql_vpaaqa = "SELECT firstname, middlename, lastname, suffix FROM usersinfo WHERE role = 'VPAAQA'";
$result_vpaaqa = $conn->query($sql_vpaaqa);

$vpaaqa_firstname = "";
$vpaaqa_middlename = "";
$vpaaqa_lastname = "";
$vpaaqa_suffix = ""; // Added suffix variable

// Fetch VPAAQA data
if ($result_vpaaqa->num_rows > 0) {
    $row = $result_vpaaqa->fetch_assoc();
    $vpaaqa_firstname = strtoupper($row["firstname"]);
    $vpaaqa_middlename = strtoupper($row["middlename"]);
    $vpaaqa_lastname = strtoupper($row["lastname"]);
    $vpaaqa_suffix = ($row["suffix"]); // Fetching suffix
} else {
    $vpaaqa_firstname = "NO VPAAQA AVAILABLE";
    $vpaaqa_middlename = "";
    $vpaaqa_lastname = "";
    $vpaaqa_suffix = ""; // Default suffix
}


// Fetch the firstname, middlename, lastname, and suffix of the College President
$sql_college_president = "SELECT firstname, middlename, lastname, suffix FROM usersinfo WHERE role = 'College President'";
$result_college_president = $conn->query($sql_college_president);

$collegePresident_firstname = "";
$collegePresident_middlename = "";
$collegePresident_lastname = "";
$collegePresident_suffix = ""; // Added suffix variable

// Fetch College President data
if ($result_college_president->num_rows > 0) {
    $row = $result_college_president->fetch_assoc();
    $collegePresident_firstname = strtoupper($row["firstname"]);
    $collegePresident_middlename = strtoupper($row["middlename"]);
    $collegePresident_lastname = strtoupper($row["lastname"]);
    $collegePresident_suffix = ($row["suffix"]); // Fetching suffix
} else {
    $collegePresident_firstname = "NO COLLEGE PRESIDENT AVAILABLE";
    $collegePresident_middlename = "";
    $collegePresident_lastname = "";
    $collegePresident_suffix = ""; // Default suffix
}

// Fetch Office Head data
if ($result_office_head->num_rows > 0) {
    $row = $result_office_head->fetch_assoc();
    $office_head_firstname = strtoupper($row["firstname"]);
    $office_head_middlename = strtoupper($row["middlename"]);
    $office_head_lastname = strtoupper($row["lastname"]);
    $office_head_suffix = ($row["suffix"]); // Fetching suffix
} else {
    $office_head_firstname = "NO OFFICE HEAD AVAILABLE";
    $office_head_middlename = "";
    $office_head_lastname = "";
    $office_head_suffix = ""; // Default suffix
}

// Fetch the user's role, designation, and position from the session
$role = $_SESSION['role']; // User's role
$designation = $_SESSION['designation']; // User's designation
$user_position = isset($_SESSION['position']) ? $_SESSION['position'] : null; // Check if position is set

// Initialize the values
$support_value = 'Undefined';
$core_value = 'Undefined';
$strategic_value = 'Undefined';

    // New condition for IPCR with designation as Dean
    if ($role === 'IPCR' && $designation === 'Dean') {
        // SQL query to fetch values for "Office Head"
        $sql_rdm = "SELECT support, core, strategic FROM rdm WHERE position = 'Office Head'";

        // Prepare and execute the statement
        if ($stmt = $conn->prepare($sql_rdm)) $stmt->execute();
            $result_rdm = $stmt->get_result();

            // Check if results exist
            if ($result_rdm->num_rows > 0) {
                $row = $result_rdm->fetch_assoc();
                $support_value = $row['support'];
                $core_value = $row['core'];
                $strategic_value = $row['strategic'];
            }
            
            // Close the statement
            $stmt->close();
        } 

// Fetch the user's role, designation, and position from the session
$role = $_SESSION['role']; // User's role
$designation = $_SESSION['designation']; // User's designation
$user_position = isset($_SESSION['position']) ? $_SESSION['position'] : null; // Check if position is set

// Initialize the values
$support_value = 'Undefined';
$core_value = 'Undefined';
$strategic_value = 'Undefined';

// Check if the user is a Dean (case insensitive)
if ($role === 'IPCR' && (strcasecmp($designation, 'Dean') === 0)) {
    // SQL query to fetch values for "Office Head"
    $sql_rdm = "SELECT support, core, strategic FROM rdm WHERE position = 'Office Head'";

    // Prepare and execute the statement
    if ($stmt = $conn->prepare($sql_rdm)) {
        $stmt->execute();
        $result_rdm = $stmt->get_result();

        // Check if results exist
        if ($result_rdm->num_rows > 0) {
            $row = $result_rdm->fetch_assoc();
            $support_value = $row['support'];
            $core_value = $row['core'];
            $strategic_value = $row['strategic'];
        }
        
        // Close the statement
        $stmt->close();
    }
} else if (!empty($user_position) && !empty($designation)) {
    // Check conditions for IPCR with None designation and instructor or assistant professor positions
    if ($role === 'IPCR' && $designation === 'None' && 
        (preg_match('/^instructor-[1-3]$/', $user_position) || 
         preg_match('/^assistant-professor-[1-4]$/', $user_position))) {
        
        // SQL query to fetch values for "Instructor to Assistant Professors"
        $sql_rdm = "SELECT support, core, strategic FROM rdm WHERE position = 'Instructor to Assistant Professors'";
        
        // Prepare and execute the statement
        if ($stmt = $conn->prepare($sql_rdm)) {
            $stmt->execute();
            $result_rdm = $stmt->get_result();

            // Check if results exist
            if ($result_rdm->num_rows > 0) {
                $row = $result_rdm->fetch_assoc();
                $support_value = $row['support'];
                $core_value = $row['core'];
                $strategic_value = $row['strategic'];
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
                $support_value = $row['support'];
                $core_value = $row['core'];
                $strategic_value = $row['strategic'];
            }
            
            // Close the statement
            $stmt->close();
        } 
    }

    // Condition for Faculty with Designation
    if ($role === 'IPCR' && 
    $designation !== 'None' && 
    $designation !== 'Dean' &&  // Exclude Dean designation
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
                $support_value = $row['support'];
                $core_value = $row['core'];
                $strategic_value = $row['strategic'];
            }
            
            // Close the statement
            $stmt->close();
        } 
    }
}

// Close the connection
$conn->close();
?>




<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IPCR Document</title>
    <style>
        .container {
            width: 80%;
            margin: 0 auto;
            text-align: center;
            margin-top: 40px;
            margin-bottom : 20px; 
        }
        h1 {
            text-align: center;
            text-transform: uppercase;
            font-size: 18px;
            margin-bottom: 20px;
        }
        p {
            font-size: 14px;
            line-height: 1.6;
            text-align: justify;
        }
        .signatures {
            margin: 40px 0;
            display: flex;
            justify-content: flex-end; /* Aligns items to the right */
        }
        .ratee {
            text-align: center;
            margin-left: auto; /* Ensures the ratee is on the right side */
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
            text-align: center;
        }
        .review-table th {
            background-color: #f2f2f2;
        }
        .review-table td {  
            font-size: 14px;
        }
        .highlighted {
            font-size: 17px;
            font-weight: bold;
        }
        .no-border {
            border: none;
        }
        .section-title {
            font-weight: bold;
            background-color: #f2f2f2;
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
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>INDIVIDUAL PERFORMANCE COMMITMENT AND REVIEW (IPCR)</h1>
        <p>
            I, <strong><?php echo mb_strtoupper($_SESSION['firstname'] . ' ' . $_SESSION['middlename'] . ' ' . $_SESSION['lastname']); ?></strong> of the <strong><?php echo $_SESSION['college']; ?></strong> 
            commit to deliver and agree to be rated on the attainment of the following targets 
            in accordance with the indicated measures for the period 
            <strong>______________</strong>.
        </p>
        <div class="signatures">
            <div class="ratee" style="margin-left : 60%;">
                <p style="text-align:center; ">
                    <span style="text-decoration: underline; line-height: 2; font-weight: bold; text-align : center;">
                            <strong>
                            <?php 
                            echo mb_strtoupper($_SESSION['firstname'] . ' ' . $_SESSION['middlename'] . ' ' . $_SESSION['lastname']) . 
                            (!empty($_SESSION['suffix']) ? ', ' . $_SESSION['suffix'] : ''); 
                            ?>
                        </strong>
                    </span><br>
                    <span style="display: block; text-align: center;">Ratee</span>
                </p>
                <p style="text-align:center; ">Date : <span class="underline" style="margin-left: 5px; line-height: 0.8;"><?php echo date('F j, Y'); ?></span></p>
            </div>
        </div>
        <table class="review-table">
            <tr>
                <th>Reviewed by:</th>
                <th>Date</th>
                <th>Approved by:</th>
                <th>Date</th>
            </tr>
            <tr>
                <td><strong class="highlighted">
                <?php 
                    if ($_SESSION['designation'] === 'Dean') {
                        // If the designation is Dean, show the VPAAQA's name
                        echo $vpaaqa_firstname . ' ' . $vpaaqa_middlename . ' ' . $vpaaqa_lastname . 
                            (!empty($vpaaqa_suffix) ? ', ' . $vpaaqa_suffix : ''); 
                    } else {
                        // Otherwise, show the Office Head's name
                        echo $office_head_firstname . ' ' . $office_head_middlename . ' ' . $office_head_lastname . 
                            (!empty($office_head_suffix) ? ', ' . $office_head_suffix : ''); 
                    }
                    ?>
                </strong><br>Immediate Supervisor</td>
                <td></td>
                <td><strong class="highlighted">
                    <?php echo $collegePresident_firstname . ' ' . $collegePresident_middlename . ' ' . $collegePresident_lastname . 
                            (!empty($collegePresident_suffix) ? ', ' . $collegePresident_suffix : ''); ?>
                </strong><br>College President</td>
                <td></td>
            </tr>
        </table>
        <table class="review-table">
            <thead>
                <tr>
                    <th>Outputs</th>
                    <th>Success Indicator (Target + Measures)</th>
                    <th>Actual Accomplishment</th>
                    <th colspan="4" class="center-text">Rating</th>
                    <th>Remarks</th>
                </tr>
                <tr>
                    <th class="no-border"></th>
                    <th class="no-border"></th>
                    <th class="no-border"></th>
                    <th class="center-text">Q</th>
                    <th class="center-text">E</th>
                    <th class="center-text">T</th>
                    <th class="center-text">A</th>
                    <th class="no-border"></th>
                </tr>
            </thead>
            <tbody>
    <tr>
        <td class="section-title" colspan="8" style="text-align: left">Strategic Priority (<?php echo $strategic_value; ?>%)</td>
    </tr>
    <tr>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
    <tr>
        <td class="no-border"></td>
        <td class="no-border"></td>
        <td class="no-border"></td>
        <td class="no-border"></td>
        <td class="no-border"></td>
        <td class="no-border"></td>
        <td class="no-border"></td>
        <td class="no-border"></td>
    </tr>
    <tr>
        <td class="no-border"></td>
        <td class="no-border"></td>
        <td class="no-border"></td>
        <td class="no-border"></td>
        <td class="no-border"></td>
        <td class="no-border"></td>
        <td class="no-border"></td>
        <td class="no-border"></td>
    </tr>
    <tr>
        <td class="section-title" colspan="8" style="text-align: left">Core Functions (<?php echo $core_value; ?>%)</td>
    </tr>
    <tr>
        <td>Output 1</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
    <tr>
        <td class="no-border"></td>
        <td class="no-border"></td>
        <td class="no-border"></td>
        <td class="no-border"></td>
        <td class="no-border"></td>
        <td class="no-border"></td>
        <td class="no-border"></td>
        <td class="no-border"></td>
    </tr>
    <tr>
        <td class="no-border"></td>
        <td class="no-border"></td>
        <td class="no-border"></td>
        <td class="no-border"></td>
        <td class="no-border"></td>
        <td class="no-border"></td>
        <td class="no-border"></td>
        <td class="no-border"></td>
    </tr>
    <tr>
        <td class="section-title" colspan="8" style="text-align: left">Support Functions (<?php echo $support_value; ?>%)</td>
    </tr>
    <tr>
        <td>Output 1</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
    <tr>
        <td class="no-border"></td>
        <td class="no-border"></td>
        <td class="no-border"></td>
        <td class="no-border"></td>
        <td class="no-border"></td>
        <td class="no-border"></td>
        <td class="no-border"></td>
        <td class="no-border"></td>
    </tr>
    <tr>
        <td class="no-border"></td>
        <td class="no-border"></td>
        <td class="no-border"></td>
        <td class="no-border"></td>
        <td class="no-border"></td>
        <td class="no-border"></td>
        <td class="no-border"></td>
        <td class="no-border"></td>
    </tr>
    <tr>
        <td class="no-border"></td>
        <td class="no-border"></td>
        <td class="no-border"></td>
        <td class="no-border"></td>
        <td class="no-border"></td>
        <td class="no-border"></td>
        <td class="no-border"></td>
        <td class="no-border"></td>
    </tr>
</tbody>

        </table>
        <div class="comment-box">
            <p><strong>Comments and Recommendations for Development Purposes:</strong></p>
            <p>(Includes behavioral competencies)</p>
        </div>
        <table class="review-table">
        <tr>
            <td>
                <strong>
                    <?php 
                    echo mb_strtoupper($_SESSION['firstname'] . ' ' . $_SESSION['middlename'] . ' ' . $_SESSION['lastname']) . 
                    (!empty($_SESSION['suffix']) ? ', ' . $_SESSION['suffix'] : ''); 
                    ?>
                </strong>
            </td>
            <td rowspan="3" style="text-align: center; vertical-align: top;">Date</td>
            <td>
                <strong class="highlighted">
                    <?php 
                    if ($_SESSION['designation'] === 'Dean') {
                        // If the designation is Dean, show the VPAAQA's name
                        echo $vpaaqa_firstname . ' ' . $vpaaqa_middlename . ' ' . $vpaaqa_lastname . 
                            (!empty($vpaaqa_suffix) ? ', ' . $vpaaqa_suffix : ''); 
                    } else {
                        // Otherwise, show the Office Head's name
                        echo $office_head_firstname . ' ' . $office_head_middlename . ' ' . $office_head_lastname . 
                            (!empty($office_head_suffix) ? ', ' . $office_head_suffix : ''); 
                    }
                    ?>
                </strong>
            </td>
            <td rowspan="3" style="text-align: center; vertical-align: top;">Date</td>
            <td>
                <strong class="highlighted">
                    <?php 
                        echo $vpaaqa_firstname . ' ' . $vpaaqa_middlename . ' ' . $vpaaqa_lastname . 
                            (!empty($vpaaqa_suffix) ? ', ' . $vpaaqa_suffix : ''); 
                    ?>
                </strong>
            </td>
            <td rowspan="3" style="text-align: center; vertical-align: top;">Date</td>
            <td>
                <strong class="highlighted">
                    <?php echo $collegePresident_firstname . ' ' . $collegePresident_middlename . ' ' . $collegePresident_lastname . 
                            (!empty($collegePresident_suffix) ? ', ' . $collegePresident_suffix : ''); ?>
                </strong>
            </td>
            <td rowspan="3" style="text-align: center; vertical-align: top;">Date</td>
        </tr>
        <tr>
                <td style="border-top: none; border-left: 1px solid black; border-right: 1px solid black; padding: 0;">Ratee</td>
                <td style="border-top: none; border-left: 1px solid black; border-right: 1px solid black; padding: 0;">Immediate Supervisor</td>
                <td style="border-top: none; border-left: 1px solid black; border-right: 1px solid black; padding: 0;">VP for Academic Affairs and<br> Quality Assurance</td>
                <td style="border-top: none; border-left: 1px solid black; border-right: 1px solid black; padding: 0;">College President</td>
            </tr>
        </table>
    </div>
</body>
</html>
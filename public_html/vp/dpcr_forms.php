<?php
session_start();

include '../dbconnections/config.php'; // Ensure this path is correct and the file sets up $conn

// Initialize variables to hold the names
$officeHeadName = '';
$collegePresident = '';
$vpaaqa = '';

// Check if 'office_head_id' is provided in the URL
if (isset($_GET['office_head_id']) && !empty($_GET['office_head_id'])) {
    $office_head_id = $_GET['office_head_id']; // Get the office head ID from the URL

    // Fetch Office Head's Name and College
    $sql = "SELECT firstname, middlename, lastname, suffix, college FROM usersinfo WHERE idnumber = ? LIMIT 1";
    $office_head_stmt = $conn->prepare($sql);
    $office_head_stmt->bind_param("s", $office_head_id);
    $office_head_stmt->execute();
    $result = $office_head_stmt->get_result();

    // Initialize variables
    $officeHeadName = "Not Found";
    $officeHeadNameWithoutSuffix = "Not Found";
    $college_users = "Not Found";

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        // Build the office head's name with the suffix
        $officeHeadName = strtoupper($row["firstname"]) . " " 
                        . strtoupper($row["middlename"]) . " " 
                        . strtoupper($row["lastname"]);

        if (!empty($row["suffix"])) {
            $officeHeadName .= ", " . $row["suffix"]; // Add suffix if available
        }

        // Build the office head's name without the suffix
        $officeHeadNameWithoutSuffix = strtoupper($row["firstname"]) . " " 
                                     . strtoupper($row["middlename"]) . " " 
                                     . strtoupper($row["lastname"]);

        // Sanitize both names
        $officeHeadName = htmlspecialchars($officeHeadName, ENT_QUOTES);
        $officeHeadNameWithoutSuffix = htmlspecialchars($officeHeadNameWithoutSuffix, ENT_QUOTES);

        // Get and sanitize the college
        $college_users = !empty($row["college"]) ? htmlspecialchars($row["college"], ENT_QUOTES) : "College not specified";
    }

    $office_head_stmt->close();
} else {
    $officeHeadName = "Office Head ID not provided.";
    $officeHeadNameWithoutSuffix = "Office Head ID not provided.";
    $college_users = "College information not available.";

    
}


// Now you can use $officeHeadName and $college as needed


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

// At this point, $collegePresident and $vpaaqa contain the formatted names
// You can use these variables as needed in your application




// Check if 'semester_id' is provided in the URL for the Signature
if (isset($_GET['semester_id']) && !empty($_GET['semester_id'])) {
    $semester_id = intval($_GET['semester_id']); // Sanitize input

    // Fetch semester details
    $semester_stmt = $conn->prepare("SELECT semester_name, start_date, end_date, vpapproval, presidentapproval, final_approval_vpaa, final_approval_press, vp_first_created_at, vp_final_created_at, press_first_created_at, press_final_created_at, userapproval, users_final_approval, dean_first_approval_created_at, dean_final_approval_created_at FROM semester_tasks WHERE semester_id = ?");
    $semester_stmt->bind_param("i", $semester_id);
    $semester_stmt->execute();
    $semester_result = $semester_stmt->get_result();
    $semester = $semester_result->fetch_assoc();
    $semester_stmt->close();

    // Use the office head ID from the URL instead of session
    if (isset($_GET['office_head_id']) && !empty($_GET['office_head_id'])) {
        $office_head_id = $_GET['office_head_id']; // Get the office head ID from the URL

        $signature_stmt = $conn->prepare("SELECT data FROM signature WHERE idnumber = ?");
        $signature_stmt->bind_param("s", $office_head_id); // Use the office head ID for fetching signature
        $signature_stmt->execute();
        $signature_stmt->bind_result($signature_data);
        $signature_stmt->fetch();
        $signature_stmt->close();
    } else {
        // Handle the case where office head ID is not provided
        echo "Error: Office Head ID not provided.";
    }
    
        // Check if users_final_approval is 1
        if ($semester['users_final_approval'] == 1) {
            // Fetch the signature based on the office head ID
            $signature_stmt = $conn->prepare("SELECT data FROM signature WHERE idnumber = ?");
            $signature_stmt->bind_param("s", $office_head_id); // Use the office head ID for fetching signature
            $signature_stmt->execute();
            $signature_stmt->bind_result($signature_final);
            $signature_stmt->fetch();
            $signature_stmt->close();
        } else {
            // If users_final_approval is not 1, set the signature_final to null
            $signature_final = null; // or use an empty string ''
        }


    // Check if vpapproval is 1 before fetching the VPAA signature
    if ($semester['vpapproval'] == 1) {
        // Fetch VPAA signature
        $signature_stmt = $conn->prepare("SELECT data FROM signature WHERE role = ?");
        $role = 'VPAAQA'; // Define the role you are looking for
        $signature_stmt->bind_param("s", $role);
        $signature_stmt->execute();
        $signature_stmt->bind_result($signature_data_vpaaqa);
        $signature_stmt->fetch();
        $signature_stmt->close();
    } else {
        // If vpapproval is not 1, set the signature data to null or empty
        $signature_data_vpaaqa = null; // or use an empty string ''
    }

    // Check if vpapproval is 1 before fetching the VPAA signature
    if ($semester['final_approval_vpaa'] == 1) {
        // Fetch VPAA signature
        $signature_stmt = $conn->prepare("SELECT data FROM signature WHERE role = ?");
        $role = 'VPAAQA'; // Define the role you are looking for
        $signature_stmt->bind_param("s", $role);
        $signature_stmt->execute();
        $signature_stmt->bind_result($signature_final_data_vpaaqa);
        $signature_stmt->fetch();
        $signature_stmt->close();
    } else {
        // If vpapproval is not 1, set the signature data to null or empty
        $signature_final_data_vpaaqa = null; // or use an empty string ''
    }    

    // Check if presidentfirstapproval 
    if ($semester['presidentapproval'] == 1) {
        // Fetch VPAA signature
        $signature_stmt = $conn->prepare("SELECT data FROM signature WHERE role = ?");
        $role = 'College president'; // Define the role you are looking for
        $signature_stmt->bind_param("s", $role);
        $signature_stmt->execute();
        $signature_stmt->bind_result($signature_data_president);
        $signature_stmt->fetch();
        $signature_stmt->close();
    } else {
        // If vpapproval is not 1, set the signature data to null or empty
        $signature_data_president = null; // or use an empty string ''
    }   

    // Check if presidentfirstapproval 
    if ($semester['final_approval_press'] == 1) {
        // Fetch VPAA signature
        $signature_stmt = $conn->prepare("SELECT data FROM signature WHERE role = ?");
        $role = 'College president'; // Define the role you are looking for
        $signature_stmt->bind_param("s", $role);
        $signature_stmt->execute();
        $signature_stmt->bind_result($signature_finaldata_president);
        $signature_stmt->fetch();
        $signature_stmt->close();
    } else {
        // If vpapproval is not 1, set the signature data to null or empty
        $signature_finaldata_president = null; // or use an empty string ''
    }   
}


// Check if 'semester_id' is provided in the URL
if (isset($_GET['semester_id']) && !empty($_GET['semester_id'])) {
    $semester_id = intval($_GET['semester_id']); // Sanitize input

    // Fetch semester details
    $semester_stmt = $conn->prepare("SELECT semester_name,start_date, end_date, vpapproval, presidentapproval, final_approval_vpaa, final_approval_press, vp_first_created_at, vp_final_created_at, press_first_created_at, press_final_created_at, userapproval, users_final_approval, dean_first_approval_created_at, dean_final_approval_created_at FROM semester_tasks WHERE semester_id = ?");
    $semester_stmt->bind_param("i", $semester_id);
    $semester_stmt->execute();
    $semester_result = $semester_stmt->get_result();
    $semester = $semester_result->fetch_assoc();
    $semester_stmt->close();

    // Fetch strategic tasks including documents_uploaded and sibling_code
    $strategic_stmt = $conn->prepare("SELECT task_name, description, documents_req, documents_uploaded, quality, efficiency, timeliness, average, sibling_code FROM strategic_tasks WHERE semester_id = ?");
    $strategic_stmt->bind_param("i", $semester_id);
    $strategic_stmt->execute();
    $strategic_result = $strategic_stmt->get_result();
    $strategic_tasks = $strategic_result->fetch_all(MYSQLI_ASSOC);
    $strategic_stmt->close();

    // Fetch core tasks including documents_uploaded and sibling_code
    $core_stmt = $conn->prepare("SELECT task_name, description, documents_req, documents_uploaded, quality, efficiency, timeliness, average, sibling_code FROM core_tasks WHERE semester_id = ?");
    $core_stmt->bind_param("i", $semester_id);
    $core_stmt->execute();
    $core_result = $core_stmt->get_result();
    $core_tasks = $core_result->fetch_all(MYSQLI_ASSOC);
    $core_stmt->close();

    // Fetch support tasks including documents_uploaded and sibling_code
    $support_stmt = $conn->prepare("SELECT task_name, description, documents_req, documents_uploaded, quality, efficiency, timeliness, average, sibling_code FROM support_tasks WHERE semester_id = ?");
    $support_stmt->bind_param("i", $semester_id);
    $support_stmt->execute();
    $support_result = $support_stmt->get_result();
    $support_tasks = $support_result->fetch_all(MYSQLI_ASSOC);
    $support_stmt->close();

    // Fetch data from the database
    $sql = "SELECT position, strategic, core, support FROM rdm WHERE position = 1"; // Modify the query as needed
    $result = $conn->query($sql);

    $strategic = "N/A"; // Default value
    $core = "N/A"; // Default value
    $support = "N/A"; // Default value

    if ($result->num_rows > 0) {
        // Fetch the row
        $row = $result->fetch_assoc();
        $strategic = $row['strategic']; // Get the strategic value
        $core = $row['core']; // Get the core value
        $support = $row['support']; // Get the support value
    }

    // Calculate weighted averages and final rating
    $weightedAverageStrategic = 0; // Initialize weighted average for strategic tasks
    $weightedAverageCore = 0; // Initialize weighted average for core tasks
    $weightedAverageSupport = 0; // Initialize weighted average for support tasks

    // Calculate weighted average for strategic tasks
    if (count($strategic_tasks) > 0) {
        $totalStrategicAverage = 0;
        foreach ($strategic_tasks as $task) {
            $totalStrategicAverage += $task['average'];
        }
        $averageStrategic = $totalStrategicAverage / count($strategic_tasks);
        $weightedAverageStrategic = $averageStrategic * ($strategic / 100);
    }

    // Calculate weighted average for core tasks
    if (count($core_tasks) > 0) {
        $totalCoreAverage = 0;
        foreach ($core_tasks as $task) {
            $totalCoreAverage += $task['average'];
        }
        $averageCore = $totalCoreAverage / count($core_tasks);
        $weightedAverageCore = $averageCore * ($core / 100);
    }

    // Calculate weighted average for support tasks
    if (count($support_tasks) > 0) {
        $totalSupportAverage = 0;
        foreach ($support_tasks as $task) {
            $totalSupportAverage += $task['average'];
        }
        $averageSupport = $totalSupportAverage / count($support_tasks);
        $weightedAverageSupport = $averageSupport * ($support / 100);
    }

    // Final Average Calculation
    $finalAverage = $weightedAverageStrategic + $weightedAverageCore + $weightedAverageSupport;

    // Determine Final Rating
    $finalRating = '';
    if ($finalAverage >= 4.5) {
        $finalRating = 'O'; // Outstanding
    } elseif ($finalAverage >= 3.5) {
        $finalRating = 'VS'; // Very Satisfactory
    } elseif ($finalAverage >= 2.5) {
        $finalRating = 'S'; // Satisfactory
    } elseif ($finalAverage >= 1.0) {
        $finalRating = 'US'; // Unsatisfactory
    } else {
        $finalRating = 'P'; // Poor
    }

    // Assuming you have fetched the college information for the user
    $college = ''; // Initialize the variable for college

    // Fetch the college of the user based on their idnumber
    $college_stmt = $conn->prepare("SELECT college FROM usersinfo WHERE idnumber = ?");
    $college_stmt->bind_param("s", $_SESSION['idnumber']);
    $college_stmt->execute();
    $college_stmt->bind_result($college);
    $college_stmt->fetch();
    $college_stmt->close();

    // Insert calculated values into the dpcr_performance_table
// Insert or update calculated values into the dpcr_performance_table
$insert_stmt = $conn->prepare("
    INSERT INTO dpcr_performance_table (semester_id, weighted_average_strategic, weighted_average_core, final_average_rating, final_rating, idnumber, college, created_at) 
    VALUES (?, ?, ?, ?, ?, ?, ?, NOW()) 
    ON DUPLICATE KEY UPDATE 
        weighted_average_strategic = VALUES(weighted_average_strategic), 
        weighted_average_core = VALUES(weighted_average_core), 
        final_average_rating = VALUES(final_average_rating), 
        final_rating = VALUES(final_rating), 
        college = VALUES(college), 
        created_at = NOW()
");

$insert_stmt->bind_param("iddssss", $semester_id, $weightedAverageStrategic, $weightedAverageCore, $finalAverage, $finalRating, $_SESSION['idnumber'], $college);

if ($insert_stmt->execute()) {
    // Success message or further actions
} else {
    echo "Error saving performance ratings: " . $insert_stmt->error;
}
$insert_stmt->close();

} else {
    // Handle the case where 'semester_id' is not set
    $semester_id = null;
    $semester = [];
    $strategic_tasks = [];
    $core_tasks = [];
    $support_tasks = [];
    echo "Error: Semester ID not provided.";
}

$conn->close();
?>
    
    
    


    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>DPCR Document</title>
<style>
    .container {
        width: 100%; /* Set the container to full width */
        margin: 0; /* Adjust left and right margins */
        box-sizing: border-box; /* Include padding and border in the element's total width and height */
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

    .background-image, .background-image-president {
    position: absolute;
    width: 50%;
    height: 60%;
    z-index: -1;
    border: solid; /* You can specify border properties here */
}

.background-image {
    top: 30%; /* Adjusted to move it higher */
    left: 0; /* Align to the left */
}

.background-image-user {
    top: 40px !important;
    left: 0; /* Align to the left */
}

.background-image-president {
    top: 30%; /* Adjusted to move it higher */
    right: 0; /* Align to the right */
}

.background-image img, .background-image-president img {
    position: absolute;
    top: 50%;
    left: 15%; /* Adjust this value as necessary */
    width: 40%;
    height: 10%;
    object-fit: cover;
    border: none;
    background-color: transparent;
}

.background-image-president img {
    position: absolute;
    top: 0;
    left: 15%; /* Adjust this value as necessary */
    width: 40%;
    height: 30%;
    object-fit: cover;
    border: none;
    background-color: transparent;
}

/* Media Queries for Responsive Design */
@media (max-width: 768px) {
    .background-image, .background-image-president {
        width: 100%; /* Full width on smaller screens */
        height: auto; /* Adjust height as necessary */
    }

    .background-image img, .background-image-president img {
        width: 80%; /* Adjust image width on smaller screens */
        height: auto; /* Maintain aspect ratio */
        left: 10%; /* Center the image */
    }
}
    
}

</style>
    </head>
    <body> 
        <div class="container">
            <h1 style="padding-top: 0;">DEPARTMENT PERFORMANCE COMMITMENT AND REVIEW (DPCR)</h1>
            <p>
    I, <span style="text-decoration: underline; line-height: 1.2; font-weight: bold;"><?php echo (htmlspecialchars($officeHeadNameWithoutSuffix)); ?></span>  of the <span style="text-decoration: underline; line-height: 1.2; font-weight: bold;"> <?php echo (htmlspecialchars($college_users)); ?></span>
    <span style="text-decoration: underline; line-height: 1.2; font-weight: bold;"><?php echo strtoupper(htmlspecialchars($_SESSION['college'])); ?></span> commit to deliver and agree to be rated on the attainment of the following targets 
    in accordance with the indicated measures for the period 
    <strong style="text-decoration: underline; line-height: 1.2; font-weight: bold;"><?php echo htmlspecialchars($semester['semester_name']); ?></strong>.
</p>

            <?php
                date_default_timezone_set('Asia/Manila'); // Set the default timezone to Asia/Manila
            ?>

                <div class="signatures">
                    <div class="ratee" style="margin-left : 60%;">
                        <p style="text-align:center; ">
                                <span style="text-decoration: underline; line-height: 2; font-weight: bold; text-align : center;">
                                    <strong><?php echo (htmlspecialchars($officeHeadName)); ?></strong>
                                </span><br>
                                <span style="display: block; text-align: center;">Ratee</span>
                            </p>
                        <p style="text-align:center; ">Date : <span class="underline" style="margin-left: 5px; line-height: 0.8;"><?php echo date('F j, Y'); ?></span></p>
                    </div>
                </div>

                <table class="review-table" style="position: relative; width: 100%; height: auto; border-collapse: collapse;">
                      <!-- Image container for the background -->
                <div class="background-image-user" style="position: absolute; top: 120px; left: 40%; width: 50%; height: 60%; z-index: -1; border:none;">
                    <?php if (isset($signature_data) && $signature_data): ?>
                        <img src="data:image/png;base64,<?php echo base64_encode($signature_data); ?>" alt="Signature" 
                            style="position: absolute; top: 0; left: 60%; width: 40%; height: 30%; object-fit: cover; border: none; background-color: transparent;">
                    <?php else: ?>
                        <p style="text-align: center;"></p>
                    <?php endif; ?>
                </div>
                <!-- Image container for the background -->
                <div class="background-image" style="position: absolute; top: 50; left: 0%; width: 50%; height: 60%; z-index: -1; border: none;">
                    <?php if (isset($signature_data_vpaaqa) && $signature_data_vpaaqa): ?>
                        <img src="data:image/png;base64,<?php echo base64_encode($signature_data_vpaaqa); ?>" alt="Signature" 
                            style="position: absolute; top: 0; left: 15%; width: 40%; height: 30%; object-fit: cover; border: none; background-color: transparent;">
                    <?php else: ?>
                        <p style="text-align: center;"></p>
                    <?php endif; ?>
                </div>

                    <!-- Image container for the background -->
                    <div class="background-image-president" style="position: absolute; top: 53; right: 0%; width: 50%; height: 60%; z-index: -1; border: none ;">
                    <?php if (isset($signature_data_president) && $signature_data_president): ?>
                        <img src="data:image/png;base64,<?php echo base64_encode($signature_data_president); ?>" alt="Signature" 
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
                        <strong class="highlighted"><?php echo htmlspecialchars($vpaaqa); ?></strong>
                    </div>
                </td>

                    <td rowspan="2" style="padding: 0; margin: 0; "> <!-- Set a fixed width and zero padding -->
                        <?php 
                        // Check if vp_first_created_at is not null or empty
                        if (!empty($semester['vp_first_created_at'])) {
                            // Create a DateTime object and format it
                            echo htmlspecialchars((new DateTime($semester['vp_first_created_at']))->format('m/d/Y'));
                        } else {
                            echo '&nbsp;'; // Use a non-breaking space to maintain width
                        }
                        ?>
                    </td>
                    <td style="position: relative; padding: 0 0 0 0; height: 75px; border-bottom: none;">
                        <div style="position: absolute; bottom: 0; width: 100%; text-align: center;">
                            <strong class="highlighted"><?php echo htmlspecialchars($collegePresident); ?></strong>
                        </div>
                    </td>

                    <td rowspan="2" style="padding: 0; margin: 0; "> 
                      <?php 
                        // Check if vp_first_created_at is not null or empty
                        if (!empty($semester['press_first_created_at'])) {
                            // Create a DateTime object and format it
                            echo htmlspecialchars((new DateTime($semester['press_first_created_at']))->format('m/d/Y'));
                        } else {
                            echo '&nbsp;'; // Use a non-breaking space to maintain width
                        }
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
        <tr>
            <td class="section-title" colspan="8" style="padding: 0;">Strategic Priority (<?php echo htmlspecialchars($strategic); ?>%)</td>
        </tr>
        <tr>
            <td style="padding: 0; text-align : left">Output I Learning and Development</td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
       <?php
$totalStrategicAverage = 0;

// Group tasks by task_name
$grouped_strategic_tasks = [];
foreach ($strategic_tasks as $task) {
    $key = $task['task_name']; // Use task_name as the grouping key
    if (!isset($grouped_strategic_tasks[$key])) {
        $grouped_strategic_tasks[$key] = [
            'task_name' => $task['task_name'],
            'description' => [],
            'documents_req' => [],
            'documents_uploaded' => [],
            'quality' => [],
            'efficiency' => [],
            'timeliness' => [],
            'average' => [],
        ];
    }
    // Add values for each task in the group
    $grouped_strategic_tasks[$key]['description'][] = $task['description'];
    $grouped_strategic_tasks[$key]['documents_req'][] = $task['documents_req'];
    $grouped_strategic_tasks[$key]['documents_uploaded'][] = $task['documents_uploaded'];
    $grouped_strategic_tasks[$key]['quality'][] = $task['quality'];
    $grouped_strategic_tasks[$key]['efficiency'][] = $task['efficiency'];
    $grouped_strategic_tasks[$key]['timeliness'][] = $task['timeliness'];
    $grouped_strategic_tasks[$key]['average'][] = $task['average'];
    $totalStrategicAverage += $task['average'];
}

// Render the grouped strategic tasks
foreach ($grouped_strategic_tasks as $group) {
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
                    <?php
                    $task_name = stripslashes($group['task_name']);
                    $task_name = str_replace('<break(+)line>', '<br>', $task_name);
                    echo $task_name;
                    ?>
                </td>
            <?php endif; ?>

            <td style="width: 33%; vertical-align: top; text-align: left; padding-left: 10px; word-wrap: break-word; white-space: normal; <?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                <?php
                $description = stripslashes($group['description'][$i]);
                $description = str_replace('<break(+)line>', '<br>', $description);
                echo $description;
                ?>
            </td>
            <td style="width: 33%; vertical-align: top; text-align: left; padding-left: 10px; word-wrap: break-word; white-space: normal; <?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                <?php
                $documents_req = $group['documents_req'][$i];
                $documents_uploaded = $group['documents_uploaded'][$i];
                $percentage = $documents_req > 0 ? ($documents_uploaded / $documents_req) * 100 : 0;
                                // Check if users_final_approval is 1
                                if ($semester['users_final_approval'] == 1) {
                                    // Calculate the percentage and display it
                                    echo htmlspecialchars(round($percentage, 2)) . '% of the target for ' . str_replace('<break(+)line>', '<br>', $description) . ' has been accomplished.';
                                } else {
                                    // If users_final_approval is not 1, replace the value with null (or whatever placeholder you want)
                                    echo null; // Or you can use an empty string or any other placeholder
                                }
                            ?>
                        </td>
                        <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                            <?php 
                            if ($semester['users_final_approval'] == 1) {
                                echo isset($group['quality'][$i]) ? htmlspecialchars($group['quality'][$i]) : null; 
                            } else {
                                echo null; // Or you can leave this empty if you prefer not to display anything
                            }
                            ?>
                        </td>
                        <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                            <?php 
                            if ($semester['users_final_approval'] == 1) {
                                echo isset($group['efficiency'][$i]) ? htmlspecialchars($group['efficiency'][$i]) : null; 
                            } else {
                                echo null; // Or leave empty
                            }
                            ?>
                        </td>
                        <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                            <?php 
                            if ($semester['users_final_approval'] == 1) {
                                echo isset($group['timeliness'][$i]) ? htmlspecialchars($group['timeliness'][$i]) : null; 
                            } else {
                                echo null; // Or leave empty
                            }
                            ?>
                        </td>
                        <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                            <?php 
                            if ($semester['users_final_approval'] == 1) {
                                echo isset($group['average'][$i]) ? htmlspecialchars($group['average'][$i]) : null; 
                            } else {
                                echo null; // Or leave empty
                            }
                            ?>
                        </td>
                        <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>"></td> <!-- Empty cell for the last column -->
                    </tr>
                    <?php
                }
            }
            ?>
            
            <tr>
                <td colspan="6" style="text-align: right;"><span>Average Sub-total</span></td>
                <td>
                    <?php 
                    if ($semester['users_final_approval'] == 1) {
                        echo count($strategic_tasks) > 0 ? number_format($totalStrategicAverage / count($strategic_tasks), 2) : '0.00';
                    } else {
                        echo null; // Or you can choose to display null or leave it empty
                    }
                    ?>
                </td>
                <td></td>
            </tr>
            <tr>
                <td colspan="6" style="text-align: right;"><span>Weighted Average (Average Sub-total * Strategic %)</span></td>
                <td>
                    <?php 
                    $weightedAverageStrategic = (count($strategic_tasks) > 0) ? ($totalStrategicAverage / count($strategic_tasks)) * (htmlspecialchars($strategic) / 100) : 0;
                    if ($semester['users_final_approval'] == 1) {
                        echo number_format($weightedAverageStrategic, 2); 
                    }
                    ?>
                </td>
                <td></td>
            </tr>
                        <td class="section-title" colspan="8" style="padding: 0;">Core Priority (<?php echo htmlspecialchars($core); ?>%)</td>
                    </tr>
                    <?php
            $totalCoreAverage = 0;
            
            // Group tasks by task_name
            $grouped_core_tasks = [];
            foreach ($core_tasks as $task) {
                $key = $task['task_name']; // Use task_name as the grouping key
                if (!isset($grouped_core_tasks[$key])) {
                    $grouped_core_tasks[$key] = [
                        'task_name' => $task['task_name'],
                        'description' => [],
                        'documents_req' => [],
                        'documents_uploaded' => [],
                        'quality' => [],
                        'efficiency' => [],
                        'timeliness' => [],
                        'average' => [],
                    ];
                }
                // Add values for each task in the group
                $grouped_core_tasks[$key]['description'][] = $task['description'];
                $grouped_core_tasks[$key]['documents_req'][] = $task['documents_req'];
                $grouped_core_tasks[$key]['documents_uploaded'][] = $task['documents_uploaded'];
                $grouped_core_tasks[$key]['quality'][] = $task['quality'];
                $grouped_core_tasks[$key]['efficiency'][] = $task['efficiency'];
                $grouped_core_tasks[$key]['timeliness'][] = $task['timeliness'];
                $grouped_core_tasks[$key]['average'][] = $task['average'];
                $totalCoreAverage += $task['average'];
            }
            
            // Render the grouped core tasks
            foreach ($grouped_core_tasks as $group) {
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
                                <?php
                                $task_name = stripslashes($group['task_name']);
                                $task_name = str_replace('<break(+)line>', '<br>', $task_name);
                                echo $task_name;
                                ?>
                            </td>
                        <?php endif; ?>
            
                        <td style="width: 33%; vertical-align: top; text-align: left; padding-left: 10px; word-wrap: break-word; white-space: normal; <?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                            <?php
                            $description = stripslashes($group['description'][$i]);
                            $description = str_replace('<break(+)line>', '<br>', $description);
                            echo $description;
                            ?>
                        </td>
                        <td style="width: 33%; vertical-align: top; text-align: left; padding-left: 10px; word-wrap: break-word; white-space: normal; <?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                            <?php
                            $documents_req = $group['documents_req'][$i];
                            $documents_uploaded = $group['documents_uploaded'][$i];
                            $percentage = $documents_req > 0 ? ($documents_uploaded / $documents_req) * 100 : 0;
                                          // Check if users_final_approval is 1
                                          if ($semester['users_final_approval'] == 1) {
                                            // Calculate the percentage and display it
                                            echo htmlspecialchars(round($percentage, 2)) . '% of the target for ' . str_replace('<break(+)line>', '<br>', $description) . ' has been accomplished.';
                                        } else {
                                            // If users_final_approval is not 1, replace the value with null (or whatever placeholder you want)
                                            echo null; // Or you can use an empty string or any other placeholder
                                        }
                                    ?>
                                </td>
                                <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                                    <?php 
                                    if ($semester['users_final_approval'] == 1) {
                                        echo isset($group['quality'][$i]) ? htmlspecialchars($group['quality'][$i]) : null; 
                                    } else {
                                        echo null; // Or you can leave this empty if you prefer not to display anything
                                    }
                                    ?>
                                </td>
                                <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                                    <?php 
                                    if ($semester['users_final_approval'] == 1) {
                                        echo isset($group['efficiency'][$i]) ? htmlspecialchars($group['efficiency'][$i]) : null; 
                                    } else {
                                        echo null; // Or leave empty
                                    }
                                    ?>
                                </td>
                                <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                                    <?php 
                                    if ($semester['users_final_approval'] == 1) {
                                        echo isset($group['timeliness'][$i]) ? htmlspecialchars($group['timeliness'][$i]) : null; 
                                    } else {
                                        echo null; // Or leave empty
                                    }
                                    ?>
                                </td>
                                <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                                    <?php 
                                    if ($semester['users_final_approval'] == 1) {
                                        echo isset($group['average'][$i]) ? htmlspecialchars($group['average'][$i]) : null; 
                                    } else {
                                        echo null; // Or leave empty
                                    }
                                    ?>
                                </td>
                                <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>"></td> <!-- Empty cell for the last column -->
                            </tr>
                            <?php
                        }
                    }
                    ?>
                    
                    <tr>
                        <td colspan="6" style="text-align: right;"><span>Average Sub-total</span></td>
                        <td>
                            <?php 
                            if ($semester['users_final_approval'] == 1) {
                                echo count($core_tasks) > 0 ? number_format($totalCoreAverage / count($core_tasks), 2) : '0.00';
                            } else {
                                echo null; // Or you can choose to display null or leave it empty
                            }
                            ?>
                        </td>
                        <td></td>
                    </tr>
                    <tr>
                        <td colspan="6" style="text-align: right;"><span>Weighted Average (Average Sub-total * Core %)</span></td>
                        <td>
                            <?php 
                            $weightedAverageCore = (count($core_tasks) > 0) ? ($totalCoreAverage / count($core_tasks)) * (htmlspecialchars($core) / 100) : 0;
                            if ($semester['users_final_approval'] == 1) {
                                echo number_format($weightedAverageCore, 2); 
                            }
                            ?>
                        </td>
                        <td></td>
                    </tr>
            
                    <tr>
                        <td class="section-title" colspan="8" style="padding: 0;">Support Priority (<?php echo htmlspecialchars($support); ?>%)</td>
                    </tr>
                    <?php
            $totalSupportAverage = 0;
            
            // Group tasks by task_name
            $grouped_support_tasks = [];
            foreach ($support_tasks as $task) {
                $key = $task['task_name']; // Use task_name as the grouping key
                if (!isset($grouped_support_tasks[$key])) {
                    $grouped_support_tasks[$key] = [
                        'task_name' => $task['task_name'],
                        'description' => [],
                        'documents_req' => [],
                        'documents_uploaded' => [],
                        'quality' => [],
                        'efficiency' => [],
                        'timeliness' => [],
                        'average' => [],
                    ];
                }
                // Add values for each task in the group
                $grouped_support_tasks[$key]['description'][] = $task['description'];
                $grouped_support_tasks[$key]['documents_req'][] = $task['documents_req'];
                $grouped_support_tasks[$key]['documents_uploaded'][] = $task['documents_uploaded'];
                $grouped_support_tasks[$key]['quality'][] = $task['quality'];
                $grouped_support_tasks[$key]['efficiency'][] = $task['efficiency'];
                $grouped_support_tasks[$key]['timeliness'][] = $task['timeliness'];
                $grouped_support_tasks[$key]['average'][] = $task['average'];
                $totalSupportAverage += $task['average'];
            }
            
            // Render the grouped support tasks
            foreach ($grouped_support_tasks as $group) {
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
                                <?php
                                $task_name = stripslashes($group['task_name']);
                                $task_name = str_replace('<break(+)line>', '<br>', $task_name);
                                echo $task_name;
                                ?>
                            </td>
                        <?php endif; ?>
            
                        <td style="width: 33%; vertical-align: top; text-align: left; padding-left: 10px; word-wrap: break-word; white-space: normal; <?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                            <?php
                            $description = stripslashes($group['description'][$i]);
                            $description = str_replace('<break(+)line>', '<br>', $description);
                            echo $description;
                            ?>
                        </td>
                        <td style="width: 33%; vertical-align: top; text-align: left; padding-left: 10px; word-wrap: break-word; white-space: normal; <?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                            <?php
                            $documents_req = $group['documents_req'][$i];
                            $documents_uploaded = $group['documents_uploaded'][$i];
                            $percentage = $documents_req > 0 ? ($documents_uploaded / $documents_req) * 100 : 0;
                                                         // Check if users_final_approval is 1
                                                         if ($semester['users_final_approval'] == 1) {
                                                            // Calculate the percentage and display it
                                                            echo htmlspecialchars(round($percentage, 2)) . '% of the target for ' . str_replace('<break(+)line>', '<br>', $description) . ' has been accomplished.';
                                                        } else {
                                                            // If users_final_approval is not 1, replace the value with null (or whatever placeholder you want)
                                                            echo null; // Or you can use an empty string or any other placeholder
                                                        }
                                                    ?>
                                                </td>
                                                <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                                                    <?php 
                                                    if ($semester['users_final_approval'] == 1) {
                                                        echo isset($group['quality'][$i]) ? htmlspecialchars($group['quality'][$i]) : null; 
                                                    } else {
                                                        echo null; // Or you can leave this empty if you prefer not to display anything
                                                    }
                                                    ?>
                                                </td>
                                                <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                                                    <?php 
                                                    if ($semester['users_final_approval'] == 1) {
                                                        echo isset($group['efficiency'][$i]) ? htmlspecialchars($group['efficiency'][$i]) : null; 
                                                    } else {
                                                        echo null; // Or leave empty
                                                    }
                                                    ?>
                                                </td>
                                                <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                                                    <?php 
                                                    if ($semester['users_final_approval'] == 1) {
                                                        echo isset($group['timeliness'][$i]) ? htmlspecialchars($group['timeliness'][$i]) : null; 
                                                    } else {
                                                        echo null; // Or leave empty
                                                    }
                                                    ?>
                                                </td>
                                                <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>">
                                                    <?php 
                                                    if ($semester['users_final_approval'] == 1) {
                                                        echo isset($group['average'][$i]) ? htmlspecialchars($group['average'][$i]) : null; 
                                                    } else {
                                                        echo null; // Or leave empty
                                                    }
                                                    ?>
                                                </td>
                                                <td style="<?php echo $topBorderStyle . ' ' . $bottomBorderStyle; ?>"></td> <!-- Empty cell for the last column -->
                                            </tr>
                                            <?php
                                        }
                                    }
                                    ?>
                                    
                                    <tr>
                                        <td colspan="6" style="text-align: right;"><span>Average Sub-total</span></td>
                                        <td>
                                            <?php 
                                            if ($semester['users_final_approval'] == 1) {
                                                echo count($support_tasks) > 0 ? number_format($totalSupportAverage / count($support_tasks), 2) : '0.00';
                                            } else {
                                                echo null; // Or you can choose to display null or leave it empty
                                            }
                                            ?>
                                        </td>
                                        <td></td>
                                    </tr>
                                    <tr>
                        <td colspan="6" style="text-align: right;"><span>Weighted Average (Average Sub-total * Support %)</span></td>
                        <td>
                            <?php 
                            $weightedAverageSupport = (count($support_tasks) > 0) ? ($totalSupportAverage / count($support_tasks)) * (htmlspecialchars($support) / 100) : 0;
                            if ($semester['users_final_approval'] == 1) {
                                echo number_format($weightedAverageSupport, 2); 
                            }
                            ?>
                        </td>
                        <td></td>
                    </tr>
            
                    <tr>
                        <td colspan="6" style="text-align: right;"><span>Final Average Rating (Strategic % + Core % + Support %)</span></td>
                        <td>
                        <?php 
                            $finalAverage = $weightedAverageStrategic + $weightedAverageCore + $weightedAverageSupport;
                            if ($semester['users_final_approval'] == 1) {
                                echo number_format($finalAverage, 2); 
                            } else {
                                echo null;
                            }
                            ?>
                        </td>
                        <td></td>
                    </tr>
                    <tr>
                        <td colspan="6" style="text-align: right;"><span>Final Adjectival Rating (O, VS, S, US, OR P)</span></td>
                        <td>
                            <?php 
                            $finalRating = '';
                            if ($finalAverage >= 4.20) {
                                $finalRating = 'O'; // Outstanding
                            } elseif ($finalAverage >= 3.40) {
                                $finalRating = 'VS'; // Very Satisfactory
                            } elseif ($finalAverage >= 2.60) {
                                $finalRating = 'S'; // Satisfactory
                            } elseif ($finalAverage >= 1.80) {
                                $finalRating = 'US'; // Unsatisfactory
                            } else {
                                $finalRating = 'P'; // Poor
                            }
                            if ($semester['users_final_approval'] == 1) {
                                echo $finalRating; 
                            }
                            ?>
            </td>
            <td></td>
        </tr>
            <td colspan="8" style="text-align: left; vertical-align: top; padding: 0; height: 100px;">
                <p style="margin: 0 0 0 20px; font-size: 12px;"><span>Comments and Recommendations for Development Purposes :</span></p>
                <p style="margin: 0 0 0 20px; font-size: 12px;">(Includes behavioral competencies)</p>
            </td>
    </tbody>
</table>

        <div class="review-table-container">
                <table class="review-table">
                    <tr>
                        <td colspan="2" style="text-align: center; vertical-align: top; padding: 0; height:10px;">Discussed with</td>
                        <td colspan="4" style="text-align: center; vertical-align: top; padding: 0; height: 10px;">Assessed by</td>
                        <td colspan="2" style="text-align: center; vertical-align: top; padding: 0; height: 10px;">Final Rating by</td>
                    </tr>
                    
                    <tr>
                    <td class="for-signature" style="position: relative; padding: 0; height: 140px; width: 190px; overflow: hidden; border-bottom: none;">
                        <?php if ($signature_final): ?>
                            <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; overflow: hidden;">
                                <img src="data:image/png;base64,<?php echo base64_encode($signature_final); ?>" alt="Signature" style="position: absolute; top: 50%; left: 50%; width: auto; height: auto; max-width: 100%; max-height: 100%; transform: translate(-50%, -50%); border: none; background-color: transparent;">
                            </div>
                        <?php else: ?>
                            <p style="text-align: center;"></p>
                        <?php endif; ?>
                    </td>
                    <td rowspan="3" style="padding: 0; margin: 0; vertical-align: top; text-align: center; width: 100px;"> <!-- Adjust width as needed -->
                        <p style="text-align: center; margin: 0;">Date</p>
                        <div style="text-align: center; margin-top: 50%;">
                            <?php 
                            if (!empty($semester['dean_final_approval_created_at'])) {
                                echo htmlspecialchars((new DateTime($semester['dean_final_approval_created_at']))->format('m/d/Y'));
                            } else {
                                echo '&nbsp;'; // Use a non-breaking space to maintain width
                            }
                            ?>
                        </div>
                    </td>
                    <td class="for-signature" style="position: relative; padding: 0; height: 140px; width: 190px; overflow: hidden; border-bottom: none; vertical-align: top; white-space: normal;">
                        <p style="font-size: 12px; text-align: left; margin: 2px 0; padding: 0; letter-spacing: -0.5px; overflow-wrap: break-word;">
                            I certify that I discussed my assessment of the performance with the employee
                        </p>
                        <div class="for-signature" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; overflow: hidden; z-index: 1;">
                        <?php 
                        if ($semester['final_approval_vpaa'] == 1 && $signature_final_data_vpaaqa): ?>
                            <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; overflow: hidden;">
                                <img src="data:image/png;base64,<?php echo base64_encode($signature_final_data_vpaaqa); ?>" alt="Signature" style="position: absolute; top: 50%; left: 50%; width: auto; height: auto; max-width: 100%; max-height: 100%; transform: translate(-50%, -50%); border: none; background-color: transparent;">
                            </div>
                        <?php elseif ($semester['final_approval_vpaa'] == null): ?>
                            <p class="no-signature" style="text-align: center; margin: 0; padding: 0;"></p>
                        <?php endif; ?>
                        </div>
                    </td>
                    <td rowspan="3" style="padding: 0; margin: 0; vertical-align: top; text-align: center; width: 100px;"> <!-- Adjust width as needed -->
                        <p style="text-align: center; margin: 0;">Date</p>
                        <div style="text-align: center; margin-top: 50%;">
                            <?php 
                            // Check if press_first_created_at is not null or empty
                            if (!empty($semester['vp_final_created_at'])) {
                                // Create a DateTime object and format it
                                echo htmlspecialchars((new DateTime($semester['vp_final_created_at']))->format('m/d/Y'));
                            } else {
                                echo '&nbsp;'; // Use a non-breaking space to maintain width
                            }
                            ?>
                        </div>
                    </td>
                    <td class="for-signature" style="position: relative; padding: 0; height: 140px; width: 190px; overflow: hidden; border-bottom: none; vertical-align: top;">
                        <?php 
                        if ($semester['final_approval_vpaa'] == 1 && $signature_final_data_vpaaqa): ?>
                            <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; overflow: hidden;">
                                <img src="data:image/png;base64,<?php echo base64_encode($signature_final_data_vpaaqa); ?>" alt="Signature" style="position: absolute; top: 50%; left: 50%; width: auto; height: auto; max-width: 100%; max-height: 100%; transform: translate(-50%, -50%); border: none; background-color: transparent;">
                            </div>
                        <?php elseif ($semester['final_approval_vpaa'] == null): ?>
                            <p class="no-signature" style="text-align: center; margin: 0; padding: 0;"></p>
                        <?php endif; ?>
                    </td>
                    <td rowspan="3" style="padding: 0; margin: 0; vertical-align: top; text-align: center; width: 100px;"> <!-- Adjust width as needed -->
                        <p style="text-align: center; margin: 0;">Date</p>
                        <div style="text-align: center; margin-top: 50%;">
                            <?php 
                            // Check if press_first_created_at is not null or empty
                            if (!empty($semester['vp_final_created_at'])) {
                                // Create a DateTime object and format it
                                echo htmlspecialchars((new DateTime($semester['vp_final_created_at']))->format('m/d/Y'));
                            } else {
                                echo '&nbsp;'; // Use a non-breaking space to maintain width
                            }
                            ?>
                        </div>
                    </td>
                    <td class="for-signature" style="position: relative; padding: 0; height: 140px; width: 190px; overflow: hidden; border-bottom: none;">
                    <?php 
                            if ($semester['final_approval_press'] == 1 && $signature_finaldata_president): ?>
                                <img src="data:image/png;base64,<?php echo base64_encode($signature_finaldata_president); ?>" alt="Signature" style="position: absolute; top: 50%; left: 50%; width: auto; height: auto; max-width: 100%; max-height: 100%; transform: translate(-50%, -50%); border: none; background-color: transparent;">
                            <?php elseif ($semester['final_approval_press'] == null): ?>
                                <p class="no-signature" style="text-align: center;"></p>
                            <?php endif; ?>
                    </td>
                    <td rowspan="3" style="padding: 0; margin: 0; vertical-align: top; text-align: center; width: 100px;"> <!-- Adjust width as needed -->
                        <p style="text-align: center; margin: 0;">Date</p>
                        <div style="text-align: center; margin-top: 50%;">
                            <?php 
                            // Check if press_first_created_at is not null or empty
                            if (!empty($semester['press_final_created_at'])) {
                                // Create a DateTime object and format it
                                echo htmlspecialchars((new DateTime($semester['press_final_created_at']))->format('m/d/Y'));
                            } else {
                                echo '&nbsp;'; // Use a non-breaking space to maintain width
                            }
                            ?>
                        </div>
                    </td>
                    </tr>
                    <tr>
                        <td style="border-top: none; border-left: 1px solid black; border-right: 1px solid black; padding: 0;">
                        <strong class="highlighted" style="font-size: 12px;"><?php echo (htmlspecialchars($officeHeadName)); ?>
                            </strong>
                        </td>

                        <td style="border-top: none; border-left: 1px solid black; border-right: 1px solid black; padding: 0;">
                            <strong class="highlighted" style="font-size: 12px;"><?php echo (htmlspecialchars($vpaaqa)); ?></strong>
                        </td>

                        <td style="border-top: none; border-left: 1px solid black; border-right: 1px solid black; padding: 0;">
                            <strong class="highlighted" style="font-size: 12px;"><?php echo (htmlspecialchars($vpaaqa)); ?></strong>
                        </td>

                        <td style="border-top: none; border-left: 1px solid black; border-right: 1px solid black; padding: 0;">
                            <strong class="highlighted" style="font-size: 12px;"><?php echo (htmlspecialchars($collegePresident)); ?></strong>
                        </td>
                    </tr>
                    <tr>
                        <td style="border-top: none; border-left: 1px solid black; border-right: 1px solid black; padding: 0;">Ratee</td>
                        <td style="border-top: none; border-left: 1px solid black; border-right: 1px solid black; padding: 0;">Immediate Supervisor</td>
                        <td style="border-top: none; border-left: 1px solid black; border-right: 1px solid black; padding: 0;">VP for Academic Affairs and<br> Quality Assurance</td>
                        <td style="border-top: none; border-left: 1px solid black; border-right: 1px solid black; padding: 0;">College President</td>
                    </tr>
            </table>
        </div>
            <p style="font-style: italic; font-size: 8px;">Legend 1 - Quality 2 - Efficiency 3 - Timeliness 4 - Average</p>
    </div>
    </body>
</html>
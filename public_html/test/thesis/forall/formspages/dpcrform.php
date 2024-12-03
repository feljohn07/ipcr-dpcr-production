<?php
session_start();

include '../../dbconnections/config.php'; // Adjust the path if necessary

// Fetch the firstname, middlename, lastname, and suffix of the VPAAQA and College President
$sql_vpaa = "SELECT firstname, middlename, lastname, suffix, role FROM usersinfo WHERE role IN ('VPAAQA', 'College President')"; 
$result_vpaa = $conn->query($sql_vpaa);

$vpaa_firstname = "";
$vpaa_middlename = "";
$vpaa_lastname = "";
$vpaa_suffix = ""; // New variable for suffix
$collegePresident_firstname = "";
$collegePresident_middlename = "";
$collegePresident_lastname = "";
$collegePresident_suffix = ""; // New variable for suffix

if ($result_vpaa->num_rows > 0) {
    while($row = $result_vpaa->fetch_assoc()) {
        if ($row["role"] === "VPAAQA") {
            $vpaa_firstname = $row["firstname"];
            $vpaa_middlename = $row["middlename"]; // Fetch the middle name
            $vpaa_lastname = $row["lastname"];
            $vpaa_suffix = $row["suffix"]; // Fetch the suffix
        } elseif ($row["role"] === "College President") {
            $collegePresident_firstname = $row["firstname"];
            $collegePresident_middlename = $row["middlename"]; // Fetch the middle name
            $collegePresident_lastname = $row["lastname"];
            $collegePresident_suffix = $row["suffix"]; // Fetch the suffix
        }
    }
} else {
    $vpaa_firstname = "No data available";
    $vpaa_middlename = "No data available"; // Handle missing middle name
    $vpaa_lastname = "No data available";
    $vpaa_suffix = ""; // Handle missing suffix
    $collegePresident_firstname = "No data available";
    $collegePresident_middlename = "No data available"; // Handle missing middle name
    $collegePresident_lastname = "No data available";
    $collegePresident_suffix = ""; // Handle missing suffix
}

// Fetch the values from the rdm table based on the position
$position = $_SESSION['role']; // Assuming the user's role is stored in the session
$sql_rdm = "SELECT support, core, strategic FROM rdm WHERE position = ?";
$stmt = $conn->prepare($sql_rdm);
$stmt->bind_param("s", $position);
$stmt->execute();
$result_rdm = $stmt->get_result();

$support_value = '';
$core_value = '';
$strategic_value = '';

if ($result_rdm->num_rows > 0) {
    $row = $result_rdm->fetch_assoc();
    $support_value = $row['support'];
    $core_value = $row['core'];
    $strategic_value = $row['strategic'];
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
            margin-bottom: 20px; 
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
        <h1>DEPARTMENT PERFORMANCE COMMITMENT AND REVIEW (DPCR)</h1>
        <p>
            I, <strong><?php echo strtoupper($_SESSION['firstname'] . ' ' . $_SESSION['middlename'] . ' ' . $_SESSION['lastname']); ?></strong> of the <strong><?php echo $_SESSION['college']; ?></strong>
            commit to deliver and agree to be rated on the attainment of the following targets 
            in accordance with the indicated measures for the period 
            <strong>______________</strong>.
        </p>
                <div class="signatures">
                    <div class="ratee" style="margin-left: 60%;">
                    <p style="text-align:center;">
                    <span style="text-decoration: underline; line-height: 2; font-weight: bold; text-align: center;">
                        <strong>
                            <?php 
                                echo strtoupper(htmlspecialchars($_SESSION['firstname']) . ' ' . 
                                    htmlspecialchars($_SESSION['middlename']) . ' ' . 
                                    htmlspecialchars($_SESSION['lastname'])) . 
                                    (isset($_SESSION['suffix']) && $_SESSION['suffix'] ? ', ' . htmlspecialchars($_SESSION['suffix']) : ''); 
                            ?>
                        </strong>
                    </span><br>
                    <span style="display: block; text-align: center;">Ratee</span>
                </p>
                <p style="text-align:center;">Date: <span class="underline" style="margin-left: 5px; line-height: 0.8;"><?php echo date('F j, Y'); ?></span></p>
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
                <td><strong class="highlighted"><?php echo strtoupper($vpaa_firstname . ' ' . $vpaa_middlename . ' ' . $vpaa_lastname . ($vpaa_suffix ? ', ' . $vpaa_suffix : '')); ?></strong><br>Immediate Supervisor</td>
                <td></td>
                <td><strong class="highlighted"><?php echo strtoupper($collegePresident_firstname . ' ' . ($collegePresident_middlename ? $collegePresident_middlename . ' ' : '') . $collegePresident_lastname . ($collegePresident_suffix ? ', ' . $collegePresident_suffix : '')); ?></strong><br>College President</td>
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
                <td style="border-top: none; border-left: 1px solid black; border-right: 1px solid black; padding: 0;">
                    <strong class="highlighted">
                        <?php 
                            echo strtoupper(htmlspecialchars($_SESSION['firstname']) . ' ' . 
                                htmlspecialchars($_SESSION['middlename']) . ' ' . 
                                htmlspecialchars($_SESSION['lastname'])) . 
                                (isset($_SESSION['suffix']) && $_SESSION['suffix'] ? ', ' . htmlspecialchars($_SESSION['suffix']) : ''); 
                        ?>
                    </strong>
                </td>
                <td style="border-top: none; border-left: 1px solid black; border-right: 1px solid black; padding: 0;">
                    <strong class="highlighted">
                        <?php 
                            echo strtoupper($vpaa_firstname . ' ' . 
                                $vpaa_middlename . ' ' . 
                                $vpaa_lastname) . 
                                ($vpaa_suffix ? ', ' . $vpaa_suffix : ''); 
                        ?>
                    </strong>
                </td>
                <td style="border-top: none; border-left: 1px solid black; border-right: 1px solid black; padding: 0;">
                    <strong class="highlighted">
                        <?php 
                            echo strtoupper($vpaa_firstname . ' ' . 
                                $vpaa_middlename . ' ' . 
                                $vpaa_lastname) . 
                                ($vpaa_suffix ? ', ' . $vpaa_suffix : ''); 
                        ?>
                    </strong>
                </td>
                <td style="border-top: none; border-left: 1px solid black; border-right: 1px solid black; padding: 0;">
                <strong class="highlighted">
                    <?php 
                        echo strtoupper($collegePresident_firstname . ' ' . 
                            ($collegePresident_middlename ? $collegePresident_middlename . ' ' : '') . 
                            $collegePresident_lastname) . 
                            ($collegePresident_suffix ? ', ' . $collegePresident_suffix : ''); 
                    ?>
                </strong>
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
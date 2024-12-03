<?php
// Database connection
require_once('../../dbconnections/config.php');

if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    die("Connection failed: " . $conn->connect_error);
}

$data = [];

// Handle GET request to fetch data
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $sqlFetch = "SELECT * FROM rdm WHERE position IN ('Office Head', 'Instructor to Assistant Professors', 'Associate Professors to Professors', 'Faculty with Designation')";
    $result = $conn->query($sqlFetch);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            switch ($row['position']) {
                case 'Office Head':
                    $data['strategic_kpi1'] = $row['strategic'];
                    $data['core_kpi1'] = $row['core'];
                    $data['support_kpi1'] = $row['support'];
                    break;
                case 'Instructor to Assistant Professors':
                    $data['strategic_kpi2'] = $row['strategic'];
                    $data['core_kpi2'] = $row['core'];
                    $data['support_kpi2'] = $row['support'];
                    break;
                case 'Associate Professors to Professors':
                    $data['strategic_kpi3'] = $row['strategic'];
                    $data['core_kpi3'] = $row['core'];
                    $data['support_kpi3'] = $row['support'];
                    break;
                case 'Faculty with Designation':
                    $data['strategic_kpi4'] = $row['strategic'];
                    $data['core_kpi4'] = $row['core'];
                    $data['support_kpi4'] = $row['support'];
                    break;
            }
        }
    }
}

// Handle POST request to update data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $positions = [
        1 => 'Office Head',
        2 => 'Instructor to Assistant Professors',
        3 => 'Associate Professors to Professors',
        4 => 'Faculty with Designation'
    ];

    $allUpdatesSuccessful = true;
    $errorMessage = '';

    foreach ($positions as $id => $position) {
        $strategic = isset($_POST["strategic_kpi$id"]) ? $conn->real_escape_string($_POST["strategic_kpi$id"]) : null;
        $core = isset($_POST["core_kpi$id"]) ? $conn->real_escape_string($_POST["core_kpi$id"]) : null;
        $support = isset($_POST["support_kpi$id"]) ? $conn->real_escape_string($_POST["support_kpi$id"]) : null;

        $sql = "UPDATE rdm SET 
                strategic = '$strategic', 
                core = '$core', 
                support = '$support' 
                WHERE position = '$position'";
                
        if (!$conn->query($sql)) {
            $allUpdatesSuccessful = false;
            $errorMessage = "Error updating $position: " . $conn->error;
            error_log($errorMessage); // Log the error to server logs
            break; // Exit the loop if an error occurs
        }
    }

    if ($allUpdatesSuccessful) {
        echo "RDM successfully updated.";
    } else {
        echo "An error occurred while updating the RDM.";
        error_log($errorMessage); // Log the final error message
    }
}

$conn->close();
?>
<?php
// Database connection
require_once('../dbconnections/config.php');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize an array to store the data
$data = [];

// Fetch data from the database
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

echo json_encode($data);

$conn->close();
?>

<?php
// Include the file containing the database connection
include '../../dbconnections/config.php';

// Initialize variables
$collegePresident = $vpaa = $ccisof = $ceitof = $casof = $cteof = $cbaof = $caof = '';

// Check connection
if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Connection failed: ' . $conn->connect_error]);
    exit;
}

// Fetch existing data from the database
$sqlFetch = "SELECT * FROM recommendingapproval";
$result = $conn->query($sqlFetch);
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $collegePresident = $row['collegePresident'];
    $vpaa = $row['vpaa'];
    $ccisof = $row['ccisof'];
    $ceitof = $row['ceitof'];
    $casof = $row['casof'];
    $cteof = $row['cteof'];
    $cbaof = $row['cbaof'];
    $caof = $row['caof'];
}

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $collegePresident = isset($_POST['collegePresident']) ? $_POST['collegePresident'] : '';
    $vpaa = isset($_POST['vpaa']) ? $_POST['vpaa'] : '';
    $ccisof = isset($_POST['ccisof']) ? $_POST['ccisof'] : '';
    $ceitof = isset($_POST['ceitof']) ? $_POST['ceitof'] : '';
    $casof = isset($_POST['casof']) ? $_POST['casof'] : '';
    $cteof = isset($_POST['cteof']) ? $_POST['cteof'] : '';
    $cbaof = isset($_POST['cbaof']) ? $_POST['cbaof'] : '';
    $caof = isset($_POST['caof']) ? $_POST['caof'] : '';

    // Update the data in the database
    $sql = "UPDATE recommendingapproval SET 
            collegePresident = ?, vpaa = ?, ccisof = ?, ceitof = ?, 
            casof = ?, cteof = ?, cbaof = ?, caof = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssssssss', $collegePresident, $vpaa, $ccisof, $ceitof, $casof, $cteof, $cbaof, $caof);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Recommending Approval Record updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error updating record: ' . $stmt->error]);
    }
    $stmt->close();
    $conn->close();
    exit;
}
?>

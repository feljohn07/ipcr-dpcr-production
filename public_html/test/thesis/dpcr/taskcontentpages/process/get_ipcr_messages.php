<?php
// Include database configuration
include '../../../dbconnections/config.php';

// Get idnumber and semester_id from the URL parameters
$idnumber = $_GET['idnumber'];
$semesterId = $_GET['semester_id'];

// Prepare and execute the SQL query
$query = $conn->prepare("SELECT message FROM performance_ipcr_message WHERE idnumber = ? AND semester_id = ?");
$query->bind_param("si", $idnumber, $semesterId);
$query->execute();
$result = $query->get_result();

// Fetch all messages in an array
$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row['message'];
}

// Close the query and connection
$query->close();
$conn->close();

// Return the messages as JSON
header('Content-Type: application/json');
echo json_encode($messages);
?>

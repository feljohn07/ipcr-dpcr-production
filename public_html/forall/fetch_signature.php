<?php
session_start();

include '../dbconnections/config.php';

if (!isset($_SESSION['idnumber'])) {
    die("Session idnumber not set");
}
$idnumber = $_SESSION['idnumber'];

$stmt = $conn->prepare("SELECT data FROM signature WHERE idnumber = ?");
if (!$stmt) {
    die("Preparation failed: " . $conn->error);
}

$stmt->bind_param("s", $idnumber);
$stmt->execute();
$stmt->bind_result($data);
$stmt->fetch();
$stmt->close();
$conn->close();

if ($data) {
    header('Content-Type: image/png');
    echo 'data:image/png;base64,' . base64_encode($data);
} else {
    echo ''; // Return an empty response
}
?>

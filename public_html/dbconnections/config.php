<?php
$servername = "localhost"; 
$username = "u574655838_latestone";
$password = "Asscatipcr.com123"; 
$dbname = "u574655838_latestone"; 


// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>


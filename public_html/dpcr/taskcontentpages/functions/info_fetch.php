<?php 

include '../../../dbconnections/config.php';

// Retrieve logged-in user's college and idnumber
$college = $_SESSION['college'];
$current_user_idnumber = $_SESSION['idnumber']; // Adjust this based on how you store the user's idnumber in session

// Fetch users from the same college excluding the current user
$usersinfo = $conn->prepare("SELECT * FROM usersinfo WHERE college = ? AND idnumber != ?");
$usersinfo->bind_param("ss", $college, $current_user_idnumber);
$usersinfo->execute();
$users_result = $usersinfo->get_result();
$users = $users_result->fetch_all(MYSQLI_ASSOC);
$usersinfo->close();

$conn->close();
?>


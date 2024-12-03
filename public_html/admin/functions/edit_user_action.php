<?php
// Include the database connection file
include '../../dbconnections/config.php'; // Replace with the correct path to your database connection file

// Retrieve form data
$pkid = $_POST['pkid']; // Primary key
$idnumber = $_POST['idnumber']; // Editable ID number
$lastname = $_POST['lastname'];
$firstname = $_POST['firstname'];
$college = $_POST['college'];
$role = $_POST['role'];

// Check if the ID number already exists for the given role
$checkSql = "SELECT * FROM usersinfo WHERE idnumber=? AND Role=? AND pkid != ?";
$checkStmt = $conn->prepare($checkSql);
$checkStmt->bind_param("ssi", $idnumber, $role, $pkid);
$checkStmt->execute();
$result = $checkStmt->get_result();

if ($result->num_rows > 0) {
    // ID number already exists for the given role
    echo "ID number already exists for the User : " . htmlspecialchars($role);
} else {
    // Prepare an update statement
    $sql = "UPDATE usersinfo SET idnumber=?, lastname=?, firstname=?, college=?, Role=? WHERE pkid=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssi", $idnumber, $lastname, $firstname, $college, $role, $pkid); // Bind 'pkid' as an integer

    if ($stmt->execute()) {
        echo "User  updated successfully!";
    } else {
        echo "Error updating user: " . $stmt->error; // Return detailed error message
    }

    $stmt->close();
}

$checkStmt->close();
$conn->close();
?>
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Include the database connection file
    include '../../dbconnections/config.php'; // Ensure this path is correct

    // Retrieve the idnumber from the POST request
    $idnumber = $_POST['idnumber'];

    // Prepare the delete statement
    $stmt = $conn->prepare("DELETE FROM usersinfo WHERE idnumber = ?");
    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }

    // Bind the idnumber parameter and execute the statement
    $stmt->bind_param("s", $idnumber);
    $stmt->execute();

    // Check if the deletion was successful
    if ($stmt->affected_rows > 0) {
        echo "User deleted successfully!";
    } else {
        echo "Error: User not found or could not be deleted.";
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();
}
?>

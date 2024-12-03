<?php
session_start();
include '../dbconnections/db01_users.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Fetch data from form
    $idnumber = isset($_POST['idnumber']) ? htmlspecialchars($_POST['idnumber']) : '';
    $firstname = isset($_POST['firstname']) ? htmlspecialchars($_POST['firstname']) : '';
    $lastname = isset($_POST['lastname']) ? htmlspecialchars($_POST['lastname']) : '';
    $suffix = isset($_POST['suffix']) ? htmlspecialchars($_POST['suffix']) : '';
    $college = isset($_POST['college']) ? htmlspecialchars($_POST['college']) : '';
    $role = isset($_POST['role']) ? htmlspecialchars($_POST['role']) : '';
    $gmail = isset($_POST['gmail']) ? htmlspecialchars($_POST['gmail']) : '';
    $designation = isset($_POST['designation']) ? htmlspecialchars($_POST['designation']) : '';
    $gender = isset($_POST['gender']) ? htmlspecialchars($_POST['gender']) : '';
    $phone = isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '';

    // File upload handling for profile picture
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == UPLOAD_ERR_OK) {
        $picture = file_get_contents($_FILES['profile_picture']['tmp_name']); // Read the uploaded file
        // Update 'picture' column in the database
        $sqlUpdatePicture = "UPDATE usersinfo SET picture=? WHERE idnumber=?";
        if ($stmtPicture = $conn->prepare($sqlUpdatePicture)) {
            $null = NULL; // Use a placeholder for the blob data
            $stmtPicture->bind_param("bs", $null, $idnumber); // Bind the placeholder and idnumber
            $stmtPicture->send_long_data(0, $picture); // Send the actual blob data
            if (!$stmtPicture->execute()) {
                echo "Error updating picture: " . $stmtPicture->error;
                exit();
            }
            $stmtPicture->close();
        } else {
            echo "Error preparing picture update statement: " . $conn->error;
            exit();
        }
    }

    // Update other profile information
    $sqlUpdateInfo = "UPDATE usersinfo SET firstname=?, lastname=?, suffix=?, college=?, Role=?, gmail=?, designation=?, gender=?, phone=? WHERE idnumber=?";
    if ($stmt = $conn->prepare($sqlUpdateInfo)) {
        $stmt->bind_param("ssssssssss", $firstname, $lastname, $suffix, $college, $role, $gmail, $designation, $gender, $phone, $idnumber);
        if ($stmt->execute()) {
            echo "Record updated successfully";
            // Update session variables
            $_SESSION['firstname'] = $firstname;
            $_SESSION['lastname'] = $lastname;
            $_SESSION['suffix'] = $suffix;
            $_SESSION['college'] = $college;
            $_SESSION['role'] = $role;
            $_SESSION['gmail'] = $gmail;
            $_SESSION['designation'] = $designation;
            $_SESSION['gender'] = $gender;
            $_SESSION['phone'] = $phone;
        } else {
            echo "Error updating record: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Error preparing statement: " . $conn->error;
    }
}

$conn->close();
?>

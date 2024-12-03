<?php
require_once('../../dbconnections/config.php');

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect form data
    $idnumber = $_POST['Idnumber'];
    $college = $_POST['College'];
    $role = $_POST['Role'];
    $username = $_POST['Username'];
    $password = $_POST['Password'];

    $original_idnumber = $idnumber; // Save original input for comparison
    
    // Adjust ID number for Office Head role (add parentheses)
    if ($role == 'Office Head') {
        $idnumber = "($idnumber)";
    }

    // Check if the ID number or username already exists
    $check_id_stmt = $conn->prepare("SELECT * FROM usersinfo WHERE Idnumber = ? AND Role = ?");
    $check_id_stmt->bind_param("ss", $idnumber, $role);
    $check_id_stmt->execute();
    $id_result = $check_id_stmt->get_result();

    $check_username_stmt = $conn->prepare("SELECT * FROM usersinfo WHERE Username = ?");
    $check_username_stmt->bind_param("s", $username);
    $check_username_stmt->execute();
    $username_result = $check_username_stmt->get_result();

    if ($id_result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => "Error: ID number $original_idnumber already exists for the role $role."]);
    } elseif ($username_result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Error: Username is already in use.']);
    } else {
        // Check for unique roles
        if ($role == 'College President' || $role == 'VPAAQA') {
            // Allow only one College President or VPAAQA
            $check_role_stmt = $conn->prepare("SELECT * FROM usersinfo WHERE Role = ?");
            $check_role_stmt->bind_param("s", $role);
            $check_role_stmt->execute();
            $role_result = $check_role_stmt->get_result();

            if ($role_result->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => "Error: $role already exists."]);
                $check_role_stmt->close();
                $conn->close();
                exit();
            }
            $check_role_stmt->close();
        } elseif ($role == 'Office Head') {
            // Allow only 1 Office Head per college
            $check_office_head_stmt = $conn->prepare("SELECT * FROM usersinfo WHERE Role = ? AND College = ?");
            $check_office_head_stmt->bind_param("ss", $role, $college);
            $check_office_head_stmt->execute();
            $office_head_result = $check_office_head_stmt->get_result();

            if ($office_head_result->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => "Office Head already exists for $college."]);
                $check_office_head_stmt->close();
                $conn->close();
                exit();
            }
            $check_office_head_stmt->close();
        }

        // Insert the new user
        $stmt = $conn->prepare("INSERT INTO usersinfo (Idnumber, College, Role, Username, Password) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $idnumber, $college, $role, $username, $password);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'New record created successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $stmt->error]);
        }

        $stmt->close();
    }

    $check_id_stmt->close();
    $check_username_stmt->close();
    $conn->close();
}
?>

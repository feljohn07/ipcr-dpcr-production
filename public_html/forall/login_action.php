<?php
session_start();
include '../dbconnections/config.php';

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Fetch username and password from the form
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Sanitize input to prevent SQL injection
    $username = $conn->real_escape_string($username);
    $password = $conn->real_escape_string($password);

    // SQL query to fetch user details based on username and password
    $sql = "SELECT * FROM usersinfo WHERE username='$username' AND password='$password'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        // User exists, fetch user details
        $row = $result->fetch_assoc();
        
        // Debugging: Check fetched role
        error_log('Fetched Role: ' . $row['role']);
        
        // Set session variables
        $_SESSION['username'] = $username; // Assuming $username is set earlier in your code
        $_SESSION['idnumber'] = $row['idnumber'];
        $_SESSION['prefix'] = $row['prefix'];
        $_SESSION['firstname'] = $row['firstname'];
        $_SESSION['lastname'] = $row['lastname'];
        $_SESSION['middlename'] = $row['middlename'];
        $_SESSION['suffix'] = $row['suffix'];
        $_SESSION['college'] = $row['college'];
        $_SESSION['role'] = $row['role'];
        $_SESSION['gmail'] = $row['gmail'];
        $_SESSION['designation'] = $row['designation']; // Assuming 'designation' is fetched from your database query
        $_SESSION['position'] = $row['position'];
        $_SESSION['gender'] = $row['gender']; // Assuming 'gender' is fetched from your database query
        $_SESSION['phone'] = $row['phone']; // Assuming 'phone' is fetched from your database query
    
        // Redirect to different dashboards based on role
        switch (strtolower($row['role'])) {
            case 'admin':
                header("Location: ../admin/admindash.php");
                break;
            case 'ipcr':
                header("Location: ../ipcr/ipcrdash.php");
                break;
            case 'office head':
                header("Location: ../dpcr/dpcrdash.php");
                break;
            case 'college president':
                header("Location: ../president/pressdash.php");
                break;
            case 'vpaaqa':
                header("Location: ../vp/vpdash.php");
                break;
            case 'immediate supervisor':
                header("Location: ../supervisor/supervisor_dash.php");
                break;
            default:
                // Redirect to a generic dashboard or login if role is not recognized
                header("Location: ../index.php");
                break;
        }
        exit();
    } else {
        // Invalid credentials, set error message
        $_SESSION['error'] = "Invalid username or password";
        header("Location: ../index.php");
        exit();
    }
}
?>

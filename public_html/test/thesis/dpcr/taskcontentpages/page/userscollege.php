<?php
session_start();
include '../../../dbconnections/config.php'; // Include your actual database connection file

// Check if user is logged in
if (!isset($_SESSION['idnumber'])) {
    echo "Please log in to view the list of users.";
    exit();
}

// Get logged-in user's college
$user_college = $_SESSION['college'];

// Prepare SQL statement to fetch users from the same college
$sql = "SELECT idnumber, firstname, lastname, suffix, college, role, gmail, picture FROM usersinfo WHERE college = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
}

$stmt->bind_param("s", $user_college);

if (!$stmt->execute()) {
    die("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
}

$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Users List</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;

        }
        table, th, td {
            border: 1px solid black;
            text-align : center ;
        }
        th, td {
            padding: 8px;
            text-align: left;
            vertical-align: middle; /* Center content vertically */
            text-align : center ;
        }
        .profile-img {
            width: 50px; /* Set explicit width */
            height: 50px; /* Set explicit height */
            object-fit: cover; /* Maintain aspect ratio and cover the entire area */
            display: block; /* Ensure image is block-level for centering */
            margin: 0 auto; /* Center horizontally */
        }
    </style>
</head>
<body>
    <h1>List of Users from Your College</h1>
        <?php
        if ($result->num_rows > 0) {
            echo "<table>";
            echo "<tr><th>Profile Picture</th><th>ID Number</th><th>First Name</th><th>Last Name</th><th>Suffix</th><th>College</th><th>Role</th><th>Gmail</th></tr>";
            while ($row = $result->fetch_assoc()) {
                // Initialize the ID number
                $idnumber = htmlspecialchars($row['idnumber'], ENT_QUOTES, 'UTF-8');

                // Check if the role is "Office Head" and format the ID number
                if ($row['role'] === 'Office Head') {
                    // Use regex to extract the ID number from parentheses
                    if (preg_match('/\((.*?)\)/', $idnumber, $matches)) {
                        $idnumber = $matches[1]; // Get the content inside the parentheses
                    }
                }

                echo "<tr>
                        <td><img src='data:image/jpeg;base64," . base64_encode($row['picture']) . "' alt='Profile Picture' class='profile-img'></td>
                        <td>" . $idnumber . "</td>
                        <td>" . htmlspecialchars($row['firstname'], ENT_QUOTES, 'UTF-8') . "</td>
                        <td>" . htmlspecialchars($row['lastname'], ENT_QUOTES, 'UTF-8') . "</td>
                        <td>" . htmlspecialchars($row['suffix'], ENT_QUOTES, 'UTF-8') . "</td>
                        <td>" . htmlspecialchars($row['college'], ENT_QUOTES, 'UTF-8') . "</td>
                        <td>" . htmlspecialchars($row['role'], ENT_QUOTES, 'UTF-8') . "</td>
                        <td>" . htmlspecialchars($row['gmail'], ENT_QUOTES, 'UTF-8') . "</td>
                    </tr>";
            }
            echo "</table>";
        } else {
            echo "No users found from your college.";
        }
    $stmt->close();
    ?>
</body>
</html>

<?php
include 'checklogin.php';
checkLogin();

// Start the session if it's not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Ensure user is logged in
checkLogin();

// Role checking: Ensure only 'office head' can access this page
$role = isset($_SESSION['role']) ? strtolower($_SESSION['role']) : '';
if ($role != 'immediate supervisor') {
    // Redirect to error page if the user is not an 'office head'
    header("Location: ../forall/error_page.php");
    exit();
}

// Logout logic
// Handle logout if requested
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    echo "<script>
        localStorage.removeItem('lastContent');
        window.location.href = '../index.php';
    </script>";
    exit();
  }
  
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supervisor</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        html, body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            height: 100%;
            overflow: hidden; /* Prevent scrolling of the whole page */
            display: flex;
            flex-direction: column;
          }
          
          .header {
            background: linear-gradient(to right, #dfe8a1, #5656c8, #7239a0);
            color: white;
            text-align: center;
            padding: 10px 0;
            font-size: 30px;
          }
          
          .navbar {
            background-color: #5c5c8a;
            overflow: hidden;
            padding-right: 20px; /* Adjust the value as needed */
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
          }
          
          .navbar button {
  font-size: 15px;
  float: left;
  display: block;
  color: white;
  text-align: center;
  padding: 15px 15px;
  text-decoration: none;
  border: none;
  background: none;
  cursor: pointer;
}

.navbar button:hover {
  background-color: #33334d;
}

.navbar img {
  width: 15px;
  height: 15px;
  margin-right: 5px;
}

.navbar form {
  float: right;
  margin-top: 5px;
  margin-right: 10px;
}

.navbar form button {
  background: none;
  border: none;
  color: white;
  cursor: pointer;
  padding: 10px;
  font-size: 13px;
  text-decoration: none;
  display: inline-block;
}

.navbar form button img {
  width: 15px;
  height: 15px;
  margin-right: 5px;
}

.navbar form button:hover {
  background-color: #33334d;
}
        .content {
            background-color: #f4f4f4;
            padding: 20px;
            overflow-y: auto;
            box-shadow: 4px 4px 10px rgba(0, 0, 0, 0.2);
            flex: 1; /* Make content take the remaining space */
            overflow-y: auto; /* Make the content scrollable */
            box-shadow: 4px 4px 10px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>
<body>
<div class="header">Supervisor</div>
<div class="navbar">
    <button onclick="loadContent('../forall/profile.php')" class="nav-link">
        <img src="../iconswhite/profile.svg" alt="Profile icon">
        Profile
    </button>
    <button onclick="loadContent('userswithdesignation.php')" class="nav-link">
        <img src="../iconswhite/signature.svg" alt="Signature Icon">
        Users with Designation
    </button>
    <button onclick="window.open('reports.php')" class="nav-link">
        <img src="../iconswhite/task.svg" alt="Task Icon">
        Reports
    </button>
    <form method="post" id="logoutForm">
        <button type="button" onclick="showLogoutModal()" class="nav-link">
            <img src="../iconswhite/logout.svg" alt="Logout Icon">
            Logout
        </button>
        <input type="hidden" name="logout" value="1">
    </form>
</div>
<div class="content" id="mainContent">
    Main Content
</div>

<?php
include_once '../forall/logoutconfirmation.php';
?>

<script>
        function loadContent(url) {
            $('#mainContent').load(url, function() {
                // Store the URL in localStorage
                localStorage.setItem('lastContent', url);
            });
        }


        // Load the stored content if available
        var lastContent = localStorage.getItem('lastContent');
        console.log("Last content URL:", lastContent); // Debugging line

        if (lastContent) {
            loadContent(lastContent);
        } else {
            // Default content to show if no previous content is stored
            loadContent('userswithdesignation.php');
        }
</script>
</body>
</html>
    
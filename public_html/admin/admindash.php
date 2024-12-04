<?php
// Include the login check function
include 'checklogin.php';

// Start the session if it's not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Ensure user is logged in
checkLogin();

// Role checking: Ensure only 'office head' can access this page
$role = isset($_SESSION['role']) ? strtolower($_SESSION['role']) : '';
if ($role != 'admin') {
    // Redirect to error page if the user is not an 'office head'
    header("Location: ../forall/error_page.php");
    exit();
}

// Handle logout if requested
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    echo "<script>
        localStorage.removeItem('lastContent');
        window.location.href = '../forall/login.php';
    </script>";
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Layout</title>
    <link rel="stylesheet" href="../admin/css/cssall.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="header">Admin Dashboard</div>
    <div class="navbar">
    <button onclick="loadContent('contentloaderpages/registerpage.php')" class="nav-link">
        <img src="../iconswhite/register.svg" alt="Register Icon">
        Register
    </button>

    <button onclick="loadContent('contentloaderpages/recommappage.php')" class="nav-link">
        <img src="../iconswhite/recommending.svg" alt="Recommending Icon">
        Recommending Approval
    </button>

    <button onclick="loadContent('contentloaderpages/rdmpage.php')" class="nav-link">
        <img src="../iconswhite/notification.svg" alt="RDM Icon">
        Role Distribution Matrix
    </button>

    <button onclick="loadContent('contentloaderpages/designation.php')" class="nav-link">
        <img src="../iconswhite/recommending.svg" alt="Recommending Icon">
        Designation
    </button>

    <button onclick="loadContent('contentloaderpages/peoplepage.php')" class="nav-link">
        <img src="../iconswhite/people.svg" alt="People Icon">
        People
    </button>

    <form method="post" id="logoutForm">
        <button type="button" onclick="showLogoutModal()" class="nav-link">
            <img src="../iconswhite/logout.svg" alt="Logout Icon">
            Logout
        </button>
        <input type="hidden" name="logout" value="1">
    </form>
</div>

<div class="content" id="mainContent"></div>

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
            loadContent('contentloaderpages/registerpage.php');
        }
</script>

</body>
</html>

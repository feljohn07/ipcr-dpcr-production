<?php
// Include the database connection file
include '../dbconnections/config.php';

// Include the checkLogin function
include 'checklogin.php';
include 'notification/countnofication.php';

// Start the session if it's not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Ensure user is logged in
checkLogin(); // This will redirect if the user is not logged in

// Role checking: Ensure only 'vpaaqa' can access this page
$role = isset($_SESSION['role']) ? strtolower($_SESSION['role']) : '';
if ($role != 'vpaaqa') {
    // Redirect to error page if the user is not an 'vpaaqa'
    header("Location: ../forall/error_page.php");
    exit();
}

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
    <title>VPAA Dashboard</title>
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
            box-shadow:  4px 4px 10px rgba(0, 0, 0, 0.2);
            flex: 1; /* Make content take the remaining space */
            overflow-y: auto; /* Make the content scrollable */
            box-shadow: 4px 4px 10px rgba(0, 0, 0, 0.2);
        }

        #loadingSpinner {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1000; /* Ensure it's above other content */
            display: none; /* Hide by default */
        }

        #loadingSpinner img {
            width: 50px; /* Adjust to your desired size */
            height: 50px; /* Adjust to your desired size */
            animation: spin 1s linear infinite; /* Apply spinning animation */
        }

        #mainContent {
            position: relative; /* Ensure this div is positioned relative */
        }

        #overlay {
            position: absolute; /* Use absolute positioning to cover the content */
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.5); /* Semi-transparent background */
            backdrop-filter: blur(5px); /* Apply blur effect */
            z-index: 10; /* Ensure it's above the main content but below the loading spinner */
            display: none; /* Hide by default */
        }

        .badge {
            background-color: red; /* Or any color you prefer */
            color: white;
            border-radius: 50%;
            padding: 3px 6px;
            font-size: 0.8em;
            position: absolute; /* Keep it absolute for positioning */
            top: -0px; /* Adjust as needed */
            right: -5px; /* Adjust as needed */
            z-index: 100; /* Ensure it’s above other content */
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.5); /* Add shadow for better visibility */
        }

        .nav-link {
            position: relative; /* For positioning the badge */
            padding: 10px;
            text-decoration: none;
            color: black; /* Adjust color as needed */
        }
    </style>
</head>
<body>
    <div id="loadingSpinner">
        <img src="../pictures/loading.webp" alt="Loading..." />
    </div>

    <?php include '../notiftext/taskupdatenotif.php'; ?>

    <div class="header">VPAA Dashboard</div>
    <div class="navbar">
        <!-- Your navigation buttons -->
        <button onclick="loadContent('../forall/profile.php')" class="nav-link">
            <img src="../iconswhite/profile.svg" alt="Profile icon">
            Profile
        </button>
        <button onclick="loadContent('vptask.php')" class="nav-link">
            <img src="../iconswhite/task.svg" alt="Task Icon">
            Task
        </button>
        <button onclick="loadContent('notification/vpnotify.php')" class="nav-link">
            <img src="../iconswhite/notification.svg" alt="Notification Icon">
            Notification
            <?php if (isset($totalUnreadNotifications) && $totalUnreadNotifications > 0): ?>
                <span class="badge"><?php echo htmlspecialchars($totalUnreadNotifications); ?></span>
            <?php endif; ?>
        </button>
        <button onclick="loadContent('../forall/signature.php')">
            <img src="../iconswhite/signature.svg" alt="Signature Icon">
            Signature
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
        <div id="overlay"></div>
        Main Content
    </div>

    <?php include_once '../forall/logoutconfirmation.php'; ?>

    <script src="notification/mark_notifications_read.js"></script>
    <script>
        let notificationLoaded = false; // Flag to track if notifications were loaded

        function loadContent(url) {
            // If notifications were loaded and the user is trying to load something else, reload the page
            if (notificationLoaded && url !== 'notification/vpnotify.php') {
                localStorage.setItem('lastContent', url); // Store the URL before reloading
                location.reload(); // Reload the page
                return; // Exit the function
            }

            $('#loadingSpinner').show();
            $('#overlay').show();

            $('#mainContent').load(url, function() {
              $('#loadingSpinner').hide();
                $('#overlay').hide();

                localStorage.setItem('lastContent', url); // Store the last loaded content URL

                // Check if the loaded content is the notification content
                if (url === 'notification/vpnotify.php') {
                    notificationLoaded = true; // Set the flag to true
                } else {
                    notificationLoaded = false; // Reset the flag for other content
                }

                // Call the function from notifications.js
                markNotificationsRead(url);
            });
        }

        $(document).ready(function() {
            var lastContent = localStorage.getItem('lastContent');
            console.log("Last content URL:", lastContent);

            if (lastContent) {
                loadContent(lastContent); // Load the last content stored in localStorage
            } else {
                loadContent('../forall/profile.php'); // Default content
            }
        });
    </script>

</body>
</html>
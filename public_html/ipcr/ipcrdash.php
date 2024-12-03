<?php
// Include the database connection file
include '../dbconnections/config.php';

// Include the checkLogin function
include 'checklogin.php';
include 'ipcrnotification/countnotification.php';
// Start the session if it's not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Ensure user is logged in
checkLogin();

// Role checking: Ensure only 'ipcr' can access this page
$role = isset($_SESSION['role']) ? strtolower($_SESSION['role']) : '';
if ($role != 'ipcr') {
    // Redirect to error page if the user is not an 'ipcr'
    header("Location: ../forall/error_page.php");
    exit();
}

// Handle logout if requested
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    echo "<script>
        localStorage.removeItem('lastContent');
        localStorage.removeItem('taskContentURL');  // Clear taskContentURL on logout
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
    <title>Three Div Layout</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="../ipcr/css/allcss.css">
    <style>
/* General styles */
html, body {
  font-family: Arial, sans-serif;
  margin: 0;
  padding: 0;
  height: 100%;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

/* Header styles */
.header {
  background: linear-gradient(to right, #dfe8a1, #5656c8, #7239a0);
  color: white;
  text-align: center;
  padding: 10px 0;
  font-size: 30px;
}

/* Navbar styles */
.navbar {
  background-color: #5c5c8a;
  overflow: hidden;
  padding-right: 20px;
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

/* Content styles */
.content {
  background-color: #f4f4f4;
  padding: 20px;
  overflow-y: auto;
  box-shadow: 4px 4px 10px rgba(0, 0, 0, 0.2);
  flex: 1;
  overflow-y: auto;
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

        <script src="js/navbarActive.js"></script>  
</head>

<body>
<div class="header">IPCR Dash</div>
<div class="navbar">
    <button onclick="loadContent('../forall/profile.php')" class="nav-link">
        <img src="../iconswhite/profile.svg" alt="Profile icon">
            Profile
    </button>
    <button onclick="loadContent('ipcrtaskspages/taskbtnipcr.html')" class="nav-link">
    <img src="../iconswhite/task.svg" alt="Task Icon">
        Task
    </button>

    <button onclick="loadContent('ipcrnotification/ipcrnotify.php')" class="nav-link">
            <img src="../iconswhite/notification.svg" alt="Notification Icon">
            Notification
            <?php if ($totalUnreadNotifications > 0): ?>
                <span class="badge"><?php echo htmlspecialchars($totalUnreadNotifications); ?></span>
            <?php endif; ?>
        </button>

    <button onclick="loadContent('../forall/formspages/ipcrform.php')" class="nav-link">
        <img src="../iconswhite/form.svg" alt="Forms Icon">
            IPCR Forms
    </button>

    <button onclick="loadContent('../forall/signature.php')" class="nav-link">
        <img src="../iconswhite/signature.svg" alt="Signature Icon">
            Signature
    </button>

    <button onclick="loadContent('archive/ipcrarchive.php')" class="nav-link">
        <img src="../iconswhite/archives.svg" alt="Archive Icon">
            Archive
    </button>

   <button onclick="window.open('ipcrtaskspages/ipcr_reports.php')" class="nav-link">
        <img src="../iconswhite/notification.svg" alt="Reports Icon">
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
        </div>
    </div>

    <div id="loadingSpinner">
    <img src="../pictures/loading.webp" alt="Loading..." />
</div>

    <?php
    include_once '../forall/logoutconfirmation.php';
    ?>
<script src="ipcrnotification/mark_notifications_read.js"></script>
    <script>
            let notificationLoaded = false; // Flag to track if notifications were loaded

            function loadContent(url) {
    // If notifications were loaded and the user is trying to load something else, reload the page
    if (notificationLoaded && url !== 'ipcrnotification/ipcrnotify.php') {
        localStorage.setItem('lastContent', url); // Store the URL before reloading
        location.reload(); // Reload the page
        return; // Exit the function
    }

    $('#loadingSpinner').show(); // Show the loading spinner
        $('#overlay').show(); // Show the overlay

        $('#mainContent').load(url, function() {
            $('#loadingSpinner').hide(); // Hide the loading spinner
            $('#overlay').hide(); // Hide the overlay
            // Store the URL in localStorage
            localStorage.setItem('lastContent', url);
            
            // Check if the loaded content is `ipcrnotification/ipcrnotify.php`
            if (url === 'ipcrnotification/ipcrnotify.php') {
                notificationLoaded = true; // Set the flag to true when loading notifications
            } else {
                notificationLoaded = false; // Reset the flag for other content
            }

            // Check if the loaded content is `ipcrtaskpages/taskbtnipcr.html`
            if (url === 'ipcrtaskpages/taskbtnipcr.html') {
                var taskContentURL = localStorage.getItem('taskContentURL');
                if (taskContentURL) {
                    loadTaskContent(taskContentURL);
                } else {
                    loadTaskContent('taskcontentpages/viewsubmittedtask.php');
                }
            }

            // Call the function from notifications.js
            markNotificationsRead(url);
        });
    }

    $(document).ready(function() {
        // Load stored content if available, otherwise load default content
        var lastContent = localStorage.getItem('lastContent');
        
        console.log("Last content URL:", lastContent); // Debugging line

        if (lastContent) {
            // If there's a previously stored URL, load that content
            loadContent(lastContent);
        } else {
            // If no stored URL, load the default content
            loadContent('../forall/profile.php');
        }
    });
</script>

</body>
</html>

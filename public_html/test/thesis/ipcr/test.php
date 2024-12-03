<?php
include '../forall/checklogin.php';
checkLogin();

if (isset($_POST['logout'])) {
  session_unset();
  session_destroy();
  header("Location: ../forall/login.php");
  exit();
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Three Div Layout</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body, html {
            width: 100%;
            height: 100%;
            font-family: Arial, sans-serif;
        }

        .header {
            width: 100%;
            height: 60px;
            background-color: #333;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .main-content {
            display: flex;
            height: calc(100% - 60px); /* Full height minus the header height */
        }

        .sidebar {
            width: 15%;
            background-color: #5c5c8a;
            color: white;
            padding: 20px;
            overflow-y: auto;
            position: relative; /* Needed for absolute positioning */
        }

        .sidebar ul {
            list-style-type: none;
            padding: 0;
        }

        .sidebar li {
            margin-bottom: 10px;
        }

        .sidebar a, .sidebar button {
            text-decoration: none;
            font-size: 16px;
            color: #ffffff;
            display: flex;
            align-items: center;
            border: none;
            background: none;
            cursor: pointer;
            width: 100%;
            text-align: left;
            word-wrap: break-word;
            white-space: normal;
            padding: 10px 0;
        }

        .sidebar a:hover, .sidebar button:hover {
            background-color: #33334d;
        }

        .sidebar img {
            margin-right: 10px;
            width: 24px;
            height: 24px;
        }

        .sidebar form {
            position: absolute;
            bottom: 20px; /* Adjust as needed */
            width: calc(100% - 40px); /* Adjust for sidebar padding */
            display: flex;
            align-items: center;
        }

        .content {
            flex-grow: 1;
            background-color: #f4f4f4;
            padding: 20px;
            overflow-y: auto;
        }

        .modal {
            display: none; 
            position: fixed;
            z-index: 1; 
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgb(0,0,0);
            background-color: rgba(0,0,0,0.4);
            padding-top: 60px;
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="header">
        Welcome IPCR
    </div>
    <div class="main-content">
        <div class="sidebar">
            <ul>
                <li>
                    <a href="javascript:void(0);" onclick="loadContent('../forall/profile.php')">
                        <img src="../iconswhite/profile.svg" alt="Profile icon">
                        Profile
                    </a>
                </li>
                <li>
                    <a href="javascript:void(0);" onclick="loadContent('@')">
                        <img src="../iconswhite/task.svg" alt="Task Icon">
                        Task
                    </a>
                </li>
                <li>
                    <a href="javascript:void(0);" onclick="loadContent('../forall/archives.php')">
                        <img src="../iconswhite/archives.svg" alt="Archive Icon">
                        Archive
                    </a>
                </li>
                <li>
                    <a href="javascript:void(0);" onclick="loadContent('../admin/rdm.php')">
                        <img src="../iconswhite/notification.svg" alt="RDM Icon">
                        Notification
                    </a>
                </li>
                <li>
                    <a href="javascript:void(0);" onclick="loadContent('../admin/people.php')">
                        <img src="../iconswhite/people.svg" alt="Group Icon">
                        Group
                    </a>
                </li>
                <li>
                    <a href="javascript:void(0);" onclick="loadContent('../admin/forms.php')">
                        <img src="../iconswhite/form.svg" alt="Forms Icon">
                        DPCR Forms
                    </a>
                </li>
                <li>
                <a href="../forall/signature.php">
                        <img src="../iconswhite/Signature.svg" alt="Signature Icon">
                        Signature
                    </a>
                </li>
                <li>
                    <a href="javascript:void(0);" onclick="loadContent('../admin/reports.php')">
                        <img src="../iconswhite/notification.svg" alt="Reports Icon">
                        Reports
                    </a>
                </li>
                <li>
                    <form method="post">
                        <button type="submit" name="logout">
                            <img src="../iconswhite/logout.svg" alt="Logout Icon">
                            Logout
                        </button>
                    </form>
                </li>
            </ul>
        </div>
        <div class="content" id="mainContent">
            Main Content
        </div>
    </div>

    <div id="profileModal" class="modal">
    <div class="modal-content">
      <span class="close" onclick="closeProfileModal()">&times;</span>
      <?php include '../forall/profile.php'; ?>
    </div>
  </div>
    
      <script>
        function loadContent(url) {
            var mainContent = document.getElementById('mainContent');
            fetch(url)
                .then(response => response.text())
                .then(data => {
                    mainContent.innerHTML = data;
                })
                .catch(error => {
                    console.error('Error loading content:', error);
                });
        }

        function openProfileModal() {
            document.getElementById("profileModal").style.display = "block";
        }
    
        function closeProfileModal() {
            document.getElementById("profileModal").style.display = "none";
            
        }
      </script>

</body>
</html>

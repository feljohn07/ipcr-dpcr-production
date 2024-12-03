<?php
session_start();
include '../../dbconnections/config.php';

// Retrieve office head idnumber from session
$idnumber = $_SESSION['idnumber']; // Ensure this is correctly set in your login process

if (empty($idnumber) || $idnumber == '0') {
    die("Error: Office head ID is not set properly.");
}

// Retrieve college from session
$college = $_SESSION['college']; // Ensure this is set correctly in your login process

if (empty($college)) {
    die("Error: College is not set properly.");
}

// Fetch semester tasks for the user's college
$semester_stmt = $conn->prepare("SELECT * FROM semester_tasks WHERE college = ? AND status = 'done' ORDER BY created_at DESC");
$semester_stmt->bind_param("s", $college);
$semester_stmt->execute();
$semester_result = $semester_stmt->get_result();
$semesters = $semester_result->fetch_all(MYSQLI_ASSOC);
$semester_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Created Tasks</title>
    <style>
        /* General styles */
        body, h2, p, button, form {
            margin: 0;
            padding: 0;
            border: 0;
            font-family: Arial, sans-serif;
        }

        .container {
            margin: 0;
            padding: 0;
            border: 0;
        }

        h2 {
            margin-bottom: 20px;
            font-size: 24px;
            color: #4A4A4A;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }

        .task-box {
            background-color: #f7f8fa;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-align: left;
            position: relative;
            transition: box-shadow 0.3s;
        }

        .task-box:hover {
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
        }

        .task-box h3 {
            font-size: 15px;
            color: #444;
            text-align: center;
            margin-bottom: 15px;
        }

        .task-details {
            border-top: 1px solid #ccc;
            padding-top: 10px;
            margin-top: 10px;
        }

        .task-details p {
            font-size: 14px;
            color: #555;
            margin-bottom: 8px;
        }

        /* Improved status dot styling */
        .status-dot {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }

        .pending {
            background-color: #FFAA00;
        }

        .approve {
            background-color: #28A745;
        }

        .disapprove {
            background-color: #FF4D4D;
        }

        /* Improved progress bar */
        .progress-bar {
            width: 100%;
            background-color: #f1f1f1;
            border-radius: 5px;
            overflow: hidden;
            margin-top: 10px;
        }

        .progress-bar-fill {
            height: 20px;
            background-color: #4caf50;
            text-align: center;
            line-height: 20px;
            color: white;
            transition: width 0.3s ease-in-out;
        }

        /* Kebab menu styles */
        .kebab-menu {
            position: absolute;
            top: 10px;
            right: 10px;
            cursor: pointer;
            width: 15px;
            height: 15px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .kebab-menu span {
            background-color: #333;
            height: 2px;
            width: 100%;
            border-radius: 1px;
        }

        /* Animated dropdown */
        .dropdown {
            display: none;
            position: absolute;
            top: 35px;
            right: 10px;
            background-color: #fff;
            border: 1px solid #ddd;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            border-radius: 5px;
            z-index: 1;
            transition: opacity 0.3s ease;
        }

        .dropdown button {
            background-color: #fff;
            color: black;
            border: none;
            padding: 10px 15px;
            text-align: left;
            width: 100%;
            cursor: pointer;
        }

        .dropdown button:hover {
            background-color: #e5e5e5;
        }
    </style>

    <script>
        function toggleDropdown(taskId) {
            const dropdown = document.getElementById('dropdown-' + taskId);
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        }

        function openTasksInNewTab(semesterId) {
            var form = document.createElement('form');
            form.method = 'post';
            form.action = 'archive/archive_assigned_tasks.php';
            form.target = '_blank';

            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'semester_id';
            input.value = semesterId;
            form.appendChild(input);

            document.body.appendChild(form);
            form.submit();
        }

        function viewipcrtask(semesterId) {
            var form = document.createElement('form');
            form.method = 'post';
            form.action = 'archive/archive_viewcreated_task.php';
            form.target = '_blank';

            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'semester_id';
            input.value = semesterId;
            form.appendChild(input);

            document.body.appendChild(form);
            form.submit();
        }

        // Close dropdown when clicking outside of it
        document.addEventListener('click', function(event) {
            var dropdowns = document.querySelectorAll('.dropdown');
            dropdowns.forEach(function(dropdown) {
                if (!dropdown.contains(event.target) && !event.target.closest('.kebab-menu')) {
                    dropdown.style.display = 'none';
                }
            });
        });
    </script>
</head>
<body>
    <div class="container">
        <h2>Archive</h2>
        <div class="grid">
            <?php foreach ($semesters as $index => $semester): ?>
                <?php
                // Calculate progress
                $required_docs = (int)$semester['overall_required_documents'];
                $uploaded_docs = (int)$semester['overall_documents_uploaded'];
                $progress = $required_docs > 0 ? ($uploaded_docs / $required_docs) * 100 : 0;
                ?>
                <div class="task-box">
                    <h3><?php echo htmlspecialchars($semester['semester_name']); ?></h3>
                    
                    <!-- Kebab menu (3 small lines) -->
                    <div class="kebab-menu" onclick="toggleDropdown('<?php echo htmlspecialchars($semester['semester_id']); ?>')">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>

                    <!-- Dropdown menu -->
                    <div class="dropdown" id="dropdown-<?php echo htmlspecialchars($semester['semester_id']); ?>">
                        <button onclick="openTasksInNewTab('<?php echo htmlspecialchars($semester['semester_id']); ?>')">View Assigned Tasks</button>
                        <button onclick="viewipcrtask('<?php echo htmlspecialchars($semester['semester_id']); ?>')">My Created Task</button>
                    </div>

                    <div class="task-details">
                        <p><strong>Start Date:</strong> <?php echo date('F/d/Y', strtotime($semester['start_date'])); ?></p>
                        <p><strong>End Date:</strong> <?php echo date('F/d/Y', strtotime($semester['end_date'])); ?></p>
                        <p><strong>College:</strong> <?php echo htmlspecialchars($semester['college']); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

 <!-- Notification Container -->
 <div id="notification" style="display: none; padding: 10px; position: fixed; top: 10px; right: 10px; background-color: #28a745; color: white; z-index: 1000; border-radius: 5px;"></div>

</body>
</html>
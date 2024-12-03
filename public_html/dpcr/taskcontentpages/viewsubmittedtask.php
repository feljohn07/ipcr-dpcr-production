<?php
session_start();
include '../../dbconnections/config.php';

// Retrieve office head idnumber from session
$idnumber = $_SESSION['idnumber']; // Ensure this is correctly set in your login process

if (empty($idnumber) || $idnumber == '0') {
    die("Error: Office head ID is not set properly.");
}

// Fetch semester tasks
$semester_stmt = $conn->prepare("SELECT * FROM semester_tasks WHERE office_head_id = ? AND status = 'undone' ORDER BY created_at DESC");
$semester_stmt->bind_param("s", $idnumber);
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
            background-color: gray;
        }

        .approve {
            background-color: blue;
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

        .mark-done-button {
            margin-top: 10px; /* Adjust this value as needed */
        }


        #semester-done {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        #semester-done-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
        }

        #semester-done-content button {
            background-color: #4CAF50;
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        #semester-done-content button.cancel {
            background-color: #f44336;
        }

        #semester-done-content button:hover {
            background-color: #45a049;
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
            form.action = 'taskcontentpages/view_tasks.php';
            form.target = '_blank';

            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'semester_id';
            input.value = semesterId;
            form.appendChild(input);

            document.body.appendChild(form);
            form.submit();
        }

        function editTasks(semesterId) {
            var form = document.createElement('form');
            form.method = 'post';
            form.action = 'taskcontentpages/page/editable_task.php';
            
            // Create a hidden input to hold the semester_id
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'semester_id';
            input.value = semesterId;
            form.appendChild(input);

            // Append the form to the document body
            document.body.appendChild(form);
            
            // Submit the form
            form.submit();
        }

        function redirectAssignUser(semesterId) {
            var form = document.createElement('form');
            form.method = 'post';
            form.action = 'taskcontentpages/page/assignusers.php';
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

        function viewipcrtask(semesterId) {
            var form = document.createElement('form');
            form.method = 'post';
            form.action = 'taskcontentpages/page/viewipcrtask.php';
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

        function markSemesterDone(semesterId) {
            // Show the modal for marking semester done
            const modal = document.getElementById('semester-done');
            modal.style.display = 'block';

            const confirmButton = document.getElementById('confirm-semester-done');
            confirmButton.onclick = function () {
                // Implement AJAX call to mark semester as done
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'taskcontentpages/process/mark_semester_done.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function () {
                    if (xhr.status === 200) {
                        // Store notification message in localStorage
                        localStorage.setItem('notificationMessage', 'Semester marked as done successfully.');
                        localStorage.setItem('notificationError', 'false'); // No error
                    } else {
                        // Store notification message in localStorage
                        localStorage.setItem('notificationMessage', 'Error marking semester as done.');
                        localStorage.setItem('notificationError', 'true'); // Error occurred
                    }
                    location.reload(); // Reload page to reflect changes
                };
                xhr.send('semester_id=' + semesterId);
            };
        }

        // Check if the page should show the notification
        var notification = document.getElementById('notification');
        if (localStorage.getItem('notificationMessage')) {
            notification.textContent = localStorage.getItem('notificationMessage');
            if (localStorage.getItem('notificationError') === 'true') {
                notification.style.backgroundColor = '#dc3545'; // Red background
            } else {
                notification.style.backgroundColor = '#28a745'; // Green background
            }
            notification.style.color = 'white';
            notification.style.display = 'block'; // Show the notification

            // Hide notification after 3 seconds
            setTimeout(function () {
                notification.style.display = 'none';
                localStorage.removeItem('notificationMessage');
                localStorage.removeItem('notificationError');
            }, 3000);
        }

        function closeModal() {
            const modal = document.getElementById('semester-done');
            modal.style.display = 'none';
        }

    </script>
</head>
<body>
    <div class="container">
        <h2>Created Semester Tasks</h2>
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
                        <button onclick="openTasksInNewTab('<?php echo htmlspecialchars($semester['semester_id']); ?>')">View Tasks</button>
                        <button onclick="editTasks('<?php echo htmlspecialchars($semester['semester_id']); ?>')">Edit Tasks</button>
                        <button onclick="redirectAssignUser('<?php echo htmlspecialchars($semester['semester_id']); ?>')">Assign User</button>
                        <button onclick="viewipcrtask('<?php echo htmlspecialchars($semester['semester_id']); ?>')">View IPCR Task</button>
                        <button class="mark-done-button" onclick="markSemesterDone(<?php echo htmlspecialchars($semester['semester_id']); ?>)">Mark Done</button>
                    </div>

                    <div class="task-details">
                        <p><strong>Start Date:</strong> <?php echo date('F/d/Y', strtotime($semester['start_date'])); ?></p>
                        <p><strong>End Date:</strong> <?php echo date('F/d/Y', strtotime($semester['end_date'])); ?></p>
                        <p><strong>College:</strong> <?php echo htmlspecialchars($semester['college']); ?></p>
                        <p><strong>Dean's First Signature:</strong>
                            <span class="status-dot <?php echo $semester['userapproval'] == 1 ? 'approve' : 'pending'; ?>"></span>
                        </p>
                        <p><strong>First Approval VPAAQA:</strong>
                            <span class="status-dot <?php echo $semester['vpapproval'] === null || $semester['vpapproval'] === '' ? 'pending' : ($semester['vpapproval'] == '0' ? 'disapprove' : 'approve'); ?>"></span>
                        </p>
                        <p><strong>First Approval President:</strong>
                            <span class="status-dot <?php echo $semester['presidentapproval'] === null || $semester['presidentapproval'] === '' ? 'pending' : ($semester['presidentapproval'] == '0' ? 'disapprove' : 'approve'); ?>"></span>
                        </p>
                        <p><strong>Dean's Final Signature:</strong>
                            <span class="status-dot" style="
                                display: inline-block;
                                width: 12px;
                                height: 12px;
                                border-radius: 50%;
                                margin-right: 8px;
                                <?php 
                                // Determine the background color based on the conditions
                                if ($semester['users_final_approval'] == 1) {
                                    echo 'background-color: blue;'; // Blue if users final approval is 1
                                } elseif ($semester['presidentapproval'] == 1) {
                                    echo 'background-color: #1aff1a;'; // Green if president approval is 1
                                } else {
                                    echo 'background-color: #ccc;'; // Gray if neither are 1
                                }
                                ?>
                            "></span>
                        </p>
                        </p>
                        <p><strong>Final Approval VPAAQA:</strong>
                            <span class="status-dot <?php echo $semester['final_approval_vpaa'] == 1 ? 'approve' : 'pending'; ?>"></span>
                        </p>
                        <p><strong>Final Approval President:</strong>
                            <span class="status-dot <?php echo $semester['final_approval_press'] == 1 ? 'approve' : 'pending'; ?>"></span>
                        </p>
                        <p><strong>Progress:</strong></p>
                        <div class="progress-bar">
                            <div class="progress-bar-fill" style="width: <?php echo $progress; ?>%">
                                <?php echo round($progress) . '%'; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div id="semester-done">
        <div id="semester-done-content">
            <h3>Are you sure you want to mark this semester as done?</h3>
            <button id="confirm-semester-done">Yes, Mark Done</button>
            <button class="cancel" onclick="closeModal()">Cancel</button>
        </div>
    </div>
</div>

   <!-- Notification Container -->
   <div id="notification" style="display: none; padding: 10px; position: fixed; top: 10px; right: 10px; background-color: #28a745; color: white; z-index: 1000; border-radius: 5px;"></div>

</body>
</html>

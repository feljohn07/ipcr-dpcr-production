<?php
session_start();
include '../functions/scs_fetch.php';
include '../functions/info_fetch.php';
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <title>View Tasks for Semester <?php echo htmlspecialchars($semester['semester_name']); ?></title>
<style>
    
    
            /* Modal styling */
            .modal {
                display: none; /* Hidden by default */
                position: fixed; /* Stay in place */
                z-index: 1; /* Sit on top */
                left: 0;
                top: 0;
                width: 100%; /* Full width */
                height: 100%; /* Full height */
                overflow: auto; /* Enable scroll if needed */
                background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
            }

            .modal-content {
                background-color: #fefefe;
                margin: 5% auto; /* Centered */
                padding: 20px;
                border: 1px solid #888;
                width: 80%; /* Adjust width as needed */
                max-height: 80%; /* Set a max height */
                display: flex;
                flex-direction: column; /* To stack elements vertically */
            }

            .modal-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .modal-body {
                flex: 1; /* Allow the body to take up remaining space */
                overflow-y: auto; /* Scrollable if content exceeds height */
            }

            .modal-footer {
                display: flex;
                justify-content: flex-end;
                padding-top: 10px;
            }

            .modal-body table {
                border-collapse: separate;
                border-spacing: 0;
                
            }

            .modal-body thead {
                border: 1px solid #888; /* Add border to both header and body cells */
                position: sticky;
                top: 0;
                background-color: #fefefe; /* Match the background color of the modal content */
                z-index: 1; /* Ensure the header stays on top */
            }
            .modal-body th, .modal-body td {
                padding: 8px; /* Add padding for better spacing */
                text-align: center; /* Center align text */
            }

            .modal-body th {
                border: 1px solid #888; /* Add border to both header and body cells */
                border-bottom: 1px solid #888; /* Add a border to separate the header from the body */
            }

            .close {
                color: #aaa;
                font-size: 28px;
                font-weight: bold;
                cursor: pointer;
            }

            .close:hover,
            .close:focus {
                color: black;
                text-decoration: none;
                cursor: pointer;
            }

            /* Table styling inside modal */


            /* Modal image styling */
            .modal-content img {
                width: 50px; /* Adjust as needed */
                height: 50px; /* Adjust as needed */
                object-fit: cover; /* Ensures image covers the cell without distortion */
            }



            .modal-content input[type="checkbox"] {
                margin: 0;
            }

            .modal-content button {
                background-color: #4caf50;
                color: white;
                border: none;
                padding: 10px;
                border-radius: 5px;
                cursor: pointer;
            }

            .modal-content button:hover {
                background-color: #45a049;
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

            /* Modal form styling */
           


            .modal-content input[type="checkbox"] {
                margin: 0;
            }

            .modal-content button {
                background-color: #4caf50;
                color: white;
                border: none;
                padding: 10px;
                border-radius: 5px;
                cursor: pointer;
                margin-top: 10px;
            }

            .modal-content button:hover {
                background-color: #45a049;
            }

            /* Status column styling */
            /* CSS for status columns */
            /* Ensure general table styling */
            .tabledata th {
                text-align : center ;
            }

         

            .status-container table {
                width: 100%;
                border-collapse: collapse;
                
            }

            .status-container th {
                text-align : center ;    
            }

            .status-container .pending {
                background-color: orange;
                color: white;
            }

            .status-container .approved {
                background-color: rgb(13, 232, 130);
                color: white;
            }

            .status-container .declined {
                background-color: red;
                color: white;
            }

            .declined-column {
                max-width: 100px; /* Adjust width as needed */
                word-wrap: break-word; /* Wrap long content */
            }
                

            /* Style each user entry with a border */
            .user-entry {
                display: flex;
                justify-content: space-between;
                align-items: center;
                border: 1px solid #cccccc; /* Border around each user entry */
                padding: 5px;
                margin-bottom: 5px;
                border-radius: 4px;
                background-color: #f9f9f9;
                justify-content: space-between;
            }

            /* Optional: Style for spacing and border color */
            .status-container td {
                padding: 10px;
                vertical-align: top;
            }

            .status-container {
                border: 1px solid #dddddd; /* Optional: Border around the status table */
            }
            .message-modal-content {
                background-color: #fefefe;
                margin: 15% auto; /* Centered */
                padding: 20px;
                border: 1px solid #888;
                width: 80%; /* Adjust width as needed */
            }



            
            .message-icon-btn {
                background: none;
                border: none;
                cursor: pointer;
                color: #007bff; /* Change the color as needed */
                margin-left: auto; /* Push the button to the far right */
            }

            .message-icon-btn:hover {
                color: #0056b3; /* Hover color */
            }

            .message-icon-btn i {
                font-size: 1.2em;
            }

            #viewAssigned UsersModal {
                display: none; /* Hidden by default */
                position: fixed; /* Stay in place */
                z-index: 1; /* Sit on top */
                left: 0;
                top: 0;
                width: 100%; /* Full width */
                height: 100%; /* Full height */
                overflow: auto; /* Enable scroll if needed */
                background-color: rgb(0, 0, 0); /* Fallback color */
                background-color: rgba(0, 0, 0, 0.4); /* Black w/ opacity */
            }       

            #viewAssignedUsersModal .modal-content {
                background-color: #fefefe;
                margin: 15% auto; /* 15% from the top and centered */
                padding: 20px;
                border: 1px solid #888;
                width: 80%; /* Could be more or less, depending on screen size */
            }

            #viewAssignedUsersModal .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            }

            #viewAssignedUsersModal .close:hover,
            #viewAssignedUsersModal .close:focus {
            color: #000;
            text-decoration: none;
            cursor: pointer;
            }


</style>

<script>
        function closeTab() {
            window.close(); // Attempt to close the current tab
        }
    </script>
    <script>
    function scrollToSection(sectionId) {
        const section = document.getElementById(sectionId);
        if (section) {
            section.scrollIntoView({ behavior: 'smooth' });
        }
    }
</script>
</head>
<body>

    <div class="header">
        <style>
            .header {
                margin-top: 60px; /* Adjust this value based on your navigation bar's height */
                padding: 20px; /* Optional: Add padding for spacing */
                background-color: #f8f9fa; /* Same as navigation background for continuity */
            }
        </style>
        <h2>Semester Tasks: <?php echo htmlspecialchars($semester['semester_name']); ?></h2>
        <p><strong>Start Date:</strong> <?php echo date('F/d/Y', strtotime($semester['start_date'])); ?></p>
        <p><strong>End Date:</strong> <?php echo date('F/d/Y', strtotime($semester['end_date'])); ?></p>
    </div>
        
<div class="navigation">
    <style>
        /* Style for the navigation container */
        .navigation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            background-color: rgba(255, 255, 255, 0.8); /* Transparent background */
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            padding: 10px 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2); /* Slight shadow for depth */
            backdrop-filter: blur(10px); /* Adds a blur effect for a modern look */
        }

        /* Style for the Close button */
        .close-btn {
            padding: 8px 12px;
            background-color: #dc3545;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
            transition: background-color 0.3s ease; /* Smooth hover effect */
        }

        .close-btn:hover {
            background-color: #c82333; /* Darken color on hover */
        }

        /* Style for the navigation buttons */
        .nav-buttons button {
            margin-right: 10px;
            padding: 8px 12px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
            transition: background-color 0.3s ease, transform 0.3s ease; /* Smooth transitions */
        }

        .nav-buttons button:hover {
            background-color: #0056b3;
            transform: scale(1.05); /* Slightly enlarge button on hover */
        }

        /* Ensure the nav-buttons section is on the left */
        .nav-buttons {
            display: flex;
            align-items: center;
        }
    </style>

    <button class="close-btn" onclick="closeTab()">Close This Tab</button>
    <div class="nav-buttons">
        <button onclick="scrollToSection('strategicTasks')">Strategic Tasks</button>
        <button onclick="scrollToSection('coreTasks')">Core Tasks</button>
        <button onclick="scrollToSection('supportTasks')">Support Tasks</button>
    </div>
</div>


<!-- Strategic Tasks -->
<div class="tabledata">
    <h3 id="strategicTasks" style="background-color: #6c757d; color: white; padding: 10px 15px; border-radius: 5px; margin: 10px 0; font-size: 1.5em; font-weight: bold; text-align: left; transition: background-color 0.3s ease;">
        Strategic:
    </h3>
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
        <thead>
            <tr style="background-color: #f2f2f2;">
                <th style="padding: 8px; border: 1px solid #ddd;">Task Name</th>
                <th style="padding: 8px; border: 1px solid #ddd;">Description</th>
                <th style="padding: 8px; border: 1px solid #ddd;">Target</th>
                <th style="padding: 8px; border: 1px solid #ddd;">Due date</th>
                <th style="padding: 8px; border: 1px solid #ddd;">Status</th>
                <th style="width: 100px; padding: 8px; border: 1px solid #ddd;">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($strategic_tasks as $task): ?>
                <tr>
                    <td style="padding: 8px; border: 1px solid #ddd;">
                        <?php 
                            // First remove any escape characters using stripslashes()
                            $task_name = stripslashes($task['task_name']);
                            
                            // Replace <breakline> with <br> after removing slashes
                            $task_name = str_replace('<break(+)line>', '<br>', $task_name);
                            
                            // Display the task name after replacing <breakline> with <br>
                            echo $task_name; 
                        ?>
                    </td>

                    <td class="description-cell" style="padding: 8px; border: 1px solid #ddd;">
                        <?php 
                            // First remove any escape characters using stripslashes()
                            $description = stripslashes($task['description']);
                            
                            // Replace <breakline> with <br> after removing slashes
                            $description = str_replace('<break(+)line>', '<br>', $description);
                            
                            // Display the description after replacing <breakline> with <br>
                            echo $description; 
                        ?>
                    </td>
                    <td style="padding: 8px; border: 1px solid #ddd;"><?php echo htmlspecialchars($task['documents_req']); ?></td>
                    <td style="padding: 8px; border: 1px solid #ddd;"><?php echo htmlspecialchars(date('M/d/Y', strtotime($task['due_date']))); ?></td>
                    <td class="status-container" style="padding: 8px; border: 1px solid #ddd;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr>
                                    <th style="padding: 5px; border: 1px solid #ddd; background-color: #ffeeba;">Pending</th>
                                    <th style="padding: 5px; border: 1px solid #ddd; background-color: #c3e6cb;">Approved</th>
                                    <th style="padding: 5px; border: 1px solid #ddd; background-color: #f5c6cb;">Declined</th>
                                </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td style="padding: 5px; border: 1px solid #ddd;">
                                    <div style="max-height: 150px; overflow-y: auto;"> <!-- Set max-height and overflow -->
                                        <?php 
                                            $pending_users = explode(',', $task['pending_users']);
                                            foreach ($pending_users as $user) {
                                                echo '<div style="white-space: normal; overflow: visible; text-overflow: clip; display: block;">' . htmlspecialchars($user) . '</div>';
                                            }
                                        ?>
                                    </div>
                                </td>
                                <td style="padding: 5px; border: 1px solid #ddd;">
                                    <div style="max-height: 150px; overflow-y: auto;"> <!-- Set max-height and overflow -->
                                        <?php 
                                            $approved_users = explode(',', $task['approved_users'] ?? '');
                                            foreach ($approved_users as $user) {
                                                echo '<div style="white-space: normal; overflow: visible; text-overflow: clip; display: block;">' . htmlspecialchars($user) . '</div>';
                                            }
                                        ?>
                                    </div>
                                </td>
                                <td class="declined-column" style="padding: 5px; border: 1px solid #ddd;">
                                    <div style="max-height: 150px; overflow-y: auto;"> <!-- Set max-height and overflow -->
                                        <?php 
                                            $declined_users = !empty($task['declined_users']) ? explode(',', $task['declined_users']) : [];
                                            foreach ($declined_users as $user) {
                                                if (strpos($user, ': ') !== false) {
                                                    list($user_name, $message) = explode(': ', $user);
                                                    echo '<div style="white-space: nowrap; display: block;">' . htmlspecialchars($user_name) . ' 
                                                    <button class="message-icon-btn" onclick="openMessageModal(\'' . htmlspecialchars($message) . '\')">
                                                        <i class="fas fa-envelope"></i>
                                                    </button>
                                                </div>';
                                                } else {
                                                    // If the user doesn't have a message associated, just display the name
                                                    echo '<div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 150px; display: block;">' . htmlspecialchars($user) . '</div>';
                                                }
                                            }
                                        ?>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                        </table>
                    </td>
                    <td style="text-align: center; padding: 8px; border: 1px solid #ddd;"> 
                    <button class="icon-button" id="taskButton-<?php echo htmlspecialchars($task['task_id']); ?>" 
                        onclick="handleButtonClick(
                            '<?php echo htmlspecialchars($task['task_id']); ?>', 
                            'strategic', 
                            '<?php echo htmlspecialchars($semester_id); ?>', 
                            '<?php echo htmlspecialchars($task['documents_req']); ?>', 
                            '<?php echo htmlspecialchars($task['documents_req_by_user']); ?>', 
                            '<?php echo htmlspecialchars($task['task_name']); ?>', 
                            '<?php echo htmlspecialchars($task['description']); ?>', 
                            '<?php echo htmlspecialchars($task['due_date']); ?>'
                        )" 
                        style="background-color: transparent; border: none; cursor: pointer; position: relative; width: 40px; height: 40px;">
                        <i class="fas fa-user-plus" style="font-size: 20px;"></i>
                        <div class="spinner" style="display: none; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 20px; height: 20px; border: 2px solid rgba(0, 0, 0, 0.1); border-radius: 50%; border-top: 2px solid #3498db; animation: spin 1s linear infinite;"></div>
                    </button>

                    <button class="icon-button" id="viewButton-<?php echo htmlspecialchars($task['task_id']); ?>" 
                            onclick="viewAssignedUsersModal('<?php echo htmlspecialchars($task['task_id']); ?>', 'strategic', '<?php echo htmlspecialchars($semester_id); ?>', '<?php echo htmlspecialchars($task['documents_req']); ?>', '<?php echo htmlspecialchars($task['documents_req_by_user']); ?>', '<?php echo htmlspecialchars($task['task_name']); ?>', '<?php echo htmlspecialchars($task['description']); ?>')" 
                            style="background-color: transparent; border: none; cursor: pointer; position: relative; width: 40px; height: 40px;">
                        <i class="fas fa-eye" style="font-size: 20px;"></i>
                        <div class="spinner" style="display: none; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 20px; height: 20px; border: 2px solid rgba(0, 0, 0, 0.1); border-radius: 50%; border-top: 2px solid #3498db; animation: spin 1s linear infinite;"></div>
                    </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>


<!-- Core Tasks -->
<div class="tabledata">
    <!-- Core Tasks -->
    <h3 id="coreTasks" style="background-color: #6c757d; color: white; padding: 10px 15px; border-radius: 5px; margin: 10px 0; font-size: 1.5em; font-weight: bold; text-align: left; transition: background-color 0.3s ease;">
        Core:
    </h3>
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
        <thead>
            <tr style="background-color: #f2f2f2;">
                <th style="padding: 8px; border: 1px solid #ddd;">Task Name</th>
                <th style="padding: 8px; border: 1px solid #ddd;">Description</th>
                <th style="padding: 8px; border: 1px solid #ddd;">Target</th>
                <th style="padding: 8px; border: 1px solid #ddd;">Due date</th>
                <th style="padding: 8px; border: 1px solid #ddd;">Status</th>
                <th style="width: 100px; padding: 8px; border: 1px solid #ddd;">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($core_tasks as $task): ?>
                <tr>
                <td style="padding: 8px; border: 1px solid #ddd;">
                        <?php 
                            // First remove any escape characters using stripslashes()
                            $task_name = stripslashes($task['task_name']);
                            
                            // Replace <breakline> with <br> after removing slashes
                            $task_name = str_replace('<break(+)line>', '<br>', $task_name);
                            
                            // Display the task name after replacing <breakline> with <br>
                            echo $task_name; 
                        ?>
                    </td>

                    <td class="description-cell" style="padding: 8px; border: 1px solid #ddd;">
                        <?php 
                            // First remove any escape characters using stripslashes()
                            $description = stripslashes($task['description']);
                            
                            // Replace <breakline> with <br> after removing slashes
                            $description = str_replace('<break(+)line>', '<br>', $description);
                            
                            // Display the description after replacing <breakline> with <br>
                            echo $description; 
                        ?>
                    </td>
                    <td style="padding: 8px; border: 1px solid #ddd;"><?php echo htmlspecialchars($task['documents_req']); ?></td>
                    <td style="padding: 8px; border: 1px solid #ddd;"><?php echo htmlspecialchars(date('M/d/Y', strtotime($task['due_date']))); ?></td>
                    <td class="status-container" style="padding: 8px; border: 1px solid #ddd;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr>
                                    <th style="padding: 5px; border: 1px solid #ddd; background-color: #ffeeba;">Pending</th>
                                    <th style="padding: 5px; border: 1px solid #ddd; background-color: #c3e6cb;">Approved</th>
                                    <th style="padding: 5px; border: 1px solid #ddd; background-color: #f5c6cb;">Declined</th>
                                </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td style="padding: 5px; border: 1px solid #ddd;">
                                    <div style="max-height: 150px; overflow-y: auto;"> <!-- Set max-height and overflow -->
                                        <?php 
                                            $pending_users = explode(',', $task['pending_users']);
                                            foreach ($pending_users as $user) {
                                                echo '<div style="white-space: normal; overflow: visible; text-overflow: clip; display: block;">' . htmlspecialchars($user) . '</div>';
                                            }
                                        ?>
                                    </div>
                                </td>
                                <td style="padding: 5px; border: 1px solid #ddd;">
                                    <div style="max-height: 150px; overflow-y: auto;"> <!-- Set max-height and overflow -->
                                        <?php 
                                            $approved_users = explode(',', $task['approved_users'] ?? '');
                                            foreach ($approved_users as $user) {
                                                echo '<div style="white-space: normal; overflow: visible; text-overflow: clip; display: block;">' . htmlspecialchars($user) . '</div>';
                                            }
                                        ?>
                                    </div>
                                </td>
                                <td class="declined-column" style="padding: 5px; border: 1px solid #ddd;">
                                    <div style="max-height: 150px; overflow-y: auto;"> <!-- Set max-height and overflow -->
                                        <?php 
                                            $declined_users = !empty($task['declined_users']) ? explode(',', $task['declined_users']) : [];
                                            foreach ($declined_users as $user) {
                                                if (strpos($user, ': ') !== false) {
                                                    list($user_name, $message) = explode(': ', $user);
                                                    echo '<div style="white-space: nowrap; display: block;">' . htmlspecialchars($user_name) . ' 
                                                    <button class="message-icon-btn" onclick="openMessageModal(\'' . htmlspecialchars($message) . '\')">
                                                        <i class="fas fa-envelope"></i>
                                                    </button>
                                                </div>';
                                                } else {
                                                    // If the user doesn't have a message associated, just display the name
                                                    echo '<div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 150px; display: block;">' . htmlspecialchars($user) . '</div>';
                                                }
                                            }
                                        ?>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                        </table>
                    </td>
                    <td style="text-align: center; padding: 8px; border: 1px solid #ddd;"> 
                    <button class="icon-button" id="taskButton-<?php echo htmlspecialchars($task['task_id']); ?>" 
                        onclick="handleButtonClick(
                            '<?php echo htmlspecialchars($task['task_id']); ?>', 
                            'core', 
                            '<?php echo htmlspecialchars($semester_id); ?>', 
                            '<?php echo htmlspecialchars($task['documents_req']); ?>', 
                            '<?php echo htmlspecialchars($task['documents_req_by_user']); ?>', 
                            '<?php echo htmlspecialchars($task['task_name']); ?>', 
                            '<?php echo htmlspecialchars($task['description']); ?>', 
                            '<?php echo htmlspecialchars($task['due_date']); ?>'
                        )" 
                        style="background-color: transparent; border: none; cursor: pointer; position: relative; width: 40px; height: 40px;">
                        <i class="fas fa-user-plus" style="font-size: 20px;"></i>
                        <div class="spinner" style="display: none; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 20px; height: 20px; border: 2px solid rgba(0, 0, 0, 0.1); border-radius: 50%; border-top: 2px solid #3498db; animation: spin 1s linear infinite;"></div>
                    </button>

                    <button class="icon-button" id="viewButton-<?php echo htmlspecialchars($task['task_id']); ?>" 
                            onclick="viewAssignedUsersModal('<?php echo htmlspecialchars($task['task_id']); ?>', 'core', '<?php echo htmlspecialchars($semester_id); ?>', '<?php echo htmlspecialchars($task['documents_req']); ?>', '<?php echo htmlspecialchars($task['documents_req_by_user']); ?>', '<?php echo htmlspecialchars($task['task_name']); ?>', '<?php echo htmlspecialchars($task['description']); ?>')" 
                            style="background-color: transparent; border: none; cursor: pointer; position: relative; width: 40px; height: 40px;">
                        <i class="fas fa-eye" style="font-size: 20px;"></i>
                        <div class="spinner" style="display: none; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 20px; height: 20px; border: 2px solid rgba(0, 0, 0, 0.1); border-radius: 50%; border-top: 2px solid #3498db; animation: spin 1s linear infinite;"></div>
                    </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

 <!-- Support Tasks -->
<div class="tabledata">
    <h3 id="supportTasks" style="background-color: #6c757d; color: white; padding: 10px 15px; border-radius: 5px; margin: 10px 0; font-size: 1.5em; font-weight: bold; text-align: left; transition: background-color 0.3s ease;">
        Support:
    </h3>
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
        <thead>
            <tr style="background-color: #f2f2f2;">
                <th style="padding: 8px; border: 1px solid #ddd;">Task Name</th>
                <th style="padding: 8px; border: 1px solid #ddd;">Description</th>
                <th style="padding: 8px; border: 1px solid #ddd;">Target</th>
                <th style="padding: 8px; border: 1px solid #ddd;">Due date</th>
                <th style="padding: 8px; border: 1px solid #ddd;">Status</th>
                <th style="width: 100px; padding: 8px; border: 1px solid #ddd;">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($support_tasks as $task): ?>
                <tr>
                <td style="padding: 8px; border: 1px solid #ddd;">
                        <?php 
                            // First remove any escape characters using stripslashes()
                            $task_name = stripslashes($task['task_name']);
                            
                            // Replace <breakline> with <br> after removing slashes
                            $task_name = str_replace('<break(+)line>', '<br>', $task_name);
                            
                            // Display the task name after replacing <breakline> with <br>
                            echo $task_name; 
                        ?>
                    </td>

                    <td class="description-cell" style="padding: 8px; border: 1px solid #ddd;">
                        <?php 
                            // First remove any escape characters using stripslashes()
                            $description = stripslashes($task['description']);
                            
                            // Replace <breakline> with <br> after removing slashes
                            $description = str_replace('<break(+)line>', '<br>', $description);
                            
                            // Display the description after replacing <breakline> with <br>
                            echo $description; 
                        ?>
                    </td>
                    <td style="padding: 8px; border: 1px solid #ddd;"><?php echo htmlspecialchars($task['documents_req']); ?></td>
                    <td style="padding: 8px; border: 1px solid #ddd;"><?php echo htmlspecialchars(date('M/d/Y', strtotime($task['due_date']))); ?></td>
                    <td class="status-container" style="padding: 8px; border: 1px solid #ddd;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr>
                                    <th style="padding: 5px; border: 1px solid #ddd; background-color: #ffeeba;">Pending</th>
                                    <th style="padding: 5px; border: 1px solid #ddd; background-color: #c3e6cb;">Approved</th>
                                    <th style="padding: 5px; border: 1px solid #ddd; background-color: #f5c6cb;">Declined</th>
                                </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td style="padding: 5px; border: 1px solid #ddd;">
                                    <div style="max-height: 150px; overflow-y: auto;"> <!-- Set max-height and overflow -->
                                        <?php 
                                            $pending_users = explode(',', $task['pending_users']);
                                            foreach ($pending_users as $user) {
                                                echo '<div style="white-space: normal; overflow: visible; text-overflow: clip; display: block;">' . htmlspecialchars($user) . '</div>';
                                            }
                                        ?>
                                    </div>
                                </td>
                                <td style="padding: 5px; border: 1px solid #ddd;">
                                    <div style="max-height: 150px; overflow-y: auto;"> <!-- Set max-height and overflow -->
                                        <?php 
                                            $approved_users = explode(',', $task['approved_users'] ?? '');
                                            foreach ($approved_users as $user) {
                                                echo '<div style="white-space: normal; overflow: visible; text-overflow: clip; display: block;">' . htmlspecialchars($user) . '</div>';
                                            }
                                        ?>
                                    </div>
                                </td>
                                <td class="declined-column" style="padding: 5px; border: 1px solid #ddd;">
                                    <div style="max-height: 150px; overflow-y: auto;"> <!-- Set max-height and overflow -->
                                        <?php 
                                            $declined_users = !empty($task['declined_users']) ? explode(',', $task['declined_users']) : [];
                                            foreach ($declined_users as $user) {
                                                if (strpos($user, ': ') !== false) {
                                                    list($user_name, $message) = explode(': ', $user);
                                                    echo '<div style="white-space: nowrap; display: block;">' . htmlspecialchars($user_name) . ' 
                                                    <button class="message-icon-btn" onclick="openMessageModal(\'' . htmlspecialchars($message) . '\')">
                                                        <i class="fas fa-envelope"></i>
                                                    </button>
                                                </div>';
                                                } else {
                                                    // If the user doesn't have a message associated, just display the name
                                                    echo '<div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 150px; display: block;">' . htmlspecialchars($user) . '</div>';
                                                }
                                            }
                                        ?>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                        </table>
                    </td>
                    <td style="text-align: center; padding: 8px; border: 1px solid #ddd;"> 
                    <button class="icon-button" id="taskButton-<?php echo htmlspecialchars($task['task_id']); ?>" 
                        onclick="handleButtonClick(
                            '<?php echo htmlspecialchars($task['task_id']); ?>', 
                            'support', 
                            '<?php echo htmlspecialchars($semester_id); ?>', 
                            '<?php echo htmlspecialchars($task['documents_req']); ?>', 
                            '<?php echo htmlspecialchars($task['documents_req_by_user']); ?>', 
                            '<?php echo htmlspecialchars($task['task_name']); ?>', 
                            '<?php echo htmlspecialchars($task['description']); ?>', 
                            '<?php echo htmlspecialchars($task['due_date']); ?>'
                        )" 
                        style="background-color: transparent; border: none; cursor: pointer; position: relative; width: 40px; height: 40px;">
                        <i class="fas fa-user-plus" style="font-size: 20px;"></i>
                        <div class="spinner" style="display: none; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 20px; height: 20px; border: 2px solid rgba(0, 0, 0, 0.1); border-radius: 50%; border-top: 2px solid #3498db; animation: spin 1s linear infinite;"></div>
                    </button>

                    <button class="icon-button" id="viewButton-<?php echo htmlspecialchars($task['task_id']); ?>" 
                            onclick="viewAssignedUsersModal('<?php echo htmlspecialchars($task['task_id']); ?>', 'support', '<?php echo htmlspecialchars($semester_id); ?>', '<?php echo htmlspecialchars($task['documents_req']); ?>', '<?php echo htmlspecialchars($task['documents_req_by_user']); ?>', '<?php echo htmlspecialchars($task['task_name']); ?>', '<?php echo htmlspecialchars($task['description']); ?>')" 
                            style="background-color: transparent; border: none; cursor: pointer; position: relative; width: 40px; height: 40px;">
                        <i class="fas fa-eye" style="font-size: 20px;"></i>
                        <div class="spinner" style="display: none; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 20px; height: 20px; border: 2px solid rgba(0, 0, 0, 0.1); border-radius: 50%; border-top: 2px solid #3498db; animation: spin 1s linear infinite;"></div>
                    </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>


        <!-- Modal for displaying messages -->
        <div id="messageModal" class="modal">
            <div class="message-modal-content">
                <span class="close" onclick="closeMessageModal()">×</span>
                <h2>Message</h2>
                <p id="messageContent"></p>
            </div>
        </div>
        <!-- Modal -->

<!-- Modal -->
<!-- Modal -->
<!-- Modal -->
<div id="assignModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Assign Owner</h2>
        </div>
        <div class="modal-body">
            <form id="assignForm">
                <input type="hidden" id="task_id" name="task_id" value="<?php echo htmlspecialchars($task['task_id']); ?>">
                <input type="hidden" id="task_type" name="task_type" value="<?php echo htmlspecialchars($task['tasktype']); ?>">
                <input type="hidden" id="semester_id" name="semester_id" value="<?php echo htmlspecialchars($semester['semester_id']); ?>">
                <input type="hidden" id="task_name" name="task_name" value="<?php echo htmlspecialchars($task['task_name']); ?>">
                <input type="hidden" id="due_date" name="due_date" value="<?php echo htmlspecialchars($task['due_date']); ?>">
                <input type="hidden" id="task_description" name="task_description" value="<?php echo htmlspecialchars($task['description']); ?>">
                <input type="hidden" id="end_date" value="<?php echo htmlspecialchars($semester['end_date']); ?>">
                
                
                <table style="border-collapse: collapse; width: 100%;">
                    <thead>
                        <tr style="border-bottom: 2px solid #ddd;">
                            <th style="padding: 8px; border: 1px solid #ddd;">Photo</th>
                            <th style="padding: 8px; border: 1px solid #ddd;">ID Number</th>
                            <th style="padding: 8px; border: 1px solid #ddd;">First Name</th>
                            <th style="padding: 8px; border: 1px solid #ddd;">Last Name</th>
                            <th style="padding: 8px; border: 1px solid #ddd;">Designation</th>
                            <th style="padding: 8px; border: 1px solid #ddd;" id="targetHeader">Target</th> <!-- Dynamic header -->
                            <th style="padding: 8px; border: 1px solid #ddd;">
                                Select (all <label for="selectAllCheckbox" style="margin-left: 5px;">
                                    <input type="checkbox" id="selectAllCheckbox"> 
                                </label>)
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr style="border-bottom: 1px solid #ddd;">
                                <td style="text-align: center; padding: 8px; border: 1px solid #ddd;">
                                    <?php if (!empty($user['picture'])): ?>
                                        <img src="data:image/jpeg;base64,<?php echo base64_encode($user['picture']); ?>" alt="Picture" width="100%" height="100%" style="display: block; margin: 0 auto;">
                                    <?php else: ?>
                                        <span>No Image</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 8px; border: 1px solid #ddd;"><?php echo htmlspecialchars($user['idnumber']); ?></td>
                                <td style="padding: 8px; border: 1px solid #ddd;"><?php echo htmlspecialchars($user['firstname']); ?></td>
                                <td style="padding: 8px; border: 1px solid #ddd;"><?php echo htmlspecialchars($user['lastname']); ?></td>
                                <td style="padding: 8px; border: 1px solid #ddd;"><?php echo htmlspecialchars($user['designation']); ?></td>
                                <td style="padding: 8px; border: 1px solid #ddd;">
                                    <input type="number" name="target_<?php echo htmlspecialchars($user['idnumber']); ?>" min="0" step="1" placeholder="Enter target" class="target-input" data-id="<?php echo htmlspecialchars($user['idnumber']); ?>" disabled>
                                </td>
                                <td style="padding: 8px; border: 1px solid #ddd;">
                                    <input type="checkbox" name="users[]" 
                                        value="<?php echo htmlspecialchars($user['idnumber']); ?>" 
                                        data-firstname="<?php echo htmlspecialchars($user['firstname']); ?>" 
                                        data-lastname="<?php echo htmlspecialchars($user['lastname']); ?>"
                                        class="user-checkbox"
                                        data-target-selector=".target-input[data-id='<?php echo htmlspecialchars($user['idnumber']); ?>']">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" onclick="submitForm()">Assign</button>
        </div>
    </div>
    
    <script>
function handleButtonClick(taskId, taskType, semesterId, documentsReq, documentsReqByUser, taskName, taskDescription, dueDate) {
    const taskButton = document.getElementById('taskButton-' + taskId);
    taskButton.innerHTML = '<img src="../../../pictures/spinner.gif" alt="Loading" style="width:30px;height:30px;">';

    setTimeout(() => {
        openModal(taskId, taskType, semesterId, documentsReq, documentsReqByUser, taskName, taskDescription, dueDate);
        taskButton.innerHTML = '<i class="fas fa-user-plus"></i>';
    }, 1000);
}

function openModal(taskId, taskType, semesterId, documentsReq, documentsReqByUser, taskName, taskDescription, dueDate) {
    document.getElementById('task_id').value = taskId;
    document.getElementById('task_type').value = taskType;
    document.getElementById('semester_id').value = semesterId;
    document.getElementById('task_name').value = taskName;
    document.getElementById('task_description').value = taskDescription;
    document.getElementById('due_date').value = dueDate;

    document.getElementById('targetHeader').textContent = 'Target (' + documentsReq + '/' + documentsReqByUser + ')';
    document.getElementById('targetHeader').dataset.initialValue = parseFloat(documentsReq);
    document.getElementById('targetHeader').dataset.documentsReqByUser = parseFloat(documentsReqByUser);

    document.getElementById('assignModal').style.display = "block";
    updateTargetHeader();
}


        function closeModal() {
                // Reset all user checkboxes
                const checkboxes = document.querySelectorAll('.user-checkbox');
                checkboxes.forEach(function (checkbox) {
                    checkbox.checked = false; // Uncheck the checkbox
                    const targetInput = document.querySelector(checkbox.getAttribute('data-target-selector'));
                    targetInput.setAttribute('disabled', 'true'); // Disable the target input
                    targetInput.value = ''; // Clear the target value
                });

                // Reset the "Select All" checkbox
                const selectAllCheckbox = document.getElementById('selectAllCheckbox');
                selectAllCheckbox.checked = false; // Uncheck the "Select All" checkbox

                // Reset the target header
                document.getElementById('targetHeader').textContent = 'Target (0/0)'; // Reset target header to initial state

                // Hide the modal
                document.getElementById('assignModal').style.display = "none";
            }

            function submitForm() {
    var form = document.getElementById('assignForm');
    var selectedUsers = [];
    var hasInvalidTarget = false; // Flag to track invalid targets

    const checkboxes = form.querySelectorAll('input[name="users[]"]:checked');
    checkboxes.forEach((checkbox) => {
        const targetInput = document.querySelector(checkbox.getAttribute('data-target-selector'));
        const targetValue = parseFloat(targetInput.value); // Get the target value as a number

        // Check if the target value is 0
        if (targetValue === 0) {
            hasInvalidTarget = true; // Set flag if any target is 0
        } else {
            selectedUsers.push({
                idnumber: checkbox.value,
                firstname: checkbox.getAttribute('data-firstname'),
                lastname: checkbox.getAttribute('data-lastname'),
                target: targetValue,
                taskType: document.getElementById('task_type').value,
            });
        }
    });

    // If there is an invalid target, alert the user and return
    if (hasInvalidTarget) {
        alert("You cannot assign a target of 0. Please enter a valid target for all selected users.");
        return; // Stop form submission
    }

    var formData = new FormData();
    formData.append("task_id", document.getElementById('task_id').value);
    formData.append("semester_id", document.getElementById('semester_id').value);
    formData.append("end_date", document.getElementById('end_date').value);
    formData.append("task_name", document.getElementById('task_name').value);
    formData.append("task_description", document.getElementById('task_description').value);
    formData.append("due_date", document.getElementById('due_date').value); // Make sure this line is present
    formData.append("users", JSON.stringify(selectedUsers));

    // Save scroll position before the request
    localStorage.setItem("scrollPos", window.scrollY);

    var xhr = new XMLHttpRequest();
    xhr.open("POST", "../process/assign_user_action.php", true);
    xhr.onload = function () {
        if (xhr.status === 200) {
            localStorage.setItem('notificationMessage', 'Users assigned successfully!');
            localStorage.setItem('notificationError', 'false');
            location.reload(); // Reload the page
        } else {
            // Parse the JSON response
            let response = JSON.parse(xhr.responseText);
            localStorage.setItem('notificationMessage', response.message); // Use the message directly
            localStorage.setItem('notificationError', 'true');
            location.reload(); // Reload the page
        }
    };

    xhr.send(formData);
}

        // Restore scroll position function
        function restoreScrollPosition() {
            const scrollPos = localStorage.getItem("scrollPos");
            if (scrollPos) {
                window.scrollTo(0, parseInt(scrollPos)); // Scroll to the saved position
                localStorage.removeItem("scrollPos"); // Clean up after restoring
            }
        }

        // Restore scroll position after page load
        window.onload = function() {
            restoreScrollPosition();
        };

        // Adding event listener to save scroll position before the page unloads
        window.onbeforeunload = function() {
            localStorage.setItem("scrollPos", window.scrollY);
        };

        document.addEventListener('DOMContentLoaded', function() {
            const notificationMessage = localStorage.getItem('notificationMessage');
            const notificationError = localStorage.getItem('notificationError');

            if (notificationMessage) {
                const notificationDiv = document.getElementById('notification');
                notificationDiv.textContent = notificationMessage; // Set the message

                // Set background color based on success or failure
                if (notificationError === 'true') {
                    notificationDiv.style.backgroundColor = '#dc3545'; // Red for error
                } else {
                    notificationDiv.style.backgroundColor = '#28a745'; // Green for success
                }

                notificationDiv.style.display = 'block'; // Show the notification

                // Hide the notification after 3 seconds
                setTimeout(() => {
                    notificationDiv.style.display = 'none';
                }, 3000);

                // Clear the notification from localStorage
                localStorage.removeItem('notificationMessage');
                localStorage.removeItem('notificationError');
            }
        });

        function updateTargetHeader() {
            const initialValue = parseFloat(document.getElementById('targetHeader').dataset.initialValue);
            const documentsReqByUser = parseFloat(document.getElementById('targetHeader').dataset.documentsReqByUser);
            let totalAssigned = documentsReqByUser;

            document.querySelectorAll('.target-input').forEach(function (input) {
                const value = parseFloat(input.value) || 0;
                totalAssigned += value;
            });

            if (totalAssigned > documentsReqByUser) {
                document.getElementById('targetHeader').textContent = 'Target (' + initialValue + '/' + totalAssigned + ')';
            } else {
                document.getElementById('targetHeader').textContent = 'Target (' + initialValue + '/' + documentsReqByUser  + ')';
            }

            const remainingValue = initialValue - totalAssigned;

            document.querySelectorAll('.target-input').forEach(function (input) {
                const currentValue = parseFloat(input.value) || 0;
                input.max = remainingValue + currentValue;
            });

            // Disable checkboxes if target is reached
            const checkboxes = document.querySelectorAll('.user-checkbox');
            if (totalAssigned >= initialValue) {
                checkboxes.forEach(function (checkbox) {
                    if (!checkbox.checked) {
                        checkbox.disabled = true;
                    }
                });
            } else {
                checkboxes.forEach(function (checkbox) {
                    checkbox.disabled = false;
                });
            }
        }

        // Ensure inputs handle the remaining value correctly
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.user-checkbox').forEach(function (checkbox) {
                checkbox.addEventListener('change', function () {
                    const targetInput = document.querySelector(this.getAttribute('data-target-selector'));

                    if (this.checked) {
                        targetInput.removeAttribute('disabled');
                        targetInput.value = 1; // Set the target to 1 when selected
                    } else {
                        targetInput.setAttribute('disabled', 'true');
                        targetInput.value = ''; 
                    }

                    updateTargetHeader();
                });
            });

            document.querySelectorAll('.target-input').forEach(function (input) {
                input.addEventListener('input', function () {
                    const value = parseFloat(this.value) || 0;
                    const max = parseFloat(this.max) || 0;

                    if (value > max) {
                        this.value = max;
                        alert("Value exceeds the remaining target.");
                    }

                    updateTargetHeader();
                });
            });

                    // Select All functionality
        // Select All functionality
        const selectAllCheckbox = document.getElementById('selectAllCheckbox');
        selectAllCheckbox.addEventListener('change', function () {
            const checkboxes = document.querySelectorAll('.user-checkbox');
            const documentsReq = parseFloat(document.getElementById('targetHeader').dataset.initialValue);
            const documentsReqByUser  = parseFloat(document.getElementById('targetHeader').dataset.documentsReqByUser );
            let totalAssigned = documentsReqByUser ;

            if (this.checked) {
                // Calculate how many users can be selected based on the remaining target
                const remainingTarget = documentsReq - totalAssigned;
                let selectedCount = 0;

                checkboxes.forEach(function (checkbox) {
                    const targetInput = document.querySelector(checkbox.getAttribute('data-target-selector'));

                    if (selectedCount < remainingTarget && !checkbox.checked) {
                        checkbox.checked = true;
                        targetInput.removeAttribute('disabled');
                        targetInput.value = 1; // Set the target to 1 when selected
                        selectedCount++; // Increment the count of selected users
                    }
                });

                // Update the target header to reflect the selection
                updateTargetHeader();
            } else {
                // If "Select All" is unchecked, reset all selections
                checkboxes.forEach(function (checkbox) {
                    checkbox.checked = false;
                    const targetInput = document.querySelector(checkbox.getAttribute('data-target-selector'));
                    targetInput.setAttribute('disabled', 'true');
                    targetInput.value = ''; // Clear target value
                });

                // Update the target header
                updateTargetHeader();
            }
        });
        });
    </script>
</div>




   <!-- Notification Container -->
   <div id="notification" style="display: none; padding: 10px; position: fixed; top: 10px; right: 10px; background-color: #28a745; color: white; z-index: 1000; border-radius: 5px;"></div>

<script>
    function openMessageModal(message) {
        document.getElementById('messageContent').innerText = message;
        document.getElementById('messageModal').style.display = "block";
    }

    function closeMessageModal() {
        document.getElementById('messageModal').style.display = "none";
    }

    // Close message modal when clicking outside of it
    window.onclick = function(event) {
        var modal = document.getElementById('messageModal');
        if (event.target === modal) {
            closeMessageModal();
        }
    }
</script>



<!-- Modal for Viewing Assigned Users -->
<div id="viewAssignedUsersModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeViewAssignedUsersModal()">×</span>        
        <!-- Display Documents Required Information -->
        <div id="documentsInfo" style="
            margin-bottom: 20px; /* Space below the documents info */
            padding: 10px; /* Padding inside the box */
            border: 1px solid #ddd; /* Light border */
            background-color: #f9f9f9; /* Light background */
            border-radius: 5px; /* Rounded corners */
        ">
            <p>Target of Tasks/Total Target of each Users: (<span id="documentsRequired"></span>/<span id="documentsRequiredByUser "></span>)</p>
        </div>

        <div id="viewAssignedUsersContent"></div>
    </div>
</div>


<script>
        function viewAssignedUsersModal(taskId, taskType, semesterId, documentsReq, documentsReqByUser , taskName, taskDescription) {
        // Get the button element
        const viewButton = document.getElementById('viewButton-' + taskId);

        // Change icon to loading spinner
        viewButton.innerHTML = '<img src="../../../pictures/spinner.gif" alt="Loading" style="width:30px;height:30px;">';

        // Create a FormData object to send the data
        var formData = new FormData();
        formData.append('idoftask', taskId);
        formData.append('task_type', taskType);
        formData.append('semester_id', semesterId);
        formData.append('documents_req', documentsReq);
        formData.append('documents_req_by_user', documentsReqByUser );

        // Simulate some delay or real-time loading (you can replace this with actual data fetch)
        setTimeout(() => {
            var url = '../process/get_assigned_users.php'; // Replace with your PHP script URL

            // Send the POST request
            fetch(url, {
                method: 'POST',
                body: formData,
            })
            .then(response => response.text())
            .then(data => {
                // Display the response data inside the modal
                document.getElementById('viewAssignedUsersContent').innerHTML = data;
                document.getElementById('viewAssignedUsersModal').style.display = 'block';

                // Set the documents required information
                document.getElementById('documentsRequired').textContent = documentsReq;
                document.getElementById('documentsRequiredByUser ').textContent = documentsReqByUser ;

                // Change the button content back to the original icon after the data is loaded
                viewButton.innerHTML = '<i class="fas fa-eye"></i>';
            })
            .catch(error => {
                console.error('Error:', error);
                // If there's an error, restore the icon
                viewButton.innerHTML = '<i class="fas fa-eye"></i>';
            });
        }, 1000);  // Adjust this delay based on the time needed for loading data
    }


    function closeViewAssignedUsersModal() {
        var modal = document.getElementById('viewAssignedUsersModal');
        if (modal) {
            modal.style.display = 'none';
            if (operationPerformed) {
                location.reload(); // Reload the page if an operation was performed
            }
            operationPerformed = false; // Reset the flag
        }
    }

    function reloadAndReopenModal(taskId, taskType, semesterId, documentsReq, taskName, taskDescription) {
        location.reload();
        setTimeout(function() {
            viewAssignedUsersModal(taskId, taskType, semesterId, documentsReq, taskName, taskDescription);
        }, 100);
    }

    var operationPerformed = false;

    function deleteUser(userId, taskId, taskType, semesterId, documentsReq, taskName, taskDescription) {
        showDeleteSignatureModal(); // Show the modal

        // Add an event listener to the confirm button
        document.querySelector('.confirm-btn').addEventListener('click', () => {
            fetch("../process/delete_assignment.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: new URLSearchParams({ id: userId })
            })
            .then(response => response.text())
            .then(data => {
                showNotification("User   deleted successfully.");
                document.getElementById("row_" + userId).remove();
                operationPerformed = true; // Set the flag
                closeDeleteSignatureModal(); // Close the delete modal
            })
            .catch(error => {
                console.error("Error:", error);
                showNotification("Error deleting user: " + error.message);
            });
        });
    }

    // Restore scroll position function
function restoreScrollPosition() {
    const scrollPos = localStorage.getItem("scrollPos");
    if (scrollPos) {
        window.scrollTo(0, parseInt(scrollPos)); // Scroll to the saved position
        localStorage.removeItem("scrollPos"); // Clean up after restoring
    }
}

// Restore scroll position after page load
window.onload = function() {
    restoreScrollPosition();
};

// Adding event listener to save scroll position before the page unloads
window.onbeforeunload = function() {
    localStorage.setItem("scrollPos", window.scrollY);
};

    function enableEdit(userId) {
        document.getElementById('target_' + userId).disabled = false;
        document.getElementById('saveBtn_' + userId).style.display = 'inline';
    }


    function saveEdit(userId, taskId, taskType, semesterId, documentsReq, taskName, taskDescription) {
    const targetInput = document.getElementById('target_' + userId);
    const targetValue = targetInput.value.trim(); // Trim whitespace

    // Validate target value
    if (targetValue === '0') {
        showNotification("Target value cannot be 0."); // Notify user
        return; // Prevent submission if the value is 0
    }

    // Proceed with AJAX request if value is valid
    fetch("../process/update_target.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: new URLSearchParams({ id: userId, target: targetValue })
    })
    .then(response => response.text())
    .then(data => {
        showNotification("Target updated successfully.");
        targetInput.disabled = true; // Disable input after saving
        document.getElementById('saveBtn_' + userId).style.display = 'none'; // Hide save button
        operationPerformed = true; // Set the flag
    })
    .catch(error => console.error("Error:", error));
}

// Function to show the notification
function showNotification(message) {
    const notificationDiv = document.getElementById('notification');
    notificationDiv.textContent = message; // Set the message
    notificationDiv.style.display = 'block'; // Show the notification

    // Hide the notification after 3 seconds
    setTimeout(() => {
        notificationDiv.style.display = 'none'; 
    }, 3000);
}

</script>


<!-- Notification Container -->
<?php include '../../../notiftext/delete_assigned_user.php'; ?>

    </div>
</body>


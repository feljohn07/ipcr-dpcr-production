<?php
session_start();
include '../../../dbconnections/config.php';

// Retrieve office head idnumber from session
$idnumber = $_SESSION['idnumber']; // Ensure this is correctly set in your login process

if (empty($idnumber) || $idnumber == '0') {
    die("Error: Office head ID is not set properly.");
}

// Get semester_id from the query string
if (!isset($_POST['semester_id']) || empty($_POST['semester_id'])) {
    header("Location: ../../dpcrdash.php");
    exit(); // Ensure no further code is executed after the redirect
}

$semester_id = $_POST['semester_id'];

// Fetch semester details
$semester_stmt = $conn->prepare("SELECT * FROM semester_tasks WHERE semester_id = ?");
$semester_stmt->bind_param("i", $semester_id);
$semester_stmt->execute();
$semester_result = $semester_stmt->get_result();
$semester = $semester_result->fetch_assoc();
$semester_stmt->close();

// Fetch task details
$strategic_stmt = $conn->prepare("SELECT * FROM strategic_tasks WHERE semester_id = ?");
$strategic_stmt->bind_param("i", $semester_id);
$strategic_stmt->execute();
$strategic_tasks = $strategic_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$core_stmt = $conn->prepare("SELECT * FROM core_tasks WHERE semester_id = ?");
$core_stmt->bind_param("i", $semester_id);
$core_stmt->execute();
$core_tasks = $core_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$support_stmt = $conn->prepare("SELECT * FROM support_tasks WHERE semester_id = ?");
$support_stmt->bind_param("i", $semester_id);
$support_stmt->execute();
$support_tasks = $support_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$strategic_stmt->close();
$core_stmt->close();
$support_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Tasks</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f9f9f9;
            margin: 0;
            padding: 20px;
            color: #333;
        }

        .container {

            margin: auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            color: #4CAF50;
        }

        fieldset {
            border: none;
            margin-bottom: 20px;
        }

        legend {
            font-weight: bold;
            font-size: 1.2em;
            margin-bottom: 10px;
            color: #4CAF50;
        }

        .task-box {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            padding: 10px;
            margin-bottom: 10px;
            background-color: #f1f1f1;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .task-box label {
            margin-right: 10px;
            flex-basis: auto;
            text-align: left;
            font-weight: 500;
            font-size: 15px;
        }

        .task-box input[type="text"],
        .task-box textarea,
        .task-box input[type="number"] {
            flex-basis: 20%;
            margin-right: 10px;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .task-box textarea {
            resize: vertical;
            flex-basis: 25%;
        }

        button {
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 10px 15px;
            cursor: pointer;
            font-size: 12px;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #45a049;
        }

        @media (max-width: 600px) {
            .task-box {
                flex-direction: column;
                align-items: flex-start;
            }

            .task-box label {
                text-align: left;
                margin-right: 0;
                margin-bottom: 5px;
            }

            .task-box input,
            .task-box textarea {
                flex-basis: 100%;
            }

            button {
                width: 100%;
            }
        }
    </style>
    <script>
        let taskIndex = {
            strategic: <?php echo count($strategic_tasks); ?>,
            core: <?php echo count($core_tasks); ?>,
            support: <?php echo count($support_tasks); ?>,
        };

        function addTaskDescription(taskType) {
            
        }

        function addTask(taskType) {
            const container = document.getElementById(taskType + '-tasks-container');
            const index = taskIndex[taskType]++;
            const taskBox = document.createElement('div');
            taskBox.className = 'task-box';
            taskBox.innerHTML = `
                <label for="${taskType}_task_name_${index}">Task Name:</label>
                <textarea name="${taskType}_task_name[]" id="${taskType}_task_name_${index}" rows="4" required></textarea>

                <label for="${taskType}_description_${index}">Description:</label>
                <textarea name="${taskType}_description[]" id="${taskType}_description_${index}" rows="4" required></textarea>

                <label for="${taskType}_due_date_${index}">Due Date:</label>
                <input type="date" name="${taskType}_due_date[]" id="${taskType}_due_date_${index}" required>

                <label for="${taskType}_documents_required_${index}">Documents Required:</label>
                <input type="number" name="${taskType}_documents_required[]" id="${taskType}_documents_required_${index}" min="0" required>

                <button type="button" class="remove-task-btn" onclick="removeTask(this)">Remove Task</button>
            `;
            container.appendChild(taskBox);
        }

        function removeTask(button) {
            button.parentElement.remove();
        }

        function confirmUpdate(event) {
            event.preventDefault(); // Prevent immediate form submission
            document.getElementById("ipcrconfirmation-model").style.display = "block"; // Show the modal

            // Handle OK button click
            document.getElementById("ipcr-ok-button").addEventListener("click", function () {
                document.getElementById("ipcrconfirmation-model").style.display = "none"; // Hide modal
                event.target.submit(); // Submit the form
            });

            // Handle Cancel button click
            document.getElementById("ipcr-cancel-button").addEventListener("click", function () {
                document.getElementById("ipcrconfirmation-model").style.display = "none"; // Hide modal
            });
        }
    </script>

    <script>
        function goBack() {
            // Navigate to the specified page
            window.location.href = '../../dpcrdash.php';
        }
    </script>

</head>

<body>


    <!-- ipcrconfirmation-model -->
    <div id="ipcrconfirmation-model"
        style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); z-index: 1000;">
        <div
            style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background-color: #fff; padding: 20px; border-radius: 5px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);">
            <p style="font-size: 18px; font-weight: bold;">Confirm Approval</p>
            <p>Click OK to update this task.</p>
            <button id="ipcr-ok-button"
                style="background-color: #4CAF50; color: #fff; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">OK</button>
            <button id="ipcr-cancel-button"
                style="background-color: #e74c3c; color: #fff; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">Cancel</button>
        </div>
    </div>
    <div class="container">
        <h2>Edit Tasks</h2>
        <form method="post" action="../process/task_update.php" onsubmit="confirmUpdate(event)">
            <button id="back-button" type="button"
                style="background-color: green; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; position: fixed; right: 20px; top: 20px; z-index: 1000;"
                onclick="goBack()">Back</button>
            <input type="hidden" name="semester_id" value="<?php echo htmlspecialchars($semester_id); ?>">

            <fieldset>
                <legend>Semester Details</legend>
                <div class="task-box">
                    <label for="semester_name">Semester Name:</label>
                    <input type="text" name="semester_name"
                        value="<?php echo htmlspecialchars($semester['semester_name']); ?>" required>

                    <label for="start_date">Start Date:</label>
                    <input type="date" name="start_date"
                        value="<?php echo htmlspecialchars($semester['start_date']); ?>" required>

                    <label for="end_date">End Date:</label>
                    <input type="date" name="end_date" value="<?php echo htmlspecialchars($semester['end_date']); ?>"
                        required>

                    <label for="college">College:</label>
                    <input type="text" name="college" value="<?php echo htmlspecialchars($semester['college']); ?>"
                        readonly>
                </div>
            </fieldset>

            <fieldset>
                <legend>Strategic Task Details</legend>
                <div id="strategic-tasks-container">
                    <?php foreach ($strategic_tasks as $task): ?>
                        <div class="task-box" id="task-box-<?php echo $task['task_id']; ?>">
                            <label for="strategic_task_name_<?php echo $task['task_id']; ?>">Task Name:</label>
                            <textarea name="strategic_task_name[]" id="strategic_task_name_<?php echo $task['task_id']; ?>"
                                rows="4" required><?php
                                // First remove any escape characters using stripslashes()
                                $task_name = stripslashes($task['task_name']);

                                // If there are any custom break tags, replace them with newline characters
                                // Assuming you're using the same <break(+)line> tag for task names
                                $task_name = str_replace('<break(+)line>', "\n", $task_name);

                                // Display the task name directly in the textarea
                                echo htmlspecialchars($task_name);
                                ?></textarea>
                            <label for="strategic_description_<?php echo $task['task_id']; ?>">Description:</label>
                            <textarea name="strategic_description[]"
                                id="strategic_description_<?php echo $task['task_id']; ?>" rows="4" required><?php
                                   // First remove any escape characters using stripslashes()
                                   $description = stripslashes($task['description']);

                                   // Replace <break(+)line> with newline characters
                                   $description = str_replace('<break(+)line>', "\n", $description);

                                   // Display the description directly in the textarea
                                   echo htmlspecialchars($description);
                                   ?></textarea>
                            <label for="strategic_due_date_<?php echo $task['task_id']; ?>">Due Date:</label>
                            <input type="date" name="strategic_due_date[]"
                                id="strategic_due_date_<?php echo $task['task_id']; ?>"
                                value="<?php echo htmlspecialchars($task['due_date']); ?>" required>
                            <label for="strategic_documents_required_<?php echo $task['task_id']; ?>">Documents
                                Required:</label>
                            <input type="number" name="strategic_documents_required[]"
                                id="strategic_documents_required_<?php echo $task['task_id']; ?>"
                                value="<?php echo htmlspecialchars($task['documents_req']); ?>" min="0" required>
                            <button type="button"
                                onclick="confirmDelete('<?php echo $task['task_id']; ?>', 'strategic')">Delete</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="add-task-btn" onclick="addTask('strategic')">Add Strategic Task</button>
            </fieldset>

            <fieldset>
                <legend>Core Task Details</legend>
                <div id="core-tasks-container">
                    <?php foreach ($core_tasks as $index => $task): ?>
                        <div class="task-box" id="task-box-<?php echo $task['task_id']; ?>">
                            <textarea name="core_task_name[]" id="core_task_name_<?php echo $task['task_id']; ?>" rows="4"
                                required><?php
                                // First remove any escape characters using stripslashes()
                                $task_name = stripslashes($task['task_name']);

                                // If there are any custom break tags, replace them with newline characters
                                // Assuming you're using the same <break(+)line> tag for task names
                                $task_name = str_replace('<break(+)line>', "\n", $task_name);

                                // Display the task name directly in the textarea
                                echo htmlspecialchars($task_name);
                                ?></textarea>
                            <label for="core_description_<?php echo $task['task_id']; ?>">Description:</label>
                            <textarea name="core_description[]" id="core_description_<?php echo $task['task_id']; ?>"
                                rows="4" required><?php
                                // First remove any escape characters using stripslashes()
                                $description = stripslashes($task['description']);

                                // Replace <break(+)line> with newline characters
                                $description = str_replace('<break(+)line>', "\n", $description);

                                // Display the description directly in the textarea
                                echo htmlspecialchars($description);
                                ?></textarea>
                            <label for="core_due_date_<?php echo $task['task_id']; ?>">Due Date:</label>
                            <input type="date" name="core_due_date[]" id="core_due_date_<?php echo $task['task_id']; ?>"
                                value="<?php echo htmlspecialchars($task['due_date']); ?>" required>
                            <label for="core_documents_required_<?php echo $index; ?>">Documents Required:</label>
                            <input type="number" name="core_documents_required[]"
                                id="core_documents_required_<?php echo $index; ?>"
                                value="<?php echo htmlspecialchars($task['documents_req']); ?>" min="0" required>
                            <button type="button"
                                onclick="confirmDelete('<?php echo $task['task_id']; ?>', 'core')">Delete</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="add-task-btn" onclick="addTask('core')">Add Core Task</button>
            </fieldset>

            <fieldset>
                <legend>Support Task Details</legend>
                <div id="support-tasks-container">
                    <?php foreach ($support_tasks as $index => $task): ?>
                        <div class="task-box" id="task-box-<?php echo $task['task_id']; ?>">
                            <textarea name="support_task_name[]" id="support_task_name_<?php echo $task['task_id']; ?>"
                                rows="4" required><?php
                                // First remove any escape characters using stripslashes()
                                $task_name = stripslashes($task['task_name']);

                                // If there are any custom break tags, replace them with newline characters
                                // Assuming you're using the same <break(+)line> tag for task names
                                $task_name = str_replace('<break(+)line>', "\n", $task_name);

                                // Display the task name directly in the textarea
                                echo htmlspecialchars($task_name);
                                ?></textarea>
                            <label for="support_description_<?php echo $task['task_id']; ?>">Description:</label>
                            <textarea name="support_description[]" id="support_description_<?php echo $task['task_id']; ?>"
                                rows="4" required><?php
                                // First remove any escape characters using stripslashes()
                                $description = stripslashes($task['description']);

                                // Replace <break(+)line> with newline characters
                                $description = str_replace('<break(+)line>', "\n", $description);

                                // Display the description directly in the textarea
                                echo htmlspecialchars($description);
                                ?></textarea>
                            <label for="support_due_date_<?php echo $task['task_id']; ?>">Due Date:</label>
                            <input type="date" name="support_due_date[]"
                                id="support_due_date_<?php echo $task['task_id']; ?>"
                                value="<?php echo htmlspecialchars($task['due_date']); ?>" required>
                            <label for="support_documents_required_<?php echo $index; ?>">Documents Required:</label>
                            <input type="number" name="support_documents_required[]"
                                id="support_documents_required_<?php echo $index; ?>"
                                value="<?php echo htmlspecialchars($task['documents_req']); ?>" min="0" required>
                            <button type="button"
                                onclick="confirmDelete('<?php echo $task['task_id']; ?>', 'support')">Delete</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="add-task-btn" onclick="addTask('support')">Add Support Task</button>
            </fieldset>

            <button type="submit" name="update">Update</button>
        </form>
    </div>
    <script>
        function confirmDelete(taskId, taskType) {
            const confirmation = confirm("Are you sure you want to delete this task?");
            if (confirmation) {
                const xhr = new XMLHttpRequest();
                xhr.open("GET", `../process/task_delete.php?task_id=${taskId}&task_type=${taskType}&semester_id=<?php echo $semester_id; ?>`, true);

                xhr.onload = function () {
                    if (xhr.status === 200) {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            const taskBox = document.getElementById(`task-box-${taskId}`);
                            if (taskBox) {
                                taskBox.remove();

                                // Change the Back button to an Update button
                                const backButton = document.getElementById('back-button');
                                backButton.innerText = 'Update';
                                backButton.type = 'submit'; // Change type to submit
                                backButton.onclick = null; // Clear the onclick event to prevent goBack from being called
                            }
                        } else {
                            alert(response.message);
                        }
                    } else {
                        alert("An error occurred while trying to delete the task.");
                    }
                };

                xhr.send();
            }
        }
    </script>
    <script>
        function confirmUpdate(event) {
            event.preventDefault(); // Prevent immediate form submission
            document.getElementById("ipcrconfirmation-model").style.display = "block"; // Show the modal

            // Handle OK button click
            document.getElementById("ipcr-ok-button").onclick = function () {
                document.getElementById("ipcrconfirmation-model").style.display = "none"; // Hide modal
                event.target.submit(); // Submit the form
            };

            // Handle Cancel button click
            document.getElementById("ipcr-cancel-button").onclick = function () {
                document.getElementById("ipcrconfirmation-model").style.display = "none"; // Hide modal
            };
        }

        function goBack() {
            // Navigate to the specified page
            window.location.href = '../../dpcrdash.php';
        }

        // Other functions like addTask, removeTask, etc.
    </script>
</body>

</html>
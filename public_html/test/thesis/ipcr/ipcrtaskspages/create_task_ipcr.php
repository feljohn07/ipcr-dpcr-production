<?php
session_start();
include '../../dbconnections/config.php'; // Include your database connection

$message = '';

// Check if designation is null or empty
if (empty($_SESSION['designation']) && empty($_SESSION['position'])) {
    $message = "Set your Designation and Academic Rank on the Profile First."; // Updated message
} elseif (empty($_SESSION['designation'])) {
    $message = "Set your Designation on the Profile First.";
} elseif (empty($_SESSION['position'])) {
    $message = "Set your Academic Rank on the Profile First."; // This line is already correct
}

// If a message is set, display it and exit
if (!empty($message)) {
    echo "<div style='text-align: center; margin-top: 50px;'>
            <h2>$message</h2>
          </div>";
    exit; // Stop further execution of the script
}

function fetchSemesterData($college) {
    global $conn;
    $semester_data = [];
    
    // Update the query to include a condition for the status column
    $stmt = $conn->prepare("SELECT semester_id, semester_name FROM semester_tasks WHERE college = ? AND status = 'undone'");
    $stmt->bind_param("s", $college); // Bind the college parameter

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $semester_data[] = [
                'id' => $row['semester_id'], // Store each semester id
                'name' => $row['semester_name'] // Store each semester name
            ];
        }
    }
    
    $stmt->close();
    return $semester_data; // Return the array of semester data
}

// Function to determine if the Strategic Task fieldset should be disabled and hidden
function shouldDisableAndHideStrategicFieldset($designation, $position) {
    // Check if the designation is 'None' or 'Instructor with SO' and if the position matches the specified patterns
    if (
        ($designation === 'None' || $designation === 'Instructor with SO') && 
        (preg_match('/^instructor-[1-3]$/', $position) || 
         preg_match('/^assistant-professor-[1-4]$/', $position))
    ) {
        return true; // Disable and hide the fieldset
    }
    return false; // Enable and show the fieldset
}

// Fetch semester data based on the logged-in user's college
$college = $_SESSION['college'] ?? '';
$semester_data = fetchSemesterData($college);

// Check if the fieldset should be disabled and hidden
$disableAndHideStrategicFieldset = shouldDisableAndHideStrategicFieldset($_SESSION['designation'], $_SESSION['position']);
?>

<style > 
        
    fieldset {
        border: 1px solid #ddd;
        margin-top: 10px;
        padding: 10px;
    }

    legend {
        background-color: #efefef;
        padding: 5px 10px;
        border: 1px solid #ccc;
    }

    .task {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 5px; /* Compact padding */
        margin-bottom: 5px; /* Compact margin */
        background-color: #ffffff;
        border: 1px solid #ccc;
    }

    .task label {
        margin-right: 2px; /* Reduced space between labels and inputs */
        flex-basis: 10%;
        font-size: 14px; /* Compact font size */
        text-align: right; /* Align label text to the right to make it close to the input */
    }


    input[type="text"],
    input[type="date"],
    input[type="number"],
    textarea {
        padding: 5px; /* Reduced padding inside the input fields */
        margin-right: 5px; /* Reduced spacing between inputs */
        border: 1px solid #ccc;
    }

    input[type="text"] {
        width: 150px; /* Narrower width for input fields */
    }

    textarea {
        width: 200px; /* Smaller width for the textarea */
        resize: vertical;
    }

    input[type="number"] {
        width: 60px; /* Narrower width for the number input */
    }

    button {
        padding: 8px 15px; /* Smaller button size */
        background-color: #007BFF;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 14px; /* Reduced font size for a smaller button */
    }

    button:hover {
        background-color: #0056b3;
    }

    .remove-task {
        background-color: #ff4d4d;
        color: white;
        border: none;
        border-radius: 5px;
        padding: 5px 10px; /* Reduced padding for the remove button */
        cursor: pointer;
        font-size: 12px;
    }

    .remove-task:hover {
        background-color: #cc0000;
    }

    .task-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .task-container button {
        margin-left: 5px; /* Reduced space between button and input */
    }

    .task input,
    .task textarea {
        flex-grow: 1;
        margin-left: 5px; /* Smaller spacing between inputs */
    }

    #strategic_tasks,
    #core_tasks,
    #support_tasks {
        margin-bottom: 10px; /* Reduced space between fieldsets */
    }

</style>
<h2>Create Your Own Task</h2>
<form id="createTaskForm">
    <fieldset style="border: 1px solid #ddd; margin-top: 10px; padding: 10px; border-radius: 5px; background-color: #f9f9f9;">
        <legend style="background-color: #efefef; padding: 5px 10px; border: 1px solid #ccc; border-radius: 5px; font-weight: bold;">Select Semester</legend>
        <select name="semester_id" id="semester_id" required onchange="updateSemesterName(this)" style="padding: 5px; margin-top: 5px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-size: 14px;">
            <option value="">Select Semester</option>
            <?php foreach ($semester_data as $semester): ?>
                <option value="<?php echo htmlspecialchars($semester['id']); ?>">
                    <?php echo htmlspecialchars($semester['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="hidden" id="semester_name" name="semester_name" value="">
    </fieldset>
    
    <!-- Strategic Tasks -->
    <fieldset id="strategicFieldset" <?php echo $disableAndHideStrategicFieldset ? 'style="display: none;"' : ''; ?>>
        <legend>Strategic Task Details</legend>
        <div id="strategic_tasks">
           <div class="task">
                <label for="strategic_task_name">Outputs:</label>
                <textarea name="strategic_task_name[]" id="strategic_task_name" rows="4" required <?php echo $disableAndHideStrategicFieldset ? 'disabled' : ''; ?>></textarea>
                
                <label for="strategic_description">Success Indicator (Target + Measures):</label>
                <textarea name="strategic_description[]" id="strategic_description" rows="4" required <?php echo $disableAndHideStrategicFieldset ? 'disabled' : ''; ?>></textarea>
                
                <label for="strategic_documents_required">Target:</label>
                <input type="number" name="strategic_documents_required[]" id="strategic_documents_required" min="0" required <?php echo $disableAndHideStrategicFieldset ? 'disabled' : ''; ?>>

                <label for="strategic_due_date">Task Deadline:</label>
                <input type="date" name="strategic_due_date[]" id="strategic_due_date" required style="width: 200px;" <?php echo $disableAndHideStrategicFieldset ? 'disabled' : ''; ?>>
                
                <input type="hidden" name="strategic_college[]" value="<?php echo $_SESSION['college']; ?>">
            </div>
        </div>
        <button type="button" onclick="addTask('strategic_tasks')" <?php echo $disableAndHideStrategicFieldset ? 'disabled' : ''; ?>>Add Strategic Task</button>
        <button type="button" onclick="addDescriptionToLastTask()" <?php echo $disableAndHideStrategicFieldset ? 'disabled' : ''; ?>>Add Success Indicator (Target + Measures)</button>
    </fieldset>

    <!-- Core Tasks -->
    <fieldset id="coreFieldset">
        <legend>Core Task Details</legend>
        <div id="core_tasks">
            <div class="task">
                <label for="core_task_name">Output:</label>
                <textarea name="core_task_name[]" id="core_task_name" rows="4" required></textarea>

                <label for="core_description">Success Indicator (Target + Measures):</label>
                <textarea name="core_description[]" id="core_description" rows="4" required></textarea>

                <label for="core_documents_required">Target:</label>
                <input type="number" name="core_documents_required[]" id="core_documents_required" min="0" required>

                <label for="core_due_date">Task Deadline:</label>
                <input type="date" name="core_due_date[]" id="core_due_date" required style="width: 200px;">
                
                <input type="hidden" name="core_college[]" value="<?php echo $_SESSION['college']; ?>">
            </div>
        </div>
        <button type="button" onclick="addTask('core_tasks')">Add Core Task</button>
        <button type="button" onclick="addDescriptionToLastCoreTask()">Add Success Indicator (Target + Measures)</button>
    </fieldset>

    <!-- Support Tasks -->
    <fieldset id="supportFieldset">
        <legend>Support Task Details</legend>
        <div id="support_tasks">
           <div class="task">
                <label for="support_task_name">Output:</label>
                <textarea name="support_task_name[]" id="support_task_name" rows="4" required></textarea>

                <label for="support_description">Success Indicator (Target + Measures):</label>
                <textarea name="support_description[]" id="support_description" rows="4" required></textarea>

                <label for="support_documents_required">Target:</label>
                <input type="number" name="support_documents_required[]" id="support_documents_required" min="0" required>

                <label for="support_due_date">Task Deadline:</label>
                <input type="date" name="support_due_date[]" id="support_due_date" required style="width: 200px;">
                
                <input type="hidden" name="support_college[]" value="<?php echo $_SESSION['college']; ?>">
            </div>
        </div>
        <button type="button" onclick="addTask('support_tasks')">Add Support Task</button>
        <button type="button" onclick="addDescriptionToLastSupportTask()">Add Success Indicator (Target + Measures)</button>
    </fieldset>

    <button type="submit">Submit</button>
</form>

<!-- Notification Container -->
<div id="notification" style="display: none; padding: 10px; position: fixed; top: 10px; right: 10px; background-color: #28a745; color: white; z-index: 1000; border-radius: 5px;"></div>

<!-- Add this script to store form data in localStorage -->
<script>
    // Function to save form data to localStorage
    function saveFormData() {
        const formData = {};
        
        // Save all input fields and textareas
        $('#createTaskForm input, #createTaskForm textarea').each(function() {
            const name = $(this).attr('name');
            const value = $(this).val();
            if (name) {
                formData[name] = value;
            }
        });

        // Store form data in localStorage
        localStorage.setItem('createTaskFormData', JSON.stringify(formData));
    }

    // Attach the saveFormData function to form inputs
    $('#createTaskForm input, #createTaskForm textarea').on('input', saveFormData);

    // Function to load form data from localStorage
    function loadFormData() {
        const savedData = localStorage.getItem('createTaskFormData');
        
        if (savedData) {
            const formData = JSON.parse(savedData);
            
            // Populate form fields with saved data
            for (const key in formData) {
                if (formData.hasOwnProperty(key)) {
                    const field = $('[name="' + key + '"]');
                    
                    // If the field is a text input or textarea, set the value
                    if (field.is('input') || field.is('textarea')) {
                        field.val(formData[key]);
                    }
                    // If it's a select field, set the selected option
                    else if (field.is('select')) {
                        field.val(formData[key]);
                    }
                }
            }
        }
    }

    // Load the form data when the page is loaded
    $(document).ready(function() {
        loadFormData();
    });

    // Clear the form data from localStorage on form submission
    $('#createTaskForm').on('submit', function() {
        localStorage.removeItem('createTaskFormData');
    });
</script>

<script>
    function updateSemesterName(selectElement) {
        var selectedOption = selectElement.options[selectElement.selectedIndex];
        document.getElementById('semester_name').value = selectedOption.text; // Set the semester name to hidden input
    }

    function addTask(taskType, taskName = '') {
    var container = document.getElementById(taskType);
    var task = container.querySelector('.task').cloneNode(true);
    
    // Clear input and textarea values in the cloned task
    task.querySelectorAll('input, textarea').forEach(input => {
        if (input.type !== 'hidden') input.value = '';
    });

    // If a taskName is provided, set it to the appropriate task name textarea
    if (taskName) {
        if (taskType === 'strategic_tasks') {
            var taskNameInput = task.querySelector('textarea[name="strategic_task_name[]"]');
            taskNameInput.value = taskName; // Set the task name
            taskNameInput.setAttribute('readonly', true); // Make it readonly
        } else if (taskType === 'core_tasks') {
            var taskNameInput = task.querySelector('textarea[name="core_task_name[]"]');
            taskNameInput.value = taskName; // Set the task name
            taskNameInput.setAttribute('readonly', true); // Make it readonly
        } else if (taskType === 'support_tasks') {
            var taskNameInput = task.querySelector('textarea[name="support_task_name[]"]');
            taskNameInput.value = taskName; // Set the task name
            taskNameInput.setAttribute('readonly', true); // Make it readonly
        }
    }

    // Create hidden inputs for semester ID and name for this task
    var semesterId = document.querySelector('select[name="semester_id"]').value;
    var semesterName = document.getElementById('semester_name').value;

    var semesterIdInput = document.createElement('input');
    semesterIdInput.type = 'hidden';
    semesterIdInput.name = taskType + '_semester_id[]'; // Keep it unique for each task type
    semesterIdInput.value = semesterId;
    task.appendChild(semesterIdInput);

    var semesterNameInput = document.createElement('input');
    semesterNameInput.type = 'hidden';
    semesterNameInput.name = taskType + '_semester_name[]'; // Keep it unique for each task type
    semesterNameInput.value = semesterName;
    task.appendChild(semesterNameInput);

    // Add or hide the remove button based on the number of tasks
    var removeButton = document.createElement('button');
    removeButton.type = 'button';
    removeButton.className = 'remove-task';
    removeButton.textContent = 'Remove';
    removeButton.onclick = function() {
        removeTask(removeButton);
    };

    // Append remove button only if there are already more than 1 task
    if (container.querySelectorAll('.task').length > 0) {
        task.appendChild(removeButton);
    }

    container.appendChild(task); // Append the new task to the container
}


    function addDescriptionToLastTask() {
        var tasks = document.querySelectorAll('#strategic_tasks .task');
        if (tasks.length === 0) {
            alert('Please add a task first.');
            return;
        }

        var lastTask = tasks[tasks.length - 1]; // Get the last task
        var taskNameInput = lastTask.querySelector('textarea[name="strategic_task_name[]"]'); // Change to textarea
        var taskName = taskNameInput.value; // Get the task name value

        // Call addTask with the task name to create a new row
        addTask('strategic_tasks', taskName);
    }

    function addDescriptionToLastCoreTask() {
        var tasks = document.querySelectorAll('#core_tasks .task');
        if (tasks.length === 0) {
            alert('Please add a task first.');
            return;
        }

        var lastTask = tasks[tasks.length - 1]; // Get the last task
        var taskNameInput = lastTask.querySelector('textarea[name="core_task_name[]"]'); // Change to textarea
        var taskName = taskNameInput.value; // Get the task name value

        // Call addTask with the task name to create a new row
        addTask('core_tasks', taskName);
    }

    function addDescriptionToLastSupportTask() {
        var tasks = document.querySelectorAll('#support_tasks .task');
        if (tasks.length === 0) {
            alert('Please add a task first.');
            return;
        }

        var lastTask = tasks[tasks.length - 1]; // Get the last task
        var taskNameInput = lastTask.querySelector('textarea[name="support_task_name[]"]'); // Change to textarea
        var taskName = taskNameInput.value; // Get the task name value

        // Call addTask with the task name to create a new row
        addTask('support_tasks', taskName);
    }

    function removeTask(button) {
        button.parentNode.remove();
    }

    document.getElementById('createTaskForm').addEventListener('submit', function(event) {
        event.preventDefault(); // Prevent the form from submitting the default way

        var formData = new FormData(this);

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'ipcrtaskspages/create_task_action.php', true);

        xhr.onload = function () {
            var notificationContainer = document.getElementById('notification');
            notificationContainer.style.display = 'block';
            if (xhr.status === 200) {
                notificationContainer.innerHTML = 'Task created successfully!';
                // Clear the form
                document.getElementById('createTaskForm').reset();
            } else {
                notificationContainer.innerHTML = 'An error occurred while creating the task.';
            }
            // Hide the notification after a few seconds
            setTimeout(function() {
                notificationContainer.style.display = 'none';
            }, 3000); // 3000ms = 3 seconds
        };

        xhr.send(formData);
    });
    
</script>

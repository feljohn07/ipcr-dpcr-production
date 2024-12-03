<?php
include '../process/createtask_action.php';
// Initialize message variable
$message = '';

// Check if designation is null or empty
// Check if first name or last name is missing
if (empty($_SESSION['firstname']) || empty($_SESSION['lastname'])) {
    $message = "Set your Personal Information on Profile."; // This message will be shown if either firstname or lastname is missing
}

// If a message is set, display it and exit
if (!empty($message)) {
    echo "<div style='text-align: center; margin-top: 50px;'>
            <h2>$message</h2>
          </div>";
    exit; // Stop further execution of the script
}
?>
<style > 
    
</style>
<h2>Create Semester Tasks</h2>
<form id="createTaskForm">
    <fieldset>
        <legend>Semester Details</legend>
        <label for="semester_name">Semester :</label>
        <input type="text" id="semester_name" name="semester_name" style="width : 190px;" required>

        <label for="start_date">Start Date:</label>
        <input type="date" id="start_date" name="start_date" required>

        <label for="end_date">End Date:</label>
        <input type="date" id="end_date" name="end_date" required>

        <label for="college">College:</label>
        <input type="text" id="college" name="college" style="width : 230px;" value="<?php echo $_SESSION['college']; ?>" readonly>
    </fieldset>

    <fieldset>
        <legend>Strategic Task Details</legend>
        <div id="strategic_tasks">
            <div class="task">
            <div>
                <label for="strategic_task_name">Outputs:</label>
                <textarea name="strategic_task_name[]" required rows="4" style="width: 500px;"></textarea>
            </div>

            <div>
                <label for="strategic_description">Success Indicator(Target + Measures):</label>
                <textarea name="strategic_description[]" rows="4" required style="width: 500px;"></textarea>
            </div>

            <div>
                <label for="strategic_documents_required">Targets:</label>
                <input type="number" name="strategic_documents_required[]" min="0" required style="width: 50px;">
            </div>

            <div>
                <label for="strategic_due_date">Task Deadline:</label>
                <input type="date" name="strategic_due_date[]" required style="width: 200px;">
            </div>

            <input type="hidden" name="strategic_college[]" value="<?php echo $_SESSION['college']; ?>">
        </div>
    </div>
    <button type="button" onclick="addTask('strategic_tasks')">Add Strategic Task</button>
    <button type="button" onclick="addDescriptionToLastStrategicTask('strategic_tasks')">Add Success Indicator (Target + Measures)</button>
</fieldset>

<fieldset>
    <legend>Core Task Details</legend>
    <div id="core_tasks">
        <div class="task">
            <div>
                <label for="core_task_name">Outputs:</label>
                <textarea name="core_task_name[]" required rows="4" style="width: 500px;"></textarea>
            </div>

            <div>
                <label for="core_description">Success Indicator(Target + Measures):</label>
                <textarea name="core_description[]" rows="4" required style="width: 500px;"></textarea>
            </div>

            <div>
                <label for="core_documents_required">Targets:</label>
                <input type="number" name="core_documents_required[]"  min="0" required style="width: 50px;">
            </div>

            <div>
                <label for="core_due_date">Task Deadline:</label>
                <input type="date" name="core_due_date[]" required style="width: 200px;">
            </div>

            <input type="hidden" name="core_college[]" value="<?php echo $_SESSION['college']; ?>">
        </div>
    </div>
    <button type="button" onclick="addTask('core_tasks')">Add Core Task</button>
    <button type="button" onclick="addDescriptionToLastCoreTask()">Add Success Indicator (Target + Measures)</button>
</fieldset>

    <fieldset>
        <legend>Support Task Details</legend>
        <div id="support_tasks">
            <div class="task">
                <div>
                    <label for="support_task_name">Outputs:</label>
                    <textarea name="support_task_name[]" required rows="4" style="width: 500px;"></textarea>
                </div>

                <div>
                    <label for="support_description">Success Indicator(Target + Measures):</label>
                    <textarea name="support_description[]" rows="4" required style="width: 500px;"></textarea>
                </div>

                <div>
                    <label for="support_documents_required">Targets:</label>
                    <input type="number" name="support_documents_required[]" min="0" required style="width: 50px;">
                </div>

                <div>
                    <label for="support_due_date">Task Deadline:</label>
                    <input type="date" name="support_due_date[]" required style="width: 200px;">
                </div>

                <input type="hidden" name="support_college[]" value="<?php echo $_SESSION['college']; ?>">
            </div>
        </div>
        <button type="button" onclick="addTask('support_tasks')">Add Support Task</button>
        <button type="button" onclick="addDescriptionToLastSupportTask('support_tasks')">Add Success Indicator (Target + Measures)</button>
    </fieldset>

    <button type="submit">Submit</button>
</form>
  <!-- Notification Container -->
<div id="notification" style="display: none; padding: 10px; position: fixed; top: 10px; right: 10px; background-color: #28a745; color: white; z-index: 1000; border-radius: 5px;"></div>

<script>
        $(document).ready(function() {
            loadStoredValues();

            // Store input values in localStorage on input change
            $('input, textarea').on('input', function() {
                localStorage.setItem(this.name, this.value);
            });

            // Function to load stored values
            function loadStoredValues() {
                $('input, textarea').each(function() {
                    var storedValue = localStorage.getItem(this.name);
                    if (storedValue) {
                        this.value = storedValue;
                    }
                });
            }
        
        // Get the current date in UTC
        var today = new Date();
        var utc = today.getTime() + (today.getTimezoneOffset() * 60000); // Convert to UTC
        var manilaDate = new Date(utc + (3600000 * 8)); // Add 8 hours for Manila timezone

        var currentMonth = manilaDate.getMonth(); // 0 = January, 1 = February, ..., 11 = December

        var startDate = $('#start_date');
        var endDate = $('#end_date');
        var semesterName = $('#semester_name');

        // Initialize dates based on the current month
        if (currentMonth >= 7 && currentMonth <= 11) { // August to December
            startDate.val(manilaDate.getFullYear() + '-08-01'); // August 1st
            endDate.val(manilaDate.getFullYear() + '-12-31'); // December 31st
        } else if (currentMonth >= 0 && currentMonth <= 4) { // January to May
            startDate.val(manilaDate.getFullYear() + '-01-01'); // January 1st
            endDate.val(manilaDate.getFullYear() + '-05-31'); // May 31st
        } else {
            startDate.val(manilaDate.getFullYear() + '-06-01'); // Example default
            endDate.val(manilaDate.getFullYear() + '-06-30'); // Example default
        }

        // Function to update semester name based on dates
        function updateSemesterName() {
            var start = new Date(startDate.val());
            var end = new Date(endDate.val());
            var year = start.getFullYear();
            var nextYear = year + 1;
            var lastYear = year - 1;

            if (start.getMonth() === 7 && end.getMonth() === 11) { // August to December
                semesterName.val('1st Semester of AY ' + year + ' - ' + nextYear); // 1st Semester format
            } else if (start.getMonth() === 0 && end.getMonth() === 4) { // January to May
                semesterName.val('2nd Semester of AY ' + lastYear + ' - ' + year); // 2nd Semester format
            } else {
                semesterName.val(''); // Clear if dates don't match
            }
        }
        // Set initial semester name
        updateSemesterName();

        // Add event listeners for changes in start and end dates
        startDate.on('change', updateSemesterName);
        endDate.on('change', updateSemesterName);
    });

    function addTask(taskType) {
        var container = document.getElementById(taskType);
        var tasks = container.querySelectorAll('.task');
        var task = container.querySelector('.task').cloneNode(true);
        task.querySelectorAll('input, textarea').forEach(input => {
            if (input.type !== 'hidden') input.value = '';
        });

        // Update college value for new task
        var collegeInput = task.querySelector('input[name="' + taskType + '_college[]"]');
        if (collegeInput) {
            collegeInput.value = "<?php echo $_SESSION['college']; ?>";
        }

        // Add or hide the remove button based on the number of tasks
        var removeButton = document.createElement('button');
        removeButton.type = 'button';
        removeButton.className = 'remove-task';
        removeButton.textContent = 'Remove';
        removeButton.onclick = function() {
            removeTask(removeButton);
        };

        // Append remove button only if there are already more than 1 task
        if (tasks.length > 0) {
            task.appendChild(removeButton);
        }

        container.appendChild(task);
    }

function addDescriptionToLastStrategicTask() {
    var tasks = document.querySelectorAll('#strategic_tasks .task');
    if (tasks.length === 0) {
        alert('Please add a strategic task first.');
        return;
    }

    var lastTask = tasks[tasks.length - 1]; // Get the last task
    var taskNameInput = lastTask.querySelector('textarea[name="strategic_task_name[]"]'); // Get the task name input
    var taskName = taskNameInput.value; // Get the task name value

    // Clone the last task
    var newTask = lastTask.cloneNode(true);

    // Remove any existing remove buttons from the cloned task
    var existingRemoveButtons = newTask.querySelectorAll('.remove-task');
    existingRemoveButtons.forEach(button => button.remove());

    // Clear input values in the cloned task, except for the task name
    newTask.querySelectorAll('input[type="number"], input[type="date"], textarea').forEach(input => {
        if (input.name !== 'strategic_task_name[]') {
            input.value = '';
        }
    });

    // Set the new task name and make it read-only
    var newTaskNameInput = newTask.querySelector('textarea[name="strategic_task_name[]"]');
    newTaskNameInput.value = taskName;
    newTaskNameInput.setAttribute('readonly', true); // Make the task name read-only

    // Create and append the remove button
    var removeButton = document.createElement('button');
    removeButton.type = 'button';
    removeButton.className = 'remove-task';
    removeButton.textContent = 'Remove';
    removeButton.onclick = function () {
        removeTask(removeButton); // Use the existing removeTask function
    };

    // Append the remove button to the new task
    newTask.appendChild(removeButton);

    // Append the new task to the container
    document.getElementById('strategic_tasks').appendChild(newTask);
}


function addDescriptionToLastCoreTask() {
    var tasks = document.querySelectorAll('#core_tasks .task');
    if (tasks.length === 0) {
        alert('Please add a core task first.');
        return;
    }

    var lastTask = tasks[tasks.length - 1]; // Get the last task
    var taskNameInput = lastTask.querySelector('textarea[name="core_task_name[]"]'); // Get the task name input
    var taskName = taskNameInput.value; // Get the task name value

    // Clone the last task
    var newTask = lastTask.cloneNode(true);

    // Remove any existing remove buttons from the cloned task
    var existingRemoveButtons = newTask.querySelectorAll('.remove-task');
    existingRemoveButtons.forEach(button => button.remove());

    // Clear input values in the cloned task, except for the task name
    newTask.querySelectorAll('input[type="number"], input[type="date"], textarea').forEach(input => {
        if (input.name !== 'core_task_name[]') {
            input.value = '';
        }
    });

    // Set the new task name and make it read-only
    var newTaskNameInput = newTask.querySelector('textarea[name="core_task_name[]"]');
    newTaskNameInput.value = taskName;
    newTaskNameInput.setAttribute('readonly', true); // Make the task name read-only

    // Create and append the remove button
    var removeButton = document.createElement('button');
    removeButton.type = 'button';
    removeButton.className = 'remove-task';
    removeButton.textContent = 'Remove';
    removeButton.onclick = function () {
        removeTask(removeButton); // Use the existing removeTask function
    };

    // Append the remove button to the new task
    newTask.appendChild(removeButton);

    // Append the new task to the container
    document.getElementById('core_tasks').appendChild(newTask);
}

function addDescriptionToLastSupportTask() {
    var tasks = document.querySelectorAll('#support_tasks .task');
    if (tasks.length === 0) {
        alert('Please add a support task first.');
        return;
    }

    var lastTask = tasks[tasks.length - 1]; // Get the last task
    var taskNameInput = lastTask.querySelector('textarea[name="support_task_name[]"]'); // Get the task name input
    var taskName = taskNameInput.value; // Get the task name value

    // Clone the last task
    var newTask = lastTask.cloneNode(true);

    // Remove any existing remove buttons from the cloned task
    var existingRemoveButtons = newTask.querySelectorAll('.remove-task');
    existingRemoveButtons.forEach(button => button.remove());

    // Clear input values in the cloned task, except for the task name
    newTask.querySelectorAll('input[type="number"], input[type="date"], textarea').forEach(input => {
        if (input.name !== 'support_task_name[]') {
            input.value = '';
        }
    });

    // Set the new task name and make it read-only
    var newTaskNameInput = newTask.querySelector('textarea[name="support_task_name[]"]');
    newTaskNameInput.value = taskName;
    newTaskNameInput.setAttribute('readonly', true); // Make the task name read-only

    // Create and append the remove button
    var removeButton = document.createElement('button');
    removeButton.type = 'button';
    removeButton.className = 'remove-task';
    removeButton.textContent = 'Remove';
    removeButton.onclick = function () {
        removeTask(removeButton); // Use the existing removeTask function
    };

    // Append the remove button to the new task
    newTask.appendChild(removeButton);

    // Append the new task to the container
    document.getElementById('support_tasks').appendChild(newTask);
}


    function removeTask(button) {
        var task = button.parentElement;
        var container = task.parentElement;
        var tasks = container.querySelectorAll('.task');

        // Only remove the task if there's more than one
        if (tasks.length > 1) {
            task.remove();
        } else {
            alert('You must have at least one task.');
        }
    }

        document.getElementById('createTaskForm').addEventListener('submit', function(event) {
        event.preventDefault(); // Prevent the form from submitting the default way

        var formData = new FormData(this);

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'taskcontentpages/process/createtask_action.php', true);

        xhr.onload = function () {
            if (xhr.status === 200) {
                var notificationContainer = document.getElementById('notification');
                notificationContainer.style.display = 'block';
                notificationContainer.innerHTML = 'Task created successfully!';

                // Clear the form
                document.getElementById('createTaskForm').reset();
                
                // Clear localStorage to remove stored values
                localStorage.clear();
                // Clear the form
                document.getElementById('createTaskForm').reset();
                // You can also add a timeout to hide the notification after a few seconds
                setTimeout(function() {
                    notificationContainer.style.display = 'none';
                }, 3000); // 3000ms = 3 seconds
            } else {
                var notificationContainer = document.getElementById('notification');
                notificationContainer.style.display = 'block';
                notificationContainer.innerHTML = 'An error occurred while creating the task.';
                // You can also add a timeout to hide the notification after a few seconds
                setTimeout(function() {
                    notificationContainer.style.display = 'none';
                }, 3000); // 3000ms = 3 seconds
            }
        };

        xhr.send(formData);
    });
</script>

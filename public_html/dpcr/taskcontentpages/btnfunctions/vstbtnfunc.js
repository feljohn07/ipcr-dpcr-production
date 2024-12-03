////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// add row createtask functions
function addTask(taskType) {
    var container = document.getElementById(taskType);
    var task = container.querySelector('.task').cloneNode(true);
    task.querySelectorAll('input, textarea').forEach(input => {
        if (input.type !== 'hidden') input.value = '';
    });

    // Update college value for new task
    var collegeInput = task.querySelector('input[name="' + taskType + '_college[]"]');
    if (collegeInput) {
        collegeInput.value = "<?php echo $_SESSION['college']; ?>";
    }

    container.appendChild(task);
}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//view submitted task functions

//view task
function openTasksInNewTab(semesterId) {
    var url = 'taskcontentpages/view_tasks.php?semester_id=' + semesterId;
    var win = window.open(url, '_blank');
    win.focus();
}
//editask
function editTasks(semesterId) {
    var url = 'taskcontentpages/page/editable_task.php?semester_id=' + semesterId;
    window.open(url, '_blank'); // Open the editable form in a new tab
}
//assign user
function redirectAssignUser(semesterId) {
    // Redirect to assign_user.php with semesterId as a parameter
    var url = 'taskcontentpages/page/assignusers.php?semester_id=' + semesterId;
    var win = window.open(url, '_blank');
}

// Function to open the modal
function openModal(comment) {
    var modal = document.getElementById("myModal");
    var modalContent = document.getElementById("modalContent");
    var escapedComment = comment.replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    modalContent.innerHTML = '<p><strong>VP Comment:</strong> ' + escapedComment + '</p>';
    modal.style.display = "block";
}

// Function to close the modal
function closeModal() {
    var modal = document.getElementById("myModal");
    modal.style.display = "none";
}

// Close the modal when the user clicks outside of it
window.onclick = function (event) {
    var modal = document.getElementById("myModal");
    if (event.target == modal) {
        modal.style.display = "none";
    }
}

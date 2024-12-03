document.addEventListener('submit', function(event) {
    if (event.target && event.target.matches('#createTaskForm')) {
        event.preventDefault(); // Prevent the form from submitting the default way

        var formData = new FormData(event.target);

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'taskcontentpages/process/createtask_action.php', true);

        xhr.onload = function () {
            if (xhr.status === 200) {
                alert('Task created successfully!');
                // Optionally, reload the taskContent div or perform any other UI updates
                loadTaskContent('taskcontentpages/page/create_task.php'); // Reloading the task content with all tasks
            } else {
                alert('An error occurred while creating the task.');
            }
        };

        xhr.send(formData);
    }
});
document.addEventListener('submit', function(event) {
    if (event.target && event.target.matches('#upload-doc')) {
        event.preventDefault(); // Prevent the form from submitting the default way

        var formData = new FormData(event.target);

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'ipcrtaskspages/upload_documents.php', true);

        xhr.onload = function () {
            if (xhr.status === 200) {
                alert('Upload successfully!');
                // Optionally, reload the taskContent div or perform any other UI updates
                loadTaskContent('ipcrtaskspages/approvedtask.php'); // Reloading the task content with all tasks
            } else {
                alert('An error occurred while creating the task.');
            }
        };

        xhr.send(formData);
    }
});

document.addEventListener('submit', function(event) {
    if (event.target && event.target.matches('#delete-doc')) {
        event.preventDefault(); // Prevent the form from submitting the default way

        var formData = new FormData(event.target);

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'ipcrtaskspages/delete_file.php', true);

        xhr.onload = function () {
            if (xhr.status === 200) {
                alert('Delete successfully!');
                // Optionally, reload the taskContent div or perform any other UI updates
                loadTaskContent('ipcrtaskspages/approvedtask.php'); // Reloading the task content with all tasks
            } else {
                alert('An error occurred while creating the task.');
            }
        };

        xhr.send(formData);
    }
});



document.querySelectorAll('.update-tasktype-form').forEach(form => {
    const editButton = form.querySelector('.edit-button');
    const saveButton = form.querySelector('.save-button');
    const select = form.querySelector('select');

    editButton.addEventListener('click', function() {
        select.disabled = false; // Enable the dropdown
        editButton.style.display = 'none'; // Hide the Edit button
        saveButton.style.display = 'inline-block'; // Show the Save button
    });

    form.addEventListener('submit', function(event) {
        if (select.disabled) {
            event.preventDefault(); // Prevent form submission if the dropdown is still disabled
            alert('Please click "Edit" to enable the dropdown first.');
        }
    });
});

document.addEventListener('submit', function(event) {
    if (event.target && event.target.matches('.update-tasktype-form')) { // Use a class
        event.preventDefault(); // Prevent the default form submission

        var formData = new FormData(event.target); // Get form data

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'ipcrtaskspages/update_tasktype.php', true);

        xhr.onload = function () {
            if (xhr.status === 200) {
                alert('Task type updated successfully!');
                // Optionally reload the task content
                loadTaskContent('ipcrtaskspages/approvedtask.php'); // Adjust the URL as necessary
            } else {
                alert('An error occurred while updating the task.');
            }
        };

        xhr.send(formData); // Send the form data
    }
});
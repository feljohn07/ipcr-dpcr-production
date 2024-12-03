function showDeclineModal(taskId) {
    document.getElementById('modalTaskId').value = taskId;
    document.getElementById('declineModal').style.display = 'block';
}

function closeDeclineModal() {
    document.getElementById('declineModal').style.display = 'none';
}

function submitDeclineForm() {
    if (confirm('Click OK to confirm the task decline.')) {
        var formData = new FormData(document.getElementById('declineForm'));
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'ipcrtaskspages/update_task_status.php', true);
        xhr.onload = function () {
            if (xhr.status === 200) {
                alert('Task declined successfully.');
                closeDeclineModal();
                location.reload(); // Reload the page to reflect changes
            } else {
                alert('An error occurred while declining the task.');
            }
        };
        xhr.send(formData);
    }
}

function approveTask(taskId) {
    if (confirm('Click OK to confirm approval of this task.')) {
        var formData = new FormData();
        formData.append('task_id', taskId);
        formData.append('action', 'approve');
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'ipcrtaskspages/update_task_status.php', true);
        xhr.onload = function () {
            if (xhr.status === 200) {
                alert('Task approved successfully.');
                location.reload(); // Reload the page to reflect changes
            } else {
                alert('An error occurred while approving the task.');
            }
        };
        xhr.send(formData);
    }
}
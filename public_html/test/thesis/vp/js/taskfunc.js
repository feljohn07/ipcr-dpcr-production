
    function viewTaskDetails(semesterId) {
        // Open a new tab with detailed view
        window.open('view_semestertask_details.php?semester_id=' + semesterId, '_blank');
    }

    function openDisapproveModal(taskId) {
        document.getElementById('disapproveTaskId').value = taskId;
        document.getElementById('vpcomment').value = ''; // Clear the text area
        document.getElementById('disapproveModal').style.display = 'block';
    }

    function closeDisapproveModal() {
        document.getElementById('disapproveModal').style.display = 'none';
    }

    // Close the modal if the user clicks outside of it
    window.onclick = function(event) {
        var modal = document.getElementById('disapproveModal');
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }

    // Function to approve a task
    function approveTask(taskId) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'vptask.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

        xhr.onload = function () {
            if (xhr.status === 200) {
                // Update the task row status
                var taskRow = document.getElementById('task-row-' + taskId);
                var statusCell = taskRow.querySelector('td:nth-child(5)');

                statusCell.innerHTML = '<span class="approve">Approve</span>';
            } else {
                alert('An error occurred while approving the task.');
            }
        };

        xhr.send('task_id=' + encodeURIComponent(taskId) + '&action=approve');
    }

    // Handle disapprove form submission via AJAX
    document.addEventListener('submit', function(event) {
        if (event.target && event.target.matches('#disapproveForm')) {
            event.preventDefault(); // Prevent the form from submitting the default way

            var formData = new FormData(event.target);

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'vptask.php', true);

            xhr.onload = function () {
                if (xhr.status === 200) {
                    alert('Task disapproved successfully!');
                    // Close the modal and update task row
                    closeDisapproveModal();
                    var taskId = document.getElementById('disapproveTaskId').value;
                    var taskRow = document.getElementById('task-row-' + taskId);
                    var statusCell = taskRow.querySelector('td:nth-child(5)');

                    statusCell.innerHTML = '<span class="disapprove">Disapprove</span>';
                } else {
                    alert('An error occurred while disapproving the task.');
                }
            };

            xhr.send(formData);
        }
    });


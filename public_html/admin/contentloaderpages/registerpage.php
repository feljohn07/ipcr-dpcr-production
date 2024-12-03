<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <form id="registrationForm">
        <div class="registration-form">
            <div class="form-group">
                <label>ID Number</label>
                <input type="text" id="idNumber" name="Idnumber" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>College</label>
                    <select id="college" name="College">
                        <option value="">Select College</option>
                        <option value="COLLEGE OF COMPUTING AND INFORMATION SCIENCES">CCIS</option>
                        <option value="COLLEGE OF ENGINEERING AND INDUSTRIAL TECHNOLOGY">CEIT</option>
                        <option value="COLLEGE OF TEACHER EDUCATION">CTE</option>
                        <option value="COLLEGE OF ARTS AND SCIENCES">CAS</option>
                        <option value="COLLEGE OF AGRICULTURE">CA</option>
                        <option value="COLLEGE OF BUSINESS ADMINISTRATION">CBA</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select id="role" name="Role" required>
                        <option value="">Select Role</option>
                        <option value="College President">College President</option>
                        <option value="VPAAQA">VPAAQA</option>
                        <option value="Admin">Admin</option>
                        <option value="Office Head">Office Head</option>
                        <option value="IPCR">IPCR</option>
                        <option value="Immediate Supervisor">Immediate Supervisor</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Create Username</label>
                <input type="text" id="username" name="Username" required>
            </div>
            <div class="form-group">
                <label>Create Password</label>
                <input type="password" id="password" name="Password" required>
            </div>
            <div class="form-group">
                <button type="submit">Register</button>
            </div>
        </div>
    </form>

<?php include '../../notiftext/message.php'; ?>

</body>
</html>


<script>
 // Handle form submission for Registration
var registrationForm = document.getElementById('registrationForm');
if (registrationForm) {
    registrationForm.addEventListener('submit', function (event) {
        event.preventDefault();

        var formData = new FormData(this);
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'functions/register_action.php', true);

        xhr.onload = function () {
            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        sessionStorage.setItem('notificationMessage', response.message);
                        registrationForm.reset();
                        window.location.reload();
                    } else {
                        sessionStorage.setItem('notificationMessage', response.message);
                        sessionStorage.setItem('notificationError', true);
                        window.location.reload();
                    }
                } catch (e) {
                    sessionStorage.setItem('notificationMessage', 'Failed to parse response.');
                    sessionStorage.setItem('notificationError', true);
                    window.location.reload();
                }
            } else {
                sessionStorage.setItem('notificationMessage', 'An error occurred while processing your request.');
                sessionStorage.setItem('notificationError', true);
                window.location.reload();
            }
        };

        xhr.onerror = function () {
            sessionStorage.setItem('notificationMessage', 'An error occurred while processing your request.');
            sessionStorage.setItem('notificationError', true);
            window.location.reload();
        };

        xhr.send(formData);
    });
}

// Check if there's a notification message to display
var notificationMessage = sessionStorage.getItem('notificationMessage');
var notificationError = sessionStorage.getItem('notificationError');
if (notificationMessage) {
    var notification = document.getElementById('notification');
    if (!notification) {
        notification = document.createElement('div');
        notification.id = 'notification';
        notification.style.position = 'fixed';
        notification.style.top = '10px';
        notification.style.right = '10px';
        notification.style.padding = '10px';
        notification.style.borderRadius = '5px';
        notification.style.zIndex = '1000';
        document.body.appendChild(notification);
    }

    notification.textContent = notificationMessage;
    if (notificationError === 'true') {
        notification.style.backgroundColor = '#dc3545';
        notification.style.color = 'white';
    } else {
        notification.style.backgroundColor = '#28a745';
        notification.style.color = 'white';
    }
    notification.style.display = 'block';

    // Hide notification after 3 seconds
    setTimeout(function() {
        notification.style.display = 'none';
        sessionStorage.removeItem('notificationMessage');
        sessionStorage.removeItem('notificationError');
    }, 3000);
}
</script>
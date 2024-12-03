<?php
// Include the database handling file
include '../functions/rdm_action.php'; // Adjust the path as needed
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Role Form</title>
</head>
<body>
<form id="roleForm">
    <table id="roleTable" border="1">
        <tr>
            <th>Function/Position</th>
            <th>Strategic</th>
            <th>Core</th>
            <th>Support</th>
        </tr>
        <tr>
            <td>Office Head</td>
            <td><input type="text" name="strategic_kpi1" value="<?php echo htmlspecialchars($data['strategic_kpi1'] ?? '', ENT_QUOTES); ?>%"></td>
            <td><input type="text" name="core_kpi1" value="<?php echo htmlspecialchars($data['core_kpi1'] ?? '', ENT_QUOTES); ?>%"></td>
            <td><input type="text" name="support_kpi1" value="<?php echo htmlspecialchars($data['support_kpi1'] ?? '', ENT_QUOTES);?>%"></td>
        </tr>
        <tr>
            <td>Instructor to Assistant Professors</td>
            <td><input type="text" name="strategic_kpi2" value="<?php echo htmlspecialchars($data['strategic_kpi2'] ?? '', ENT_QUOTES); ?>%"></td>
            <td><input type="text" name="core_kpi2" value="<?php echo htmlspecialchars($data['core_kpi2'] ?? '', ENT_QUOTES); ?>%"></td>
            <td><input type="text" name="support_kpi2" value="<?php echo htmlspecialchars($data['support_kpi2'] ?? '', ENT_QUOTES); ?>%"></td>
        </tr>
        <tr>
            <td>Associate Professors to Professors</td>
            <td><input type="text" name="strategic_kpi3" value="<?php echo htmlspecialchars($data['strategic_kpi3'] ?? '', ENT_QUOTES); ?>%"></td>
            <td><input type="text" name="core_kpi3" value="<?php echo htmlspecialchars($data['core_kpi3'] ?? '', ENT_QUOTES); ?>%"></td>
            <td><input type="text" name="support_kpi3" value="<?php echo htmlspecialchars($data['support_kpi3'] ?? '', ENT_QUOTES); ?>%"></td>
        </tr>
        <tr>
            <td>Faculty with Designation</td>
            <td><input type="text" name="strategic_kpi4" value="<?php echo htmlspecialchars($data['strategic_kpi4'] ?? '', ENT_QUOTES); ?>%"></td>
            <td><input type="text" name="core_kpi4" value="<?php echo htmlspecialchars($data['core_kpi4'] ?? '', ENT_QUOTES); ?>%"></td>
            <td><input type="text" name="support_kpi4" value="<?php echo htmlspecialchars($data['support_kpi4'] ?? '', ENT_QUOTES); ?>%"></td>
        </tr>
    </table>
    <div class="button-container">
        <button type="submit" class="save-btn">Save</button>
    </div>
</form>

<?php include '../../notiftext/message.php'; ?>


<script>
    // Handle form submission
    document.getElementById('roleForm').addEventListener('submit', function(event) {
        event.preventDefault();

        var formData = new FormData(this);
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'functions/rdm_action.php', true);
        xhr.onload = function () {
            if (xhr.status === 200) {
                sessionStorage.setItem('notificationMessage', 'Role Distribution Matrix Updated Successfully');
                window.location.reload();
            } else {
                // Show error notification if needed
                var notification = document.getElementById('notification');
                notification.textContent = 'An error occurred while updating the user.';
                notification.style.backgroundColor = '#dc3545'; // Change to red for error
                notification.style.display = 'block';

                // Hide notification after 3 seconds
                setTimeout(function() {
                    notification.style.display = 'none';
                }, 3000);
            }
        };
        xhr.send(formData);
    });

    // Check if there's a notification message to display
    var notificationMessage = sessionStorage.getItem('notificationMessage');
    if (notificationMessage) {
        var notification = document.getElementById('notification');
        notification.textContent = notificationMessage;
        notification.style.display = 'block';

        // Hide notification after 3 seconds
        setTimeout(function() {
            notification.style.display = 'none';
            sessionStorage.removeItem('notificationMessage'); // Remove the notification message from storage
        }, 3000);
    }
</script>
</body>
</html>

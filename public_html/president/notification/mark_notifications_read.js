let notificationsMarkedAsRead = false; // Flag to track if notifications have been marked as read

function markNotificationsRead(url) {
    if (url === 'notification/presnotify.php' && !notificationsMarkedAsRead) {
        // Mark notifications as read
        $.post('notification/mark_notifications_read.php', {}, function(response) {
            notificationsMarkedAsRead = true; // Set the flag to true after marking
        });
    }
}


<!-- Notification Container -->
<div id="notification" style="display: none; padding: 10px; position: fixed; top: 10px; right: 10px; background-color: #28a745; color: white; z-index: 1000; border-radius: 5px;"></div>

<!-- Your existing dpcrdash.php content -->

<script>
// Check if there is a success message in PHP session
<?php if (isset($_SESSION['success_message'])): ?>
    document.addEventListener("DOMContentLoaded", function() {
        // Get the notification element
        var notification = document.getElementById('notification');
        
        // Set the notification message from the PHP session
        notification.textContent = "<?php echo $_SESSION['success_message']; ?>";
        
        // Show the notification
        notification.style.display = 'block';

        // Hide the notification after 3 seconds
        setTimeout(function() {
            notification.style.display = 'none';
        }, 3000);
    });
<?php 
    // Unset the session message after it is displayed
    unset($_SESSION['success_message']); 
endif; 
?>
</script>

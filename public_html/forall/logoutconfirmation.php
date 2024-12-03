<style>
    /* Modal styles */
    .modal-logout {
        display: none; /* Hidden by default */
        position: fixed; /* Stay in place */
        z-index: 1; /* Sit on top */
        left: 0;
        top: 0;
        width: 100%; /* Full width */
        height: 100%; /* Full height */
        overflow: auto; /* Enable scroll if needed */
        background-color: rgb(0,0,0); /* Fallback color */
        background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
    }

    .modal-content-logout {
        background-color: #fefefe;
        margin: 15% auto; /* 15% from the top and centered */
        padding: 20px;
        border: 1px solid #888;
        width: 30%; /* Could be more or less, depending on screen size */
        border-radius: 10px;
        text-align: center;
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    }

    .modal-header-logout, .modal-footer-logout {
        padding: 10px;
    }

    .modal-header-logout {
        font-weight: bold;
        font-size: 20px;
    }

    .modal-footer-logout {
        display: flex;
        justify-content: space-between;
    }

    .modal-footer-logout button {
        padding: 10px 20px;
        cursor: pointer;
    }

    .confirm-btn {
        background-color: #d9534f;
        color: white;
        border: none;
        border-radius: 5px;
    }

    .cancel-btn {
        background-color: #5bc0de;
        color: white;
        border: none;
        border-radius: 5px;
    }
</style>

<!-- Logout Confirmation Modal -->
<div id="logoutModal" class="modal-logout">
    <div class="modal-content-logout">
        <div class="modal-header-logout">
            Confirm Logout
        </div>
        <div class="modal-body-logout">
            Are you sure you want to log out?
        </div>
        <div class="modal-footer-logout">
            <button class="confirm-btn" onclick="confirmLogout()">Yes, Logout</button>
            <button class="cancel-btn" onclick="closeLogoutModal()">Cancel</button>
        </div>
    </div>
</div>

<script>
    function showLogoutModal() {
        // Display the modal
        document.getElementById('logoutModal').style.display = 'block';
    }

    function closeLogoutModal() {
        // Hide the modal
        document.getElementById('logoutModal').style.display = 'none';
    }

    function confirmLogout() {
        // Submit the logout form
        document.getElementById('logoutForm').submit();
    }
</script>
<style>
    /* Modal styles */
    .modal-delete-signature {
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

    .modal-content-delete {
        background-color: #fefefe;
        margin: 15% auto; /* 15% from the top and centered */
        padding: 20px;
        border: 1px solid #888;
        width: 30%; /* Could be more or less, depending on screen size */
        border-radius: 10px;
        text-align: center;
    }

    .modal-header-delete, .modal-footer-delete {
        padding: 10px;
    }

    .modal-header-delete {
        font-weight: bold;
        font-size: 20px;
    }

    .modal-footer-delete {
        display: flex;
        justify-content: space-between;
    }

    .modal-footer-delete button {
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

<!-- Delete Signature Confirmation Modal -->
<div id="deleteSignatureModal" class="modal-delete-signature">
    <div class="modal-content-delete">
        <div class="modal-header-delete">
            Confirm Deletion
        </div>
        <div class="modal-body-delete">
            Are you sure you want to delete your signature?
        </div>
        <div class="modal-footer-delete">
            <button class="confirm-btn" onclick="confirmDeleteSignature()">Yes, Delete</button>
            <button class="cancel-btn" onclick="closeDeleteSignatureModal()">Cancel</button>
        </div>
    </div>
</div>

<script>
    function showDeleteSignatureModal() {
        // Display the modal
        document.getElementById('deleteSignatureModal').style.display = 'block';
    }

    function closeDeleteSignatureModal() {
        // Hide the modal
        document.getElementById('deleteSignatureModal').style.display = 'none';
    }

    function confirmDeleteSignature() {
        // Add your deletion logic here, e.g., submit a form or make an API call
        document.getElementById('deleteSignatureForm').submit(); // Update this if using a different method
    }
</script>

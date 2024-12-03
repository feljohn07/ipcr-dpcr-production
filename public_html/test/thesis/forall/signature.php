<?php
session_start();

include '../dbconnections/config.php';

if (!isset($_SESSION['idnumber'])) {
    die("Session idnumber not set");
}
$idnumber = $_SESSION['idnumber'];

// Check for college in session
if (!isset($_SESSION['college'])) {
    die("Session college not set");
}
$college = $_SESSION['college'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signature Pad</title>
    <style>
        .main {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: row; /* Align items horizontally */
            align-items: flex-start; /* Align items at the start of the container */
            justify-content: center; /* Center items horizontally */
            background-color: #f4f4f4;
            text-align: center; 
        }
        .signature-show {
            width: 720px; /* Adjust width as needed */
            height: 400px; /* Adjust height as needed */
            border: 1px solid #000;
            background-color: #fff;
            margin: 10px;
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #888;
            font-size: 18px;
            font-weight: bold;
            text-align: center;
        }

        .signaturecreate {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin: 10px;
        }
        canvas {
            border: 1px solid green;
            background-color: #fff;
            width: 720px; /* Adjust width as needed */
            height: 400px; /* Adjust height as needed */
        }
        
        /* Flex container for buttons */
        .btn-container {
            display: flex;
            justify-content: space-between; /* Space between left and right button groups */
            width: 100%; /* Full width of the container */
            padding: 0px; /* Padding around the buttons */
        }
        .btn-container button {
            padding: 15px 20px; /* Increase padding for bigger buttons */
            border-radius: 10px;
            border: none;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1); /* Add a subtle shadow */
        }
        
        /* Button group on the left */
        .btn-left {
            display: flex;
            justify-content: flex-start;
        }
        
        /* Button group on the right */
        .btn-right {
            display: flex;
            justify-content: flex-end;
            gap: 15px; /* Gap between buttons */
        }
        
        /* Button styling */

        #clearButton {
            background-color: #f44336;
            color: #fff;
        }
        #saveButton {
            background-color: #4CAF50;
            color: #fff;
        }
        #deleteButton {
            background-color: #ff9800;
            color: #fff;
        }

        .modal {
    position: fixed;
    z-index: 1000;
    left: 50%;
    top: 50%;
    transform: translate(-50%, -50%);
    width: 400px;
    background-color: white;
    padding: 20px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    border-radius: 8px;
    display: none;
}

.modal-content {
    margin-bottom: 20px;
}

.modal-footer {
    text-align: right;
}

.modal-footer button {
    margin-left: 10px;
}

#modalConfirmDelete .btn-danger {
    background-color: #dc3545;
    color: white;
}

#modalConfirmDelete .btn-secondary, #notificationModal .btn-secondary {
    background-color: #6c757d;
    color: white;
}

    </style>
</head>
<body>
<div class="main">
        <div class="signature-show-container">
            <label for="signatureShow">Signature Preview:</label>
            <div class="signature-show" id="signatureShow"></div>
        </div>

        <div class="signaturecreate">
            <label for="signaturePad">Signature Pad:</label>
            <canvas id="signaturePad"></canvas>
        </div>  
        
    </div>
    <div class="btn-container">
        <div class="btn-left">
            <button id="deleteButton">Delete</button>
        </div>
        <div class="btn-right">
            <button id="clearButton">Clear</button>
            <button id="saveButton">Save</button>
        </div>
    </div>
    
   <!-- Notification Container -->
<div id="notification" style="display: none; padding: 10px; position: fixed; top: 10px; right: 10px; background-color: #28a745; color: white; z-index: 1000; border-radius: 5px;"></div>

<!-- Modal Structure -->
<div id="modalConfirmDelete" class="modal" style="display: none;">
    <div class="modal-content">
        <h4>Confirm Deletion</h4>
        <p>Are you sure you want to delete your signature?</p>
    </div>
    <div class="modal-footer">
        <button id="confirmDeleteButton" class="btn btn-danger">Delete</button>
        <button id="cancelDeleteButton" class="btn btn-secondary">Cancel</button>
    </div>
</div>

<!-- Notification Modal -->
<div id="notificationModal" class="modal" style="display: none;">
    <div class="modal-content">
        <p id="modalMessage"></p>
    </div>
    <div class="modal-footer">
        <button id="closeModalButton" class="btn btn-secondary">Close</button>
    </div>
</div>


    <script>
      $(document).ready(function() {
    const canvas = document.getElementById('signaturePad');
    const context = canvas.getContext('2d');
    const clearButton = $('#clearButton');
    const saveButton = $('#saveButton');
    const deleteButton = $('#deleteButton');
    const signatureShow = $('#signatureShow');

    // Set canvas dimensions
    canvas.width = canvas.clientWidth;
    canvas.height = canvas.clientHeight;

    // Track mouse events
    let isDrawing = false;

    function startDrawing(event) {
        isDrawing = true;
        context.beginPath();
        context.lineWidth = 2; // Adjust this value to make the lines bolder
        const x = event.clientX - canvas.getBoundingClientRect().left;
        const y = event.clientY - canvas.getBoundingClientRect().top;
        context.moveTo(x, y);
    }

    function draw(event) {
        if (isDrawing) {
            const x = event.clientX - canvas.getBoundingClientRect().left;
            const y = event.clientY - canvas.getBoundingClientRect().top;
            context.lineTo(x, y);
            context.stroke();
        }
    }

    function stopDrawing() {
        if (isDrawing) {
            isDrawing = false;
            context.closePath();
        }
    }

    // Clear canvas
    clearButton.on('click', function() {
        context.clearRect(0, 0, canvas.width, canvas.height);
    });

    // Check if canvas is empty
    function isCanvasEmpty() {
        const imageData = context.getImageData(0, 0, canvas.width, canvas.height);
        const data = imageData.data;
        return !data.some((value, index) => index % 4 === 3 && value > 0); // If any alpha value is not 0
    }

// Notification logic on page load
$(document).ready(function () {
    var notification = document.getElementById('notification');
    var message = sessionStorage.getItem('notificationMessage');
    var isError = sessionStorage.getItem('notificationError') === 'true';

    if (message) {
        notification.textContent = message;
        notification.style.backgroundColor = isError ? '#dc3545' : '#28a745'; // Red for error, green for success
        notification.style.color = 'white';
        notification.style.display = 'block';

        // Hide notification after 3 seconds
        setTimeout(function () {
            notification.style.display = 'none';
            sessionStorage.removeItem('notificationMessage'); // Clear message
            sessionStorage.removeItem('notificationError'); // Clear error status
        }, 3000);
    }
});

// Save canvas signature
saveButton.on('click', function () {
    if (isCanvasEmpty()) {
        var notification = document.getElementById('notification');
        notification.textContent = 'Please draw a signature before saving.';
        notification.style.backgroundColor = '#dc3545'; // Red background
        notification.style.color = 'white';
        notification.style.display = 'block'; // Show the notification

        // Hide notification after 3 seconds
        setTimeout(function () {
            notification.style.display = 'none';
        }, 3000);
        return; // Exit the function if no signature
    }

    const dataURL = canvas.toDataURL('image/png');
    $.ajax({
        url: '../forall/save_signature.php',
        type: 'POST',
        data: { image: dataURL },
        success: function (result) {
            if (result.trim() === "Signature saved successfully") {
                fetchSignature(); // Refresh the displayed signature
                sessionStorage.setItem('notificationMessage', result);
                sessionStorage.setItem('notificationError', 'false'); // No error
                location.reload(); // Reload to show notification
            } else {
                // Handle case where user already has a signature
                sessionStorage.setItem('notificationMessage', result);
                sessionStorage.setItem('notificationError', 'true'); // Set as error
                location.reload(); // Reload to show notification
            }
        },
        error: function (xhr, status, error) {
            console.error('Error:', error);
            sessionStorage.setItem('notificationMessage', 'Failed to save signature. Please try again.');
            sessionStorage.setItem('notificationError', 'true'); // Error occurred
            location.reload(); // Reload to show notification
        }
    });
});



$(document).ready(function() {
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
            notification.style.backgroundColor = '#dc3545'; // Error color
            notification.style.color = 'white';
        } else {
            notification.style.backgroundColor = '#28a745'; // Success color
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
});


 // Show the confirmation modal
deleteButton.on('click', function() {
    $('#modalConfirmDelete').show(); // Display the confirmation modal
});

// When the "Delete" button in the modal is clicked
$('#confirmDeleteButton').on('click', function() {
    $.ajax({
        url: '../forall/delete_signature.php',
        type: 'POST',
        success: function(result) {
            $('#modalConfirmDelete').hide(); // Hide the confirmation modal
            $('#modalMessage').text(result); // Set the result message
            $('#notificationModal').show(); // Show the result modal

            if (result.trim() === "Signature deleted successfully") {
                fetchSignature(); // Refresh the displayed signature
                context.clearRect(0, 0, canvas.width, canvas.height); // Clear the canvas
            }
        },
        error: function(xhr, status, error) {
            console.error('Error:', error);
            $('#modalConfirmDelete').hide(); // Hide the confirmation modal
            $('#modalMessage').text('Failed to delete signature. Please try again.');
            $('#notificationModal').show(); // Show the result modal
        }
    });
});

// Cancel button in the confirmation modal
$('#cancelDeleteButton').on('click', function() {
    $('#modalConfirmDelete').hide(); // Hide the confirmation modal
});

// Close button in the notification modal
$('#closeModalButton').on('click', function() {
    $('#notificationModal').hide(); // Hide the result modal
});



    // Function to fetch the signature and display it
    function fetchSignature() {
        $.ajax({
            url: '../forall/fetch_signature.php',
            type: 'GET',
            success: function(signatureUrl) {
                if (signatureUrl.trim() === "") {
                    signatureShow.css({
                        'background-image': 'none',
                        'color': '#888',
                        'font-size': '18px',
                        'font-weight': 'bold'
                    });
                    signatureShow.text('No signature yet');
                } else {
                    signatureShow.css({
                        'background-image': 'url(' + signatureUrl + ')',
                        'color': 'transparent'
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('Failed to fetch signature:', error);
            }
        });
    }

    // Fetch the signature when the page loads
    fetchSignature();

    // Add event listeners
    canvas.addEventListener('mousedown', startDrawing);
    canvas.addEventListener('mousemove', draw);
    canvas.addEventListener('mouseup', stopDrawing);
    canvas.addEventListener('mouseout', stopDrawing);
});

    </script>
</body>
</html>

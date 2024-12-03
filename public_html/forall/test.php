<?php
session_start();

include '../dbconnections/db01_users.php';

if (!isset($_SESSION['idnumber'])) {
    die("Session idnumber not set");
}
$idnumber = $_SESSION['idnumber'];
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
    <script>

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

    </script>
</body>
</html>

<?php
// Include the sendEmail function
require __DIR__ . '/../includes/verify_email.php';

// Check if query parameters exist in the URL
if (isset($_GET['user_id'], $_GET['email'], $_GET['token'])) {
    // Retrieve query parameters
    $userId = $_GET['user_id']; // Retrieves the 'user_id' parameter
    $email = $_GET['email'];    // Retrieves the 'email' parameter
    $token = $_GET['token'];    // Retrieves the 'token' parameter
} else {
    // Handle missing parameters
    echo "Missing query parameters!";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email</title>
    <style>
        /* Basic styling to center the button and container */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            /* Horizontally center the content */
            align-items: center;
            /* Vertically center the content */
            height: 100vh;
            /* Full viewport height */
            background-color: #f4f4f4;
            /* Light background for better contrast */
        }

        .email-container {
            text-align: center;
            /* Center text inside the container */
            background: #fff;
            /* White background for the container */
            padding: 20px;
            /* Add padding around the content */
            border-radius: 10px;
            /* Rounded corners for better aesthetics */
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            /* Subtle shadow effect */
        }

        .send-button {
            display: inline-block;
            /* Ensures the button takes only necessary width */
            padding: 10px 20px;
            /* Adds space inside the button */
            font-size: 16px;
            /* Larger font for better readability */
            color: #fff;
            /* White text */
            background-color: #007BFF;
            /* Blue background for the button */
            border: none;
            /* Removes default border */
            border-radius: 5px;
            /* Rounded button corners */
            cursor: pointer;
            /* Pointer cursor on hover */
            transition: background-color 0.3s ease;
            /* Smooth hover effect */
        }

        .send-button:hover {
            background-color: #0056b3;
            /* Darker blue on hover */
        }
    </style>
</head>

<body>
    <!-- Email Verification Container -->
    <div class="email-container">
        <form method="POST" action="">
            <!-- Display email dynamically using PHP -->
            <p>
                <?php
                // Output the email dynamically while escaping special characters to prevent XSS
                echo htmlspecialchars($email) . "<br>";
                ?>
            </p>

            <!-- Verify Email Button -->
            <button type="submit" class="send-button">Verify Email</button>
        </form>

        <!-- Display PHP Success Message -->
        <?php
        // When the form is submitted, call the verifyEmail function (assumes it is defined elsewhere)
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            verifyEmail(recipientEmail: $email, userId: $userId);
        }
        ?>
    </div>
</body>

</html>
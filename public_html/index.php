<?php
// Include the login check function
include 'forall/checklogin.php';

// Redirect to dashboard if already logged in
redirectToDashboardIfLoggedIn();

// Include the database connection
include 'dbconnections/config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Interface</title>
    <style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

/* Ensure the body takes up the full viewport height */
html, body {
    height: 100%;
    width: 100%;
}

body {
    display: flex;
    justify-content: flex-start; /* Aligns items to the left */
    align-items: center; /* Vertically centers items */
    height: 100%;
    margin: 0;
    background-color: #C7CBD8;
    font-family: Arial, sans-serif;
}

.container {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh; /* Full height of the viewport */
    max-width: 400px; /* Ensures the container doesn't stretch too wide */
    width: 100%;
    border: 0px solid;
}

.login-box {
    padding: 20px;
    box-shadow: 0 8px 20px black;
    max-width: 400px;
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: center; /* Center items vertically */
    align-items: center; /* Center items horizontally */
    background-image: url('pictures/greencity.jpg'); /* Replace 'your-image-url.jpg' with the URL of your image */
    background-size: cover; /* Ensure the image covers the entire background */
    background-position: center; /* Center the background image */
    background-repeat: no-repeat; /* Prevent the background image from repeating */
}

.logo {
    width: 170px;
    height: 170px;
    margin-bottom: 20px;
    background-image: url('pictures/asscat.jpeg');
    background-size: contain; /* Adjust the size to fit within the container */
    background-repeat: no-repeat;
    background-position: center;
}


.login-form {
    display: flex;
    flex-direction: column;
    margin-bottom: 10px;
}

.login-form input {
    padding: 10px;
    background-color: rgb(255, 255, 255);
    margin-bottom: 15px;
    border: 1px solid #050505;
    border-radius: 5px;
    font-size: 16px;
    text-align: left;
    color: #000000;
    box-shadow: 0 9px 15px #444549;
}

.login-form input::placeholder {
    text-align: center;
}

.login-form button {
    padding: 10px;
    background-color: #007bff;
    color: #fff;
    border: none;
    border-radius: 5px;
    font-size: 16px;
    cursor: pointer;
    box-shadow: 0 8px 20px #444549;
}

.login-form button:hover {
    background-color: #0056b3;
    box-shadow: 0 8px 20px blue;
}

.logo {
    margin-bottom: 20px;
}

.logo img {
    max-width: 100%; /* Ensure the logo image doesn't exceed the container width */
    height: auto; /* Maintain aspect ratio */
}

.login-box p {
    font-size: 40px;
    color: rgba(255, 255, 255, 0.8); /* Set text color with transparency */
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8); /* Add text shadow for 3D effect */
    color: black;
    text-align: center;
}

.container2 {
    background-image: url('pictures/view.png'); /* Replace 'your-image-url.jpg' with the URL of your image */
    background-size: cover; /* Ensure the i mage covers the entire background */
    background-position: center; /* Center the background image */
    background-repeat: no-repeat;
    width: 100%;
    height: 100%;
}

.container2 h1 {
    font-family: 'Arial', sans-serif;
    font-size: 50px;
    color: #333; /* Dark grey text color */
    margin: 0; /* Remove default margin */
}
        .notification {
    position: fixed;
    top: 0;
    left: 50%;
    transform: translateX(-50%);
    background-color: #ff0f0f;
    border: 1px solid #ccc;
    padding: 20px;
    font-size: 18px;
    font-weight: bold;
    display: none;
}
.forgot-password {
            margin-top: 10px;
            text-align: center;
        }

        .forgot-password a {
            color: #007bff;
            text-decoration: none;
            font-size: 14px;
        }

        .forgot-password a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-box">
            <p>SIDAUMS</p>
            <p>FIADP</p>
            <div class="logo"></div>
            <form class="login-form" action="forall/login_action.php" method="POST">
                <input type="text" id="username" name="username" placeholder="Username" required>
                <input type="password" id="password" name="password" placeholder="Password" required>
                <button type="submit">Log In</button>
            </form>
            <div class="forgot-password">
                <a href="forall/forgot_password.php">Forgot Password?</a>
            </div>
        </div>
    </div>
    <div class="container2"></div>

    <?php
    if (isset($_SESSION['error'])) {
        $error = $_SESSION['error'];
        unset($_SESSION['error']);
        ?>
        <div class="notification">
            <p><?= $error ?></p>
        </div>
        <script>
            let notification = document.querySelector('.notification');
            notification.style.display = 'block';
            setTimeout(function() {
                notification.style.display = 'none';
            }, 3000);
        </script>
        <?php
    }
    ?>
</body>
</html>
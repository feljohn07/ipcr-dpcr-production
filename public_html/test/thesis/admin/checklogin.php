<?php
session_start();

function checkLogin() {
    // Check if the session 'username' is set
    if (!isset($_SESSION['username'])) {
        // If not logged in, redirect to the login page
        header("Location: ../forall/login.php");
        exit();
    }
}

function redirectToDashboardIfLoggedIn() {
    // If the user is already logged in, redirect to the dashboard
    if (isset($_SESSION['username'])) {
        header("Location: admindash.php");
        exit();
    }
}
?>

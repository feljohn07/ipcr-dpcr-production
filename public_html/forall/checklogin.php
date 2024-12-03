<?php
session_start();

function checkLogin() {
    // Check if the user is logged in; otherwise, redirect to the login page
    if (!isset($_SESSION['username']) || !isset($_SESSION['role'])) {
        header("Location: ../index.php");
        exit();
    }
}

function redirectToDashboardIfLoggedIn() {
    // Check if the user is logged in
    if (isset($_SESSION['username'])) {
        // Check user role and redirect accordingly
        $role = isset($_SESSION['role']) ? $_SESSION['role'] : ''; // Retrieve user role from session

        switch (strtolower($role)) { // Convert role to lowercase for consistent comparison
            case 'admin':
                header("Location: ../admin/admindash.php");
                break;
            case 'office head':
                header("Location: ../dpcr/dpcrdash.php");
                break;
            case 'ipcr':
                header("Location: ../ipcr/ipcrdash.php");
                break;
            case 'college president':
                header("Location: ../president/pressdash.php");
                break;
            case 'vpaaqa':
                header("Location: ../vp/vpdash.php");
                break;
            default:
                // Handle undefined roles or redirect to a default page
                header("Location: ../default/dashboard.php");
                break;
        }
        exit();
    }
}
?>

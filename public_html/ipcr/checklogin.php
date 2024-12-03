<?php
session_start();

function checkLogin() {
    if (!isset($_SESSION['username']) || !isset($_SESSION['role'])) {
        header("Location: ../index.php");
        exit();
    }
}
?>

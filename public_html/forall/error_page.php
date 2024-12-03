<?php
// Include the login check function
include 'checklogin.php';

// Ensure the user is logged in, if needed
checkLogin();

// Fetch user role from session
$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - Access Denied</title>
    <link rel="stylesheet" href="../admin/css/allcss.css">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #f8d7da;
            color: #721c24;
            font-family: Arial, sans-serif;
        }
        .error-container {
            text-align: center;
            border: 1px solid #f5c6cb;
            background-color: #f8d7da;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .error-container h1 {
            font-size: 2em;
            margin-bottom: 10px;
        }
        .error-container p {
            font-size: 1.2em;
            margin-bottom: 20px;
        }
        .error-container a {
            text-decoration: none;
            color: #0056b3;
            font-size: 1.2em;
        }
        .error-container a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <h1>Access Denied</h1>
        <p>You do not have permission to access this page.</p>
        <?php
        // Determine the correct dashboard link based on user role
        switch (strtolower($role)) {
            case 'admin':
                $dashboardLink = '../admin/admindash.php';
                break;
            case 'office head':
                $dashboardLink = '../dpcr/dpcrdash.php';
                break;
            case 'ipcr':
                $dashboardLink = '../ipcr/ipcrdash.php';
                break;
            case 'college president':
                $dashboardLink = '../president/pressdash.php';
                break;
            case 'vpaaqa':
                $dashboardLink = '../vp/vpdash.php';
                break;
            default:
                $dashboardLink = '../forall/login.php'; // Fallback if role is not recognized
                break;
        }
        ?>
        <p>Please <a href="<?= htmlspecialchars($dashboardLink) ?>">return to your dashboard</a>.</p>
    </div>
</body>
</html>

<?php
include '../forall/checklogin.php';
checkLogin();

// Ensure the semester_id is set and valid
if (!isset($_GET['semester_id']) || empty($_GET['semester_id'])) {
    header("Location: pressdash.php"); // Redirect if semester_id is not provided
    exit();
}

$semester_id = $_GET['semester_id'];

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "04_task";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch semester task details
$task_stmt = $conn->prepare("SELECT * FROM semester_tasks WHERE semester_id = ?");
$task_stmt->bind_param("s", $semester_id);
$task_stmt->execute();
$task_result = $task_stmt->get_result();

if ($task_result->num_rows > 0) {
    $task = $task_result->fetch_assoc();
} else {
    echo "Task not found.";
    exit();
}

$task_stmt->close();

// Function to handle President's approval
if (isset($_POST['approve'])) {
    $approve_stmt = $conn->prepare("UPDATE semester_tasks SET presidentapproval = 1 WHERE semester_id = ?");
    $approve_stmt->bind_param("s", $semester_id);
    $approve_stmt->execute();
    $approve_stmt->close();

    // Redirect to dashboard or task list after approval
    header("Location: pressdash.php");
    exit();
}

// Logout logic
if (isset($_POST['logout'])) {
    session_start();
    session_unset();
    session_destroy();
    header("Location: ../forall/login.php");
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Semester Task</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            padding: 20px;
        }

        .task-details {
            background-color: #fff;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .task-details h2 {
            margin-top: 0;
        }

        .approval-form {
            margin-top: 20px;
        }

        .approval-form button {
            background-color: #4166BB;
            color: white;
            padding: 10px 20px;
            border: none;
            cursor: pointer;
            border-radius: 5px;
        }

        .approval-form button:hover {
            background-color: #325296;
        }

        .approval-message {
            margin-top: 10px;
            font-weight: bold;
        }

        .logout-form {
            margin-top: 20px;
        }

        .logout-form button {
            background-color: #dc3545;
            color: white;
            padding: 10px 20px;
            border: none;
            cursor: pointer;
            border-radius: 5px;
        }

        .logout-form button:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>
    <div class="task-details">
        <h2>Semester Task Details</h2>
        <p><strong>Semester Name:</strong> <?php echo htmlspecialchars($task['semester_name']); ?></p>
        <p><strong>Start Date:</strong> <?php echo htmlspecialchars($task['start_date']); ?></p>
        <p><strong>End Date:</strong> <?php echo htmlspecialchars($task['end_date']); ?></p>
        <p><strong>College:</strong> <?php echo htmlspecialchars($task['college']); ?></p>
        <p><strong>VP Approval:</strong> 
            <?php 
            if ($task['vpapproval'] === null || $task['vpapproval'] === '') {
                echo '<span style="color: blue;">Pending</span>';
            } elseif ($task['vpapproval'] == '0') {
                echo '<span style="color: red;">Disapproved</span>';
            } else {
                echo '<span style="color: green;">Approved</span>';
            }
            ?>
        </p>
        <p><strong>President Approval:</strong> 
            <?php 
            if ($task['presidentapproval'] === null || $task['presidentapproval'] === '') {
                echo '<span style="color: blue;">Pending</span>';
            } elseif ($task['presidentapproval'] == '0') {
                echo '<span style="color: red;">Disapproved</span>';
            } else {
                echo '<span style="color: green;">Approved</span>';
            }
            ?>
        </p>

        <!-- Approval form for President -->
        <?php if ($task['vpapproval'] == '1' && ($task['presidentapproval'] === null || $task['presidentapproval'] === '')) : ?>
            <form class="approval-form" method="post">
                <button type="submit" name="approve">Approve Task</button>
            </form>
        <?php elseif ($task['presidentapproval'] === '0') : ?>
            <p class="approval-message">This task has been disapproved by the President.</p>
        <?php elseif ($task['presidentapproval'] === '1') : ?>
            <p class="approval-message">This task has been approved by the President.</p>
        <?php endif; ?>

        <!-- Logout form -->
        <div class="logout-form">
            <form method="post">
                <button type="submit" name="logout">Logout</button>
            </form>
        </div>
    </div>
</body>
</html>

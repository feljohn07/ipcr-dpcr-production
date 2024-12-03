<?php
// Database connection
require_once('../dbconnections/db03_forforms.php');

$successMessage = "";

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $positions = [
        1 => 'Office Head',
        2 => 'Instructor to Assistant Professors',
        3 => 'Associate Professors to Professors',
        4 => 'Faculty with Designation'
    ];

    $allUpdatesSuccessful = true;

    foreach ($positions as $id => $position) {
        $strategic = isset($_POST["strategic_kpi$id"]) ? $conn->real_escape_string($_POST["strategic_kpi$id"]) : null;
        $core = isset($_POST["core_kpi$id"]) ? $conn->real_escape_string($_POST["core_kpi$id"]) : null;
        $support = isset($_POST["support_kpi$id"]) ? $conn->real_escape_string($_POST["support_kpi$id"]) : null;

        $sql = "UPDATE rdm SET 
                strategic = '$strategic', 
                core = '$core', 
                support = '$support' 
                WHERE position = '$position'";
                
        if (!$conn->query($sql)) {
            $allUpdatesSuccessful = false;
        }
    }

    if ($allUpdatesSuccessful) {
        echo "<script>alert('RDM successfully updated.');</script>";
    } else {
        echo "<script>alert('An error occurred while updating the RDM.');</script>";
    }
}

$conn->close();
?>



<!DOCTYPE html>
<html>
<head>
    <style>
        table {
          border-collapse: collapse;
          width: 100%;
        }
        th, td {
          border: 1px solid black;
          padding: 8px;
          text-align: center;
        }
        th {
          background-color: rgb(65, 71, 153);
          color: #fff;
        }
        td input[type="text"] {
          width: 100%;
          box-sizing: border-box;
          text-align: center;
        }
        .notification {
          margin: 20px 0;
          padding: 10px;
          border: 1px solid green;
          background-color: #ccffcc;
          color: green;
          text-align: center;
        }
        .error {
          margin: 20px 0;
          padding: 10px;
          border: 1px solid red;
          background-color: #ffcccc;
          color: red;
          text-align: center;
        }
    </style>
    <title>Update Strategic, Core, and Support Values</title>
    <script>
        function saveData() {
            document.getElementById("roleForm").submit();
        }
    </script>
</head>
<body>
    <h1>Update Strategic, Core, and Support Values</h1>

    <form id="roleForm" action="" method="post">
        <table id="roleTable" border="1">
            <tr>
                <th>Function/Position</th>
                <th>Strategic</th>
                <th>Core</th>
                <th>Support</th>
            </tr>
            <tr>
                <td>Office Head</td>
                <td><input type="text" name="strategic_kpi1" value="<?php echo htmlspecialchars($_POST['strategic_kpi1'] ?? ''); ?>"></td>
                <td><input type="text" name="core_kpi1" value="<?php echo htmlspecialchars($_POST['core_kpi1'] ?? ''); ?>"></td>
                <td><input type="text" name="support_kpi1" value="<?php echo htmlspecialchars($_POST['support_kpi1'] ?? ''); ?>"></td>
            </tr>
            <tr>
                <td>Instructor to Assistant Professors</td>
                <td><input type="text" name="strategic_kpi2" value="<?php echo htmlspecialchars($_POST['strategic_kpi2'] ?? ''); ?>"></td>
                <td><input type="text" name="core_kpi2" value="<?php echo htmlspecialchars($_POST['core_kpi2'] ?? ''); ?>"></td>
                <td><input type="text" name="support_kpi2" value="<?php echo htmlspecialchars($_POST['support_kpi2'] ?? ''); ?>"></td>
            </tr>
            <tr>
                <td>Associate Professors to Professors</td>
                <td><input type="text" name="strategic_kpi3" value="<?php echo htmlspecialchars($_POST['strategic_kpi3'] ?? ''); ?>"></td>
                <td><input type="text" name="core_kpi3" value="<?php echo htmlspecialchars($_POST['core_kpi3'] ?? ''); ?>"></td>
                <td><input type="text" name="support_kpi3" value="<?php echo htmlspecialchars($_POST['support_kpi3'] ?? ''); ?>"></td>
            </tr>
            <tr>
                <td>Faculty with Designation</td>
                <td><input type="text" name="strategic_kpi4" value="<?php echo htmlspecialchars($_POST['strategic_kpi4'] ?? ''); ?>"></td>
                <td><input type="text" name="core_kpi4" value="<?php echo htmlspecialchars($_POST['core_kpi4'] ?? ''); ?>"></td>
                <td><input type="text" name="support_kpi4" value="<?php echo htmlspecialchars($_POST['support_kpi4'] ?? ''); ?>"></td>
            </tr>
        </table>
        <div class="button-container">
            <button type="button" onclick="saveData()" class="save-btn">Save</button>
        </div>
    </form>
</body>
</html>

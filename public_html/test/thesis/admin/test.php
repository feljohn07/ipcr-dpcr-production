<?php
// Database connection details
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = '03_forforms';

try {
    // Connect to the database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Prepare SQL statement to insert/update data
    $sql = "INSERT INTO roledistributionmatrix (position, strategic, core, support)
            VALUES (:position, :strategic, :core, :support)
            ON DUPLICATE KEY UPDATE strategic = VALUES(strategic), core = VALUES(core), support = VALUES(support)";

    // Loop through each POSTed data and execute the SQL statement
    for ($i = 1; $i <= 4; $i++) { // Adjust the loop limit based on the number of rows
        // Check if all required fields are set
        if (isset($_POST["position{$i}"], $_POST["strategic{$i}"], $_POST["core{$i}"], $_POST["support{$i}"])) {
            $position = $_POST["position{$i}"];
            $strategic = $_POST["strategic{$i}"];
            $core = $_POST["core{$i}"];
            $support = $_POST["support{$i}"];

            // Validate if necessary (e.g., numeric values)

            // Execute SQL statement
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':position' => $position,
                ':strategic' => $strategic,
                ':core' => $core,
                ':support' => $support
            ]);
        } else {
            // Handle case where required fields are missing or null
            echo "Error: Required fields missing for row $i<br>";
        }
    }

    // Optionally, you can fetch the data from the database and echo it back
    // This would involve another query to select the data

    // Example: Fetching and displaying data (if needed)
    $stmt = $pdo->query("SELECT * FROM roledistributionmatrix");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($data as $row) {
        echo "{$row['position']}: Strategic = {$row['strategic']}, Core = {$row['core']}, Support = {$row['support']}<br>";
    }

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Role Distribution Matrix</title>
    <link rel="stylesheet" href="../admin/css/rdm.css">
</head>
<body>
    <h2>Role Distribution Matrix</h2>
    <form action="test.php" method="post" id="roleForm">
        <table id="roleTable">
            <tr>
                <td><input type="hidden" name="position1" value="OH">Office Head</td>
                <td><input type="text" name="strategic1" value=""></td>
                <td><input type="text" name="core1" value=""></td>
                <td><input type="text" name="support1" value=""></td>
            </tr>
            <tr>
                <td><input type="hidden" name="position2" value="IAP">Instructor to Assistant Professors</td>
                <td><input type="text" name="strategic2" value=""></td>
                <td><input type="text" name="core2" value=""></td>
                <td><input type="text" name="support2" value=""></td>
            </tr>
            <tr>
                <td><input type="hidden" name="position3" value="APP">Associate Professors to Professors</td>
                <td><input type="text" name="strategic3" value=""></td>
                <td><input type="text" name="core3" value=""></td>
                <td><input type="text" name="support3" value=""></td>
            </tr>
            <tr>
                <td><input type="hidden" name="position4" value="FWD">Faculty with Designation</td>
                <td><input type="text" name="strategic4" value=""></td>
                <td><input type="text" name="core4" value=""></td>
                <td><input type="text" name="support4" value=""></td>
            </tr>
            <!-- Repeat for other rows -->
        </table>
        <div class="button-container">
            <button type="submit" class="save-btn">Save</button>
        </div>
    </form>

    <script>
        function saveData() {
            // You can optionally handle saving via JavaScript before form submission
            // For now, the form will submit to savedata.php
        }
    </script>
</body>
</html>

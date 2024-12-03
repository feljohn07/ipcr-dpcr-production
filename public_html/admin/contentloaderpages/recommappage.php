<?php
include '../../dbconnections/config.php'; // Ensure this path is correct and the file sets up $conn

// Initialize variables to hold the names
$collegePresident = '';
$vpaaqa = '';

// Fetch College President
$sql = "SELECT firstname, lastname FROM usersinfo WHERE role = 'College President' LIMIT 1";
$result = $conn->query($sql);
if ($result === false) {
    die("Error executing query: " . $conn->error);
}
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $collegePresident = htmlspecialchars($row["firstname"] . " " . $row["lastname"], ENT_QUOTES);
}

// Fetch VPAA
$sql = "SELECT firstname, lastname FROM usersinfo WHERE role = 'VPAAQA' LIMIT 1";
$result = $conn->query($sql);
if ($result === false) {
    die("Error executing query: " . $conn->error);
}
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $vpaaqa = htmlspecialchars($row["firstname"] . " " . $row["lastname"], ENT_QUOTES);
}

// Define colleges and initialize office head variables
$colleges = [
    "COLLEGE OF COMPUTING AND INFORMATION SCIENCES" => 'ccisof',
    "COLLEGE OF ENGINEERING AND INDUSTRIAL TECHNOLOGY" => 'ceitof',
    "COLLEGE OF TEACHER EDUCATION" => 'cteof',
    "COLLEGE OF ARTS AND SCIENCES" => 'casof',
    "COLLEGE OF AGRICULTURE" => 'caof',
    "COLLEGE OF BUSINESS ADMINISTRATION" => 'cbaof'
];

$officeHeads = array_fill_keys(array_values($colleges), '');

// Fetch office heads for each college
foreach ($colleges as $collegeName => $inputId) {
    // Use prepared statements to avoid SQL injection
    $stmt = $conn->prepare("SELECT firstname, lastname FROM usersinfo WHERE college = ? LIMIT 1");
    $stmt->bind_param("s", $collegeName);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result === false) {
        die("Error executing query: " . $conn->error);
    }
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $officeHeads[$inputId] = htmlspecialchars($row["firstname"] . " " . $row["lastname"], ENT_QUOTES);
    } else {
        $officeHeads[$inputId] = ''; // No result found, leave empty
    }
    $stmt->close();
}

// Close connection
$conn->close();
?>

<!-- HTML form with PHP embedded to populate values -->
<div class="form-container"> 
    <div class="recommendingapp-form">
        <input type="text" id="collegePresident" name="collegePresident" value="<?php echo $collegePresident; ?>" readonly>
        <label for="collegePresident">College President</label>
    </div>
    <div class="recommendingapp-form">
        <input type="text" id="vpaaqa" name="vpaaqa" value="<?php echo $vpaaqa; ?>" readonly>
        <label for="vpaaqa">VPAAQA</label>
    </div>
    <div class="recommendingapp-form">
        <input type="text" id="ccisof" name="ccisof" value="<?php echo $officeHeads['ccisof']; ?>" readonly>
        <label for="ccisof">CCIS Office Head</label>
    </div>
    <div class="recommendingapp-form">
        <input type="text" id="ceitof" name="ceitof" value="<?php echo $officeHeads['ceitof']; ?>" readonly>
        <label for="ceitof">CEIT Office Head</label>
    </div>
    <div class="recommendingapp-form">
        <input type="text" id="casof" name="casof" value="<?php echo $officeHeads['casof']; ?>" readonly>
        <label for="casof">CAS Office Head</label>
    </div>
    <div class="recommendingapp-form">
        <input type="text" id="cteof" name="cteof" value="<?php echo $officeHeads['cteof']; ?>" readonly>
        <label for="cteof">CTE Office Head</label>
    </div>
    <div class="recommendingapp-form">
        <input type="text" id="cbaof" name="cbaof" value="<?php echo $officeHeads['cbaof']; ?>" readonly>
        <label for="cbaof">CBA Office Head</label>
    </div>
    <div class="recommendingapp-form">
        <input type="text" id="caof" name="caof" value="<?php echo $officeHeads['caof']; ?>" readonly>
        <label for="caof">CA Office Head</label>
    </div>
</div>

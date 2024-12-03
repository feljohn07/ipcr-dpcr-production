<?php
session_start();
// Ensure the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Database connection
include '../dbconnections/config.php';

// Fetch user details from session (ensure to sanitize appropriately)
$idnumber = isset($_SESSION['idnumber']) ? htmlspecialchars($_SESSION['idnumber']) : '';
$prefix = isset($_SESSION['prefix']) ? htmlspecialchars($_SESSION['prefix']) : ''; // New line for prefix
$firstname = isset($_SESSION['firstname']) ? htmlspecialchars($_SESSION['firstname']) : '';
$lastname = isset($_SESSION['lastname']) ? htmlspecialchars($_SESSION['lastname']) : '';
$middlename = isset($_SESSION['middlename']) ? htmlspecialchars($_SESSION['middlename']) : '';
$suffix = isset($_SESSION['suffix']) ? htmlspecialchars($_SESSION['suffix']) : '';
$college = isset($_SESSION['college']) ? htmlspecialchars($_SESSION['college']) : '';
$gender = isset($_SESSION['gender']) ? htmlspecialchars($_SESSION['gender']) : '';
$phone = isset($_SESSION['phone']) ? htmlspecialchars($_SESSION['phone']) : '';
$role = isset($_SESSION['role']) ? htmlspecialchars($_SESSION['role']) : '';
$designation = isset($_SESSION['designation']) ? htmlspecialchars($_SESSION['designation']) : '';
$position = isset($_SESSION['position']) ? htmlspecialchars($_SESSION['position']) : '';
$gmail = isset($_SESSION['gmail']) ? htmlspecialchars($_SESSION['gmail']) : '';

// Check if the role is DPCR
$isDPCR = ($role === 'DPCR');

// Fetch picture from database
$sqlGetPicture = "SELECT picture FROM usersinfo WHERE idnumber=?";
$picture = null;
if ($stmtPicture = $conn->prepare($sqlGetPicture)) {
    $stmtPicture->bind_param("s", $idnumber);
    $stmtPicture->execute();
    $stmtPicture->bind_result($picture);
    $stmtPicture->fetch();
    $stmtPicture->close();
}

// Handle form submission
// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Fetch data from form
    $prefix = isset($_POST['prefix']) ? htmlspecialchars($_POST['prefix']) : ''; // New field
    $firstname = isset($_POST['firstname']) ? htmlspecialchars($_POST['firstname']) : '';
    $lastname = isset($_POST['lastname']) ? htmlspecialchars($_POST['lastname']) : '';
    $middlename = isset($_POST['middlename']) ? htmlspecialchars($_POST['middlename']) : '';
    $suffix = isset($_POST['suffix']) ? htmlspecialchars($_POST['suffix']) : '';
    $gmail = isset($_POST['gmail']) ? htmlspecialchars($_POST['gmail']) : '';
    $gender = isset($_POST['gender']) ? htmlspecialchars($_POST['gender']) : '';
    $phone = isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '';
    
    // Handle multiple designations
    $designationArray = isset($_POST['designation']) ? $_POST['designation'] : []; // This will be an array
    $designation = implode(', ', array_map('htmlspecialchars', $designationArray)); // Join with comma and sanitize

    $position = isset($_POST['position']) ? htmlspecialchars($_POST['position']) : ''; // New field

    // File upload handling for profile picture
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == UPLOAD_ERR_OK) {
        $picture = file_get_contents($_FILES['profile_picture']['tmp_name']); // Read the uploaded file
        $sqlUpdatePicture = "UPDATE usersinfo SET picture=? WHERE idnumber=?";
        if ($stmtPicture = $conn->prepare($sqlUpdatePicture)) {
            $null = NULL; // Use a placeholder for the blob data
            $stmtPicture->bind_param("bs", $null, $idnumber);
            $stmtPicture->send_long_data(0, $picture); // Send the actual blob data
            $stmtPicture->execute();
            $stmtPicture->close();
        } else {
            echo "Error updating picture: " . $conn->error;
        }
    }

    // Update other profile information
    $sqlUpdateInfo = "UPDATE usersinfo SET prefix=?, firstname=?, lastname=?, middlename=?, suffix=?, gmail=?, gender=?, phone=?, designation=?, position=? WHERE idnumber=?";
    if ($stmt = $conn->prepare($sqlUpdateInfo)) {
        $stmt->bind_param("sssssssssss", $prefix, $firstname, $lastname, $middlename, $suffix, $gmail, $gender, $phone, $designation, $position, $idnumber);
        if ($stmt->execute()) {
            echo "Record updated successfully";
            // Update session variables
            $_SESSION['prefix'] = $prefix; // Update session variable for prefix
            $_SESSION['firstname'] = $firstname;
            $_SESSION['lastname'] = $lastname;
            $_SESSION['middlename'] = $middlename;
            $_SESSION['suffix'] = $suffix;
            $_SESSION['gmail'] = $gmail;
            $_SESSION['gender'] = $gender;
            $_SESSION['phone'] = $phone;
            $_SESSION['designation'] = $designation; // Store as a comma-separated string
            $_SESSION['position'] = $position; // Update session variable
        } else {
            echo "Error updating record: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Error preparing statement: " . $conn->error;
    }
}
// Fetch designations from the database
$sqlGetDesignations = "SELECT designation FROM designations";
$designations = [];

if ($result = $conn->query($sqlGetDesignations)) {
    while ($row = $result->fetch_assoc()) {
        $designations[] = $row['designation'];
    }
    $result->free();
} else {
    echo "Error fetching designations: " . $conn->error;
}

?>
<?php


// Check if the role is "Office Head" and format the ID number
if ($role === 'Office Head') {
    // Use regex to extract the ID number from parentheses
    if (preg_match('/\((.*?)\)/', $idnumber, $matches)) {
        $idnumber = $matches[1]; // Get the content inside the parentheses
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Page</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* General body and container styling */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
    
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
        }
    
        /* Profile form and inputs styling */
        .profile-info {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
    
        .profile-info h2 {
            text-align: center;
            margin-bottom: 20px;
        }
    
        .profile-info .form-group {
            display: flex;  /* Use flexbox for better alignment */
            justify-content: flex-start;  /* Align items to the start */
            margin-bottom: 10px;
            width: 100%;
            align-items: center;  /* Ensures vertical alignment */
        }
    
        .profile-info .form-group label {
            width: 25%;  /* Reduced label width */
            font-weight: bold;
            font-size: 12px; /* Smaller font size for labels */
            margin-bottom: 0;  /* Remove the bottom margin */
            margin-right: 10px;  /* Small space between label and input */
            text-align: right; /* Align labels to the right */
        }
    
        .profile-info .form-group input,
        .profile-info .form-group select {
            width: 70%;  /* Adjust input width */
            padding: 6px;  /* Reduced padding */
            font-size: 12px; /* Smaller font size for inputs */
            border-radius: 4px;
            border: 1px solid #ddd;
        }
    
        /* Styling for profile picture */
        .profile-picture {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
    
        .profile-picture img {
            max-width: 150px;
            max-height: 150px;
            border-radius: 0%;
            border: 4px solid #ddd;
        }
    
        .button-container {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
    
        .submit-btn {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;  /* Adjusted font size for the button */
        }
    
        .submit-btn:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Profile Info Form -->
    <div class="profile-info">
        <h2>Profile Information</h2>

        <!-- Profile Picture -->
        <div class="profile-picture" onclick="document.getElementById('profile_picture_input').click();">
            <?php if ($picture): ?>
                <img src="data:image/jpeg;base64,<?php echo base64_encode($picture); ?>" alt="Profile Picture" id="profile_picture_img">
            <?php else: ?>
                <p>No picture uploaded yet.</p>
            <?php endif; ?>
            <input type="file" id="profile_picture_input" name="profile_picture" style="display: none;" accept="image/*" onchange="previewImage(event)">
        </div>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="idnumber">ID Number</label>
                <input type="text" name="idnumber" id="idnumber" placeholder="ID Number" value="<?php echo $idnumber; ?>" readonly>
            </div>
            <div class="form-group">
                <label for="prefix">Prefix</label>
                <select name="prefix" id="prefix">
                    <option value="" disabled <?php echo $prefix == '' ? 'selected' : ''; ?>>Select Prefix</option>
                    <option value="Dr." <?php echo $prefix == 'Dr.' ? 'selected' : ''; ?>>Dr.</option>
                    <option value="Prof." <?php echo $prefix == 'Prof.' ? 'selected' : ''; ?>>Prof.</option>
                    <option value="Rev." <?php echo $prefix == 'Rev.' ? 'selected' : ''; ?>>Rev.</option>
                    <option value="None" <?php echo $prefix == 'None' ? 'selected' : ''; ?>>None</option>
                </select>
            </div>
            <div class="form-group">
                <label for="firstname">First Name</label>
                <input type="text" name="firstname" id="firstname" placeholder="First Name" value="<?php echo $firstname; ?>">
            </div>
            <div class="form-group">
                <label for="lastname">Last Name</label>
                <input type="text" id="lastname" name="lastname" value="<?php echo $lastname; ?>">
            </div>
            <div class="form-group">
                <label for="middlename">Middle Initial</label>
                <input type="text" name="middlename" id="middlename" placeholder="Middle Initial" value="<?php echo $middlename; ?>" maxlength="2" pattern="[A-Z]\." title="Please enter a single capital letter followed by a period">
            </div>
            <div class="form-group">
                <label for="suffix">Suffix</label>
                <input type="text" name="suffix" id="suffix" placeholder="Suffix" value="<?php echo $suffix; ?>">
            </div>
            <div class="form-group">
                <label for="gmail">Email</label>
                <input type="email" id="gmail" name="gmail" value="<?php echo $gmail; ?>">
            </div>
            <div class="form-group">
                <label for="gender">Gender</label>
                <select id="gender" name="gender">
                    <option value="Male" <?php echo ($gender === 'Male' ? 'selected' : ''); ?>>Male</option>
                    <option value="Female" <?php echo ($gender === 'Female' ? 'selected' : ''); ?>>Female</option>
                </select>
            </div>
            <div class="form-group">
                <label for="college">College</label>
                <input type="text" name="college" id="college" placeholder="College" value="<?php echo $college; ?>" readonly>
            </div>
            <div class="form-group">
    <label for="designation">Designation</label>
    <button type="button" id="edit_designation" onclick="toggleDesignation()">Edit Designation</button>
    <select name="designation[]" id="designation" multiple style="display: none;" disabled onchange="updateDesignationText()">
        <option value='' class="parent-option" disabled style="background-color: #d3d3d3; font-size:15px;" <?php echo ($designation == '' || $designation === null) ? 'selected' : ''; ?>>Hold Shift key to select multiple designation</option>
        <?php 
        // Split the designation string into an array if it's not empty
        $selectedDesignations = !empty($designation) ? explode(', ', $designation) : [];
        foreach ($designations as $desg): ?>
            <option value="<?php echo htmlspecialchars($desg); ?>" <?php echo in_array($desg, $selectedDesignations) ? 'selected' : ''; ?>><?php echo htmlspecialchars($desg); ?></option>
        <?php endforeach; ?>
    </select>
    <input type="text" id="designation_text" value="<?php echo htmlspecialchars(implode(', ', $selectedDesignations)); ?>" readonly style="margin-top: 10px; width: 100%;" />
</div>

<script>
function toggleDesignation() {
    var selectElement = document.getElementById('designation');
    if (selectElement.style.display === 'none') {
        selectElement.style.display = 'block'; // Show the dropdown
        selectElement.disabled = false; // Enable the dropdown
    } else {
        selectElement.style.display = 'none'; // Hide the dropdown
        selectElement.disabled = true; // Disable the dropdown
    }
}

function updateDesignationText() {
    var selectElement = document.getElementById('designation');
    var selectedOptions = Array.from(selectElement.selectedOptions).map(option => option.value);
    document.getElementById('designation_text').value = selectedOptions.join(', '); // Update the text input
}
</script>

<style>
    /* Optional styling for the input field */
    .form-group {
        margin-bottom: 20px;
    }

    #designation_text {
        padding: 8px;
        border: 1px solid #ccc;
        border-radius: 4px;
    }

    #designation {
        margin-top: 10px;
    }
</style>


            <div class="form-group">
                <label for="position">Academic Rank</label>
                <select name="position" id="position">
                <option value="" class="parent-option" disabled style="background-color: #d3d3d3;" <?php echo $position == '' ? 'selected' : ''; ?>>Select Position</option>
                    <option value="" class="parent-option" disabled>Instructors to Assistant Professor</option>
                    <option value="instructor-1" class="instructors-option" <?php echo $position == 'instructor-1' ? 'selected' : ''; ?>>Instructor 1</option>
                    <option value="instructor-2" class="instructors-option" <?php echo $position == 'instructor-2' ? 'selected' : ''; ?>>Instructor 2</option>
                    <option value="instructor-3" class="instructors-option" <?php echo $position == 'instructor-3' ? 'selected' : ''; ?>>Instructor 3</option>
                    <option value="assistant-professor-1" class="instructors-option" <?php echo $position == 'assistant-professor-1' ? 'selected' : ''; ?>>Assistant Professor 1</option>
                    <option value="assistant-professor-2" class="instructors-option" <?php echo $position == 'assistant-professor-2' ? 'selected' : ''; ?>>Assistant Professor 2</option>
                    <option value="assistant-professor-3" class="instructors-option" <?php echo $position == 'assistant-professor-3' ? 'selected' : ''; ?>>Assistant Professor 3</option>
                    <option value="assistant-professor-4" class="instructors-option" <?php echo $position == 'assistant-professor-4' ? 'selected' : ''; ?>>Assistant Professor 4</option>
                    <option value="" class="parent-option" disabled>Associate Professor to Professors</option>
                    <option value="associate-professor-1" class="professors-option" <?php echo $position == 'associate-professor-1' ? 'selected' : ''; ?>>Associate Professor 1</option>
                    <option value="associate-professor-2" class="professors-option" <?php echo $position == 'associate-professor-2' ? 'selected' : ''; ?>>Associate Professor 2</option>
                    <option value="associate-professor-3" class="professors-option" <?php echo $position == 'associate-professor-3' ? 'selected' : ''; ?>>Associate Professor 3</option>
                    <option value="associate-professor-4" class="professors-option" <?php echo $position == 'associate-professor-4' ? 'selected' : ''; ?>>Associate Professor 4</option>
                    <option value="professor-1" class="professors-option" <?php echo $position == 'professor-1' ? 'selected' : ''; ?>>Professor 1</option>
                    <option value="professor-2" class="professors-option" <?php echo $position == 'professor-2' ? 'selected' : ''; ?>>Professor 2</option>
                    <option value="professor-3" class="professors-option" <?php echo $position == 'professor-3' ? 'selected' : ''; ?>>Professor 3</option>
                    <option value="professor-4" class="professors-option" <?php echo $position == 'professor-4' ? 'selected' : ''; ?>>Professor 4</option>
                    <option value="professor-5" class="professors-option" <?php echo $position == 'professor-5' ? 'selected' : ''; ?>>Professor 5</option>
                    <option value="professor-6" class="professors-option" <?php echo $position == 'professor-6' ? 'selected' : ''; ?>>Professor 6</option>
                    <option value="university-professor-1" class="professors-option" <?php echo $position == 'university-professor-1' ? 'selected' : ''; ?>>University Professor 1</option>
                </select>
            </div>
            <div class="form-group">
                <label for="role">Role</label>
                <input type="text" name="role" id="role" placeholder="Role" value="<?php echo $role; ?>" readonly>
            </div>
            <div class="button-container">
                <button type="submit" class="submit-btn">Save Changes</button>
            </div>
        </form>
    </div>
</div>
<!-- Notification Container -->
<div id="notification" style="display: none; padding: 10px; position: fixed; top: 10px; right: 10px; background-color: #28a745; color: white; z-index: 1000; border-radius: 5px;"></div>

<script>
  function previewImage(event) {
    const file = event.target.files[0];
    
    if (file) {
        // Check if the file is an image
        if (file.type.startsWith("image/")) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('profile_picture_img').src = e.target.result;
            };
            reader.readAsDataURL(file);
        } else {
            alert("Please select a valid image file.");
            // Clear the file input so no invalid file is selected
            document.getElementById('profile_picture_input').value = '';
        }
    }
}
</script>    

</body>
</html>
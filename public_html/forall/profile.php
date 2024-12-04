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
    // $designation = isset($_POST['designation']) ? htmlspecialchars($_POST['designation']) : '';
    $position = isset($_POST['position']) ? htmlspecialchars($_POST['position']) : ''; // New field

    if (!empty($_POST['designations'])) {
        // Convert array to comma-separated string
        $designations = implode('|', $_POST['designations']);
        // Save $designation to the database
        
    }

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
    $sqlUpdateInfo = "UPDATE usersinfo SET prefix=?, firstname=?, lastname=?, middlename=?, suffix=?, gender=?, phone=?, designation=?, position=? WHERE idnumber=?";
    if ($stmt = $conn->prepare($sqlUpdateInfo)) {
        $stmt->bind_param("ssssssssss", $prefix, $firstname, $lastname, $middlename, $suffix, $gender, $phone, $designations, $position, $idnumber);
        if ($stmt->execute()) {
            echo "Record updated successfully";
            // Update session variables
            $_SESSION['prefix'] = $prefix; // Update session variable for prefix
            $_SESSION['firstname'] = $firstname;
            $_SESSION['lastname'] = $lastname;
            $_SESSION['middlename'] = $middlename;
            $_SESSION['suffix'] = $suffix;
            // $_SESSION['gmail'] = $gmail;
            $_SESSION['gender'] = $gender;
            $_SESSION['phone'] = $phone;
            $_SESSION['designation'] = $designations;
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

// Fetch user email
$sqlGetDesignations = "SELECT gmail FROM usersinfo WHERE `usersinfo`.`idnumber` = '$idnumber'";

if ($result = $conn->query($sqlGetDesignations)) {
    while ($row = $result->fetch_assoc()) {
        $gmail = $row['gmail'];
    }
    $result->free();
} else {
    echo "Error fetching designations: " . $conn->error;
}

// // Fetch user designations
// $sqlGetDesignations = "SELECT designation FROM usersinfo WHERE `usersinfo`.`idnumber` = '$idnumber'";

// if ($result = $conn->query($sqlGetDesignations)) {
//     while ($row = $result->fetch_assoc()) {
//         $designation = $row['designation'];
//     }
//     $result->free();
// } else {
//     echo "Error fetching designations: " . $conn->error;
// }

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

<style>
    .profile-container {
        width: 50%;
        margin: auto;
        padding: 20px;
        margin-top: 20px;
        background-color: #f9f9f9;
        border-radius: 10px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        font-family: Arial, sans-serif;
        position: relative;
    }

    .profile-picture {
        text-align: center;
        margin-bottom: 20px;
        cursor: pointer;
        /* Add cursor pointer for clickable effect */
    }

    .profile-picture img {
        border-radius: 10%;
        width: 190px;
        height: 190px;
        object-fit: cover;
    }

    .no-picture {
        display: inline-block;
        width: 150px;
        height: 150px;
        line-height: 150px;
        border-radius: 50%;
        background-color: #ddd;
        color: #666;
        text-align: center;
        font-size: 14px;
    }


    form {
        display: flex;
        flex-direction: column;
    }

    .input-group {
        margin-bottom: 15px;
    }

    .parent-option {
        background-color: Green;
        font-weight: bold;
        font-size: 1.1em;
        color: white;
    }

    .input-group input,
    .input-group label,
    .input-group select {
        width: 100%;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 5px;
        margin-top: 5px;
        box-sizing: border-box;
    }

    input[readonly],
    select[disabled] {
        background-color: #e9ecef;
        cursor: not-allowed;
    }

    #profile_picture {
        display: none;
        /* Hide by default */
    }

    input[type="submit"],
    input[type="button"] {
        width: 100px;
        padding: 10px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        margin-top: 10px;
    }

    #save-btn {
        background-color: #28a745;
        color: white;
    }

    #edit-btn {
        background-color: #007bff;
        color: white;
    }

    input[type="submit"]:hover,
    input[type="button"]:hover {
        opacity: 0.8;
    }

    /* Add this style for the settings button */
    /* Basic styling for the dropdown container */
    .dropdown {
        position: relative;
        display: inline-block;
    }

    /* Style for the settings button */
    .settings-button {
        padding: 10px 15px;
        background-color: transparent;
        /* Transparent background */
        color: white;
        /* White text */
        border: none;
        /* No border */
        border-radius: 5px;
        /* Rounded corners */
        cursor: pointer;
        /* Pointer cursor on hover */
        font-size: 16px;
        /* Font size */
    }

    /* Dropdown content (hidden by default) */
    .dropdown-content {
        display: none;
        /* Hidden by default */
        position: absolute;
        /* Position it below the button */
        background-color: white;
        /* White background */
        min-width: 160px;
        /* Minimum width */
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        /* Shadow for depth */
        z-index: 1;
        /* Ensure it appears above other content */
    }

    /* Style for dropdown buttons */
    .dropdown-content button {
        color: black;
        /* Text color */
        padding: 12px 16px;
        /* Padding */
        text-decoration: none;
        /* No underline */
        display: block;
        /* Block display for full width */
        border: none;
        /* No border */
        background: none;
        /* No background */
        width: 100%;
        /* Full width */
        text-align: left;
        /* Align text to the left */
        cursor: pointer;
        /* Pointer cursor on hover */
    }

    /* Change color on hover */
    .dropdown-content button:hover {
        background-color: #f1f1f1;
        /* Light gray background on hover */
    }

    /* Show the dropdown menu when toggled */
    .dropdown-content.show {
        display: block;
        /* Show the dropdown */
    }
</style>

<div class="profile-container">

    <!-- Profile Picture Section -->
    <div class="profile-picture" onclick="document.getElementById('profile_picture').click();">
        <?php if ($picture): ?>
            <img src="data:image/jpeg;base64,<?php echo base64_encode($picture); ?>" alt="Profile Picture">
        <?php else: ?>
            <div class="no-picture">Insert Photo</div>
        <?php endif; ?>
    </div>
    <!-- Settings Button -->
    <div class="dropdown">
        <button class="settings-button" type="button" onclick="toggleDropdown()">⚙️</button>
        <div class="dropdown-content" id="dropdownMenu">
            <button onclick="enableEditing()">Edit</button>
            <button onclick="location.href='../forall/change_password.php';">Change Password</button>
        </div>
        <script>
            function toggleDropdown() {
                document.getElementById("dropdownMenu").classList.toggle("show");
            }

            // Close the dropdown if the user clicks outside of it
            window.onclick = function(event) {
                if (!event.target.matches('.settings-button')) {
                    var dropdowns = document.getElementsByClassName("dropdown-content");
                    for (var i = 0; i < dropdowns.length; i++) {
                        var openDropdown = dropdowns[i];
                        if (openDropdown.classList.contains('show')) {
                            openDropdown.classList.remove('show');
                        }
                    }
                }
            }
        </script>
    </div>
    <!-- Profile Form -->
    <form id="profile-form">
        <div class="input-group">
            <a>ID Number </a>
            <input type="text" name="idnumber" id="idnumber" placeholder="ID Number" value="<?php echo $idnumber; ?>" readonly>
        </div>
        <div class="input-group">
            <a>Prefix </a>
            <select name="prefix" id="prefix" disabled>
                <option value="" disabled <?php echo $prefix == '' ? 'selected' : ''; ?>>Select Prefix</option>
                <option value="Dr." <?php echo $prefix == 'Dr.' ? 'selected' : ''; ?>>Dr.</option>
                <option value="Prof." <?php echo $prefix == 'Prof.' ? 'selected' : ''; ?>>Prof.</option>
                <option value="Rev." <?php echo $prefix == 'Rev.' ? 'selected' : ''; ?>>Rev.</option>
                <option value="None" <?php echo $prefix == 'None' ? 'selected' : ''; ?>>None</option>
            </select>
        </div>
        <div class="input-group">
            <a>First Name </a>
            <input type="text" name="firstname" id="firstname" placeholder="First Name" value="<?php echo $firstname; ?>" readonly>
        </div>
        <div class="input-group">
            <a>Last Name </a>
            <input type="text" name="lastname" id="lastname" placeholder="Last Name" value="<?php echo $lastname; ?>" readonly>
        </div>
        <div class="input-group">
            <a>Middle Initial</a>
            <input type="text" name="middlename" id="middlename" placeholder="Middle Initial" value="<?php echo $middlename; ?>" maxlength="2" pattern="[A-Z]\." title="Please enter a single capital letter followed by a period" readonly>
            <script>
                document.getElementById('middlename').addEventListener('input', function(event) {
                    // Get the current value of the input
                    let value = this.value.toUpperCase(); // Convert to uppercase

                    // If the value is a single letter, append a period
                    if (value.length === 1 && /^[A-Z]$/.test(value)) {
                        this.value = value + '.'; // Append the period
                    }

                    // If the value is more than 2 characters, truncate it
                    if (value.length > 2) {
                        this.value = value.slice(0, 2); // Keep only the first two characters
                    }
                });

                // Handle backspace to remove both letter and period
                document.getElementById('middlename').addEventListener('keydown', function(event) {
                    // Check if the backspace key was pressed
                    if (event.key === 'Backspace') {
                        // Get the current value of the input
                        let value = this.value;

                        // If there's a letter and a period, remove both
                        if (value.length === 2 && value.charAt(1) === '.') {
                            this.value = ''; // Clear the input
                            event.preventDefault(); // Prevent default backspace action
                        }
                    }
                });
            </script>
        </div>
        <div class="input-group">
            <a>Suffix </a>
            <input type="text" name="suffix" id="suffix" placeholder="Suffix" value="<?php echo $suffix; ?>" readonly>
        </div>
        <div class="input-group">
            <a>College </a>
            <input type="text" name="college" id="college" placeholder="College" value="<?php echo $college; ?>" readonly>
        </div>
        <div class="input-group">
            <a>Role </a>
            <input type="text" name="role" id="role" placeholder="Role" value="<?php echo $role; ?>" readonly>
        </div>

        <!-- <div class="input-group">
            <a>Designations </a>
            <input type="text" name="designation_text" id="designation_text" placeholder="Designation" value="<?php echo htmlspecialchars($designation); ?>" readonly>
            <select name="designation" id="designation" style="display: none;" disabled>
                <option value='' class="parent-option" disabled style="background-color: #d3d3d3;" <?php echo ($designation == '' || $designation === null) ? 'selected' : ''; ?>>Select Designation</option>
                <?php foreach ($designations as $desg): ?>
                    <option value="<?php echo htmlspecialchars($desg); ?>" <?php echo $designation == $desg ? 'selected' : ''; ?>><?php echo htmlspecialchars($desg); ?></option>
                <?php endforeach; ?>
            </select>
        </div> -->
        
        <div id="edit-designation-field" class="input-group" style="position: relative; font-family: Arial, sans-serif; display: none;"> 
            <a>Designations</a>
            <div style="border: 1px solid #ccc; border-radius: 4px; position: relative; cursor: pointer;">
                <div style="padding: 10px; background-color: #f9f9f9; display: flex; justify-content: space-between; align-items: center;" onclick="this.nextElementSibling.style.display = this.nextElementSibling.style.display === 'block' ? 'none' : 'block';">
                    Select Designations
                    <span style="font-size: 12px;">&#9662;</span>
                </div>
                <div style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 1px solid #ccc; max-height: 200px; overflow-y: auto; z-index: 1000;">
                    <?php 
                        // Convert string into an array
                        $selectedDesignations = explode('|', $designation); 
                        foreach ($designations as $desg): 
                    ?>
                        <label 
                            style="
                                display: flex; 
                                align-items: center; 
                                padding: 8px 12px; 
                                margin: 0; 
                                cursor: pointer; 
                                border: 1px solid #ddd; 
                                border-radius: 4px; 
                                background-color: #f9f9f9; 
                                transition: background-color 0.3s, box-shadow 0.3s; 
                            "
                            onmouseover="this.style.backgroundColor='#eef'; this.style.boxShadow='0 0 8px rgba(0, 0, 0, 0.1)';"
                            onmouseout="this.style.backgroundColor='#f9f9f9'; this.style.boxShadow='none';"
                        >
                            <input 
                                type="checkbox" 
                                name="designations[]" 
                                value="<?php echo htmlspecialchars($desg); ?>" 
                                <?php echo in_array($desg, $selectedDesignations) ? 'checked' : ''; ?> 
                                style="margin-right: 10px; width: 16px; height: 16px; accent-color: #007BFF;"
                            >
                            <span style="font-size: 14px; color: #333;">
                                <?php echo htmlspecialchars($desg); ?>
                            </span>
                        </label>


                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div id="view-designation-field" class="input-group" style="font-family: Arial, sans-serif;">
            <a style="font-size: 16px; margin-bottom: 10px; display: block;">Designation</a>
            <div style="display: flex; flex-wrap: wrap; gap: 10px; padding: 10px; background-color: #f9f9f9; border: 1px solid #ddd; border-radius: 6px;">
                <?php 
                    // Convert string into an array for display
                    $selectedDesignations = explode('|', $designation); 
                    foreach ($selectedDesignations as $desg): 
                ?>
                    <div style="
                        padding: 8px 12px; 
                        border: 1px solid #ccc; 
                        border-radius: 4px; 
                        background-color: #fff; 
                        color: #333; 
                        font-size: 14px; 
                        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); 
                        cursor: default;
                    ">
                        <?php echo htmlspecialchars($desg); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>



        <div class="input-group">
            <a>Academic Rank</a>
            <select name="position" id="position" disabled>
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
        <div class="input-group">
            <a>Gender </a>
            <select name="gender" id="gender" disabled>
                <option value="Male" <?php echo $gender == 'Male' ? 'selected' : ''; ?>>Male</option>
                <option value="Female" <?php echo $gender == 'Female' ? 'selected' : ''; ?>>Female</option>
            </select>
        </div>
        <!-- <div class="input-group">
            <a>Gmail </a>
            <input type="email" name="gmail" id="gmail" placeholder="Gmail" value="<?php echo $gmail; ?>" readonly>
        </div> -->
        <div class="input-group">
            <a>Phone Number </a>
            <input type="tel" name="phone" id="phone" placeholder="Phone Number" value="<?php echo $phone; ?>" readonly>
        </div>
        <input type="file" name="profile_picture" id="profile_picture" onchange="readURL(this);" disabled>
        <input type="button" id="edit-btn" value="Edit" onclick="enableEditing()" >
        <div>
            <input type="button" id="cancel-edit-btn" value="Cancel" onclick="disableEditing()" style="display: none;">
            <input type="submit" id="save-btn" value="Save" style="display:none;">
        </div>

    </form>

    <br><br>

    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style>
            .loading-spinner {
                display: none;
                width: 20px;
                height: 20px;
                border: 3px solid #f3f3f3;
                border-top: 3px solid #3498db;
                border-radius: 50%;
                animation: spin 1s linear infinite;
                margin-left: 10px;
            }

            @keyframes spin {
                0% {
                    transform: rotate(0deg);
                }

                100% {
                    transform: rotate(360deg);
                }
            }

            #notification-email {
                padding: 10px;
                color: white;
                margin-top: 10px;
                display: none;
            }

            .valid {
                background-color: #28a745;
                /* Green */
            }

            .invalid {
                background-color: #dc3545;
                /* Red */
            }
        </style>
    </head>

    <body>
        <form id="email-form">
            <label for="email">Email</label>

            <div class="input-group">
                <input type="email" id="email" name="email" value="<?php echo $gmail ?>" required oninput="debounceEmailValidation()" disabled>
                <div id="loading-spinner" class="loading-spinner"></div>
                <!-- Hidden field -->
                <input type="hidden" id="idnumber" name="idnumber" value="<?php echo $idnumber ?>">

                <div style="height: 24px;" ><p id="notification-email">Enter Email</p></div>
            </div>
            <div>
                <input id='edit-email' type="button" onclick="toggleEmailInput()" value="Edit Email">
                <input id='validate-email' type="submit" value="Verify Email" disabled>
            </div>

        </form>

    </body>

    </html>
</div>
<!-- Notification Container -->
<div id="notification" style="display: none; padding: 10px; position: fixed; top: 10px; right: 10px; background-color: #28a745; color: white; z-index: 1000; border-radius: 5px;"></div>

<script>
    // EDITED BY REX <3 ----------------------------------------------------------------------- Debouncing and Validation
    let debounceTimer;

    // Enable and Disable Email input 
    // Function to toggle email input enabled/disabled
    function toggleEmailInput() {
        var emailInput = document.getElementById('email');
        var editButton = document.getElementById('edit-email');
        var validateButton = document.getElementById('validate-email');

        if (emailInput.disabled) {
            // Enable the email input
            emailInput.disabled = false;
            validateButton.disabled = false;
            debounceEmailValidation();

        } else {
            // Disable the email input
            emailInput.disabled = true;
            validateButton.disabled = true;

        }

        if (editButton.value == 'Edit Email') {
            editButton.value = 'Cancel';
        } else {
            editButton.value = 'Edit Email';
        }
    }

    // Function to debounce the email validation or API call
    function debounceEmailValidation() {

        // Clear the previous timer (if any)
        clearTimeout(debounceTimer);
        document.getElementById('validate-email').setAttribute('disabled', 'true');

        // Show the loading spinner
        document.getElementById('loading-spinner').style.display = 'inline-block';

        // Set a new timer to run the function after a delay
        debounceTimer = setTimeout(function() {
            validateEmail();
        }, 3); // 1000 ms (1 second) delay
    }

    // Function to simulate an email validation (or call an API, etc.)
    function validateEmail() {
        const emailField = document.getElementById('email');
        const email = emailField.value;

        // Example of a simple email regex check
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        // Hide the loading spinner after the validation is complete
        document.getElementById('loading-spinner').style.display = 'none';
        
        if(email == ''){
            return;
        }

        if (emailRegex.test(email)) {
            showNotification('Valid email!', true);

            const email = document.getElementById('email').value;
            const userId = document.getElementById('idnumber').value;

            const formData = new FormData();
            formData.append('email', email);
            formData.append('idnumber', userId);

            fetch('../feature_experiment/email_verification/includes/check_email_if_exists.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    // Show a message based on PHP's response
                    // console.log(data);

                    if (data.status === 'error') {
                        console.log(data.message); // 'Email Used.
                        showNotification(data.message, false);

                        document.getElementById('validate-email').setAttribute('disabled', 'true');
                        
                    } else if (data.status === 'success') {

                        showNotification('Valid email! and ' + data.message, true);
                        document.getElementById('validate-email').removeAttribute('disabled');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });

        } else {
            showNotification('Invalid email format.', false);
        }
    }


    // Function to show notification
    function showNotification(message, isValid) {
        const notification = document.getElementById('notification-email');

        // Apply the color based on validation
        if (isValid) {
            notification.classList.add('valid');
            notification.classList.remove('invalid');
        } else {
            notification.classList.add('invalid');
            notification.classList.remove('valid');
        }

        notification.textContent = message;
        notification.style.display = 'block';

        // Hide notification after 3 seconds
        setTimeout(function() {
            notification.style.display = 'none';
        }, 3000); // 3000 ms (3 seconds) delay

    }

    // --------------------------------------------------------------------------



    document.addEventListener('submit', function(event) {

        // EDITED BY REX <3 ------------------------------------------------------------- Send Email
        if (event.target && event.target.matches('#email-form')) {
            event.preventDefault(); // Prevent the form from submitting the default way

            var formData = new FormData(event.target);

            var xhr = new XMLHttpRequest();
            xhr.open('POST', '../feature_experiment/email_verification/includes/send_email.php', true);

            xhr.onload = function() {
                var notification = document.getElementById('notification');
                if (xhr.status === 200) {
                    console.log(xhr.response);

                    notification.textContent = 'Verification Sent!';
                    notification.style.backgroundColor = '#28a745'; // Green background
                    notification.style.color = 'white';
                    notification.style.display = 'block'; // Show the notification

                    // Hide notification after 3 seconds
                    setTimeout(function() {
                        notification.style.display = 'none';
                    }, 3000);

                    // Call the function to reset the form to view mode
                    localStorage.setItem('notificationMessage', 'Profile Successfully Updated!');
                    localStorage.setItem('notificationError', 'false'); // No error
                } else {

                }
            };

            xhr.send(formData);

            // --------------------------------------------------------------------------


        } else if (event.target && event.target.matches('#profile-form')) {
            event.preventDefault(); // Prevent the form from submitting the default way

            console.log('profile form - clicked');

            var formData = new FormData(event.target);

            // Log each key-value pair
            for (let [key, value] of formData.entries()) {
                console.log(key + ': ' + value);
            }

            var xhr = new XMLHttpRequest();
            xhr.open('POST', '../forall/profile.php', true);

            xhr.onload = function() {
                var notification = document.getElementById('notification');
                if (xhr.status === 200) {
                    
                    // Edited by Rex ------------------------------------------

                    console.log(xhr.response);
                    // return

                    //  -------------------------------------------------------
                    notification.textContent = 'Profile Successfully Updated!';
                    notification.style.backgroundColor = '#28a745'; // Green background
                    notification.style.color = 'white';
                    notification.style.display = 'block'; // Show the notification

                    // Hide notification after 3 seconds
                    setTimeout(function() {
                        notification.style.display = 'none';
                    }, 3000);

                    // Call the function to reset the form to view mode
                    localStorage.setItem('notificationMessage', 'Profile Successfully Updated!');
                    localStorage.setItem('notificationError', 'false'); // No error
                    window.location.reload();
                    disableEditing();
                } else {
                    notification.textContent = 'An error occurred while updating the profile.';
                    notification.style.backgroundColor = '#dc3545'; // Red background
                    notification.style.color = 'white';
                    notification.style.display = 'block'; // Show the notification

                    // Hide notification after 3 seconds
                    setTimeout(function() {
                        notification.style.display = 'none';
                    }, 3000);
                }
            };

            xhr.send(formData);
        }
    });

    // Check if the page should show the notification
    var notification = document.getElementById('notification');
    if (localStorage.getItem('notificationMessage')) {
        notification.textContent = localStorage.getItem('notificationMessage');
        if (localStorage.getItem('notificationError') === 'true') {
            notification.style.backgroundColor = '#dc3545'; // Red background
        } else {
            notification.style.backgroundColor = '#28a745'; // Green background
        }
        notification.style.color = 'white';
        notification.style.display = 'block'; // Show the notification

        // Hide notification after 3 seconds
        setTimeout(function() {
            notification.style.display = 'none';
        }, 3000);

        // Remove the notification message and error flag
        localStorage.removeItem('notificationMessage');
        localStorage.removeItem('notificationError');
    }


    function readURL(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();

            reader.onload = function(e) {
                document.querySelector('.profile-picture img').src = e.target.result;
            }

            reader.readAsDataURL(input.files[0]); // convert to base64 string
        }
    }

    function enableEditing() {

        // Rex
        // Hide view-field designations
        document.getElementById('edit-designation-field').style.display = 'block';
        document.getElementById('view-designation-field').style.display = 'none';
        document.getElementById('cancel-edit-btn').style.display = 'inline-block';
        //

        var fields = document.querySelectorAll('#profile-form input[type="text"], #profile-form input[type="email"], #profile-form input[type="tel"], #profile-form input[type="file"], #profile-form select');
        fields.forEach(function(field) {
            if (field.id !== 'idnumber' && field.id !== 'college' && field.id !== 'role') {
                field.removeAttribute('readonly');
                field.removeAttribute('disabled');
            }
            if (field.type === 'file') {
                field.removeAttribute('disabled');
            }
        });

        // Handle the designation toggle
        // var designationText = document.getElementById('designation_text');
        // var designationSelect = document.getElementById('designation');

        // designationText.style.display = 'none'; // Hide text input
        // designationSelect.style.display = 'block'; // Show select dropdown
        // designationSelect.removeAttribute('disabled'); // Enable the select dropdown

        document.getElementById('edit-btn').style.display = 'none';
        document.getElementById('save-btn').style.display = 'inline-block';
    }

    function disableEditing() {

        // Rex
        // Hide Edit-field designations
        document.getElementById('edit-designation-field').style.display = 'none';
        document.getElementById('view-designation-field').style.display = 'block';
        document.getElementById('cancel-edit-btn').style.display = 'none';
        //

        var fields = document.querySelectorAll('#profile-form input[type="text"], #profile-form input[type="email"], #profile-form input[type="tel"], #profile-form input[type="file"], #profile-form select');
        fields.forEach(function(field) {
            field.setAttribute('readonly', true);
            field.setAttribute('disabled', true);
        });

        // Handle the designation toggle back
        // var designationText = document.getElementById('designation_text');
        // var designationSelect = document.getElementById('designation');

        // designationSelect.style.display = 'none'; // Hide select dropdown
        // designationText.style.display = 'block'; // Show text input
        // designationText.value = designationSelect.options[designationSelect.selectedIndex].text; // Set the input value to the selected option

        document.getElementById('edit-btn').style.display = 'inline-block';
        document.getElementById('save-btn').style.display = 'none';
    }

    function toggleFieldsBasedOnRole() {
        var role = '<?php echo $role; ?>'; // Get the role from PHP
        // var designationField = document.getElementById('designation');
        var positionField = document.getElementById('position');
        // var gmailField = document.getElementById('gmail');
        var phoneField = document.getElementById('phone');

        // Hide the phone field for all roles
        phoneField.parentElement.style.display = 'none'; // Hide phone field

        // Check the role and toggle visibility accordingly
        if (role === 'Office Head') {
            // designationField.parentElement.style.display = 'block'; // Hide designation field
            positionField.parentElement.style.display = 'none'; // Hide position field
            // gmailField.parentElement.style.display = 'block'; // Show email field
        } else if (role === 'College President' || role === 'VPAAQA') {
            // designationField.parentElement.style.display = 'none'; // Hide designation field
            positionField.parentElement.style.display = 'none'; // Hide position field
            // gmailField.parentElement.style.display = 'block'; // Show email field
        } else {
            // designationField.parentElement.style.display = 'block'; // Show designation field
            positionField.parentElement.style.display = 'block'; // Show position field
            // gmailField.parentElement.style.display = 'block'; // Show email field
        }
    }

    // Call the function on page load
    toggleFieldsBasedOnRole();
</script>
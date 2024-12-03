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
    $designation = isset($_POST['designation']) ? htmlspecialchars($_POST['designation']) : '';
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
            $_SESSION['designation'] = $designation;
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
    
        .profile-info .form-group input[type="file"] {
            padding: 0;
            display: none; /* Hide file input */
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
    
        /* Profile picture section */
        .profile-picture {
            background-color: #ffffff;
            padding: 20px;
            margin-bottom: 10px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
            cursor: default; /* Prevent clicking */
            margin-top: -20px; /* Moves the profile picture up */
        }
    
        .profile-picture p {
            color: #888;
            font-size: 14px;
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Profile Info Form -->
    <div class="profile-info">
        <h2>Profile Information</h2>

        <!-- Profile Picture -->
        <div class="profile-picture">
            <?php if ($picture): ?>
                <img src="data:image/jpeg;base64,<?php echo base64_encode($picture); ?>" alt="Profile Picture" id="profile_picture_img">
            <?php else: ?>
                <p>No picture uploaded yet.</p>
            <?php endif; ?>
        </div>
        <div class="dropdown">
            <button class="settings-button" type="button" onclick="toggleDropdown()">⚙️</button>
            <div class="dropdown-content" id="dropdownMenu">
                <button onclick="location.href='../forall/profile_edit.php';">Edit</button>
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
            <style>
            /* Basic styling for the dropdown container */
                .dropdown {
                    position: relative;
                    display: inline-block;
                }

                /* Style for the settings button */
                .settings-button {
                    padding: 10px 15px;
                    background-color: transparent; /* Transparent background */
                    color: white; /* White text */
                    border: none; /* No border */
                    border-radius: 5px; /* Rounded corners */
                    cursor: pointer; /* Pointer cursor on hover */
                    font-size: 20px; /* Font size */
                }

                /* Dropdown content (hidden by default) */
                .dropdown-content {
                    display: none; /* Hidden by default */
                    position: absolute; /* Position it below the button */
                    background-color: white; /* White background */
                    min-width: 160px; /* Minimum width */
                    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2); /* Shadow for depth */
                    z-index: 1; /* Ensure it appears above other content */
                }

                /* Style for dropdown buttons */
                .dropdown-content button {
                    color: black; /* Text color */
                    padding: 12px 16px; /* Padding */
                    text-decoration: none; /* No underline */
                    display: block; /* Block display for full width */
                    border: none; /* No border */
                    background: none; /* No background */
                    width: 100%; /* Full width */
                    text-align: left; /* Align text to the left */
                    cursor: pointer; /* Pointer cursor on hover */
                }

                /* Change color on hover */
                .dropdown-content button:hover {
                    background-color: #f1f1f1; /* Light gray background on hover */
                }

                /* Show the dropdown menu when toggled */
                .dropdown-content.show {
                    display: block; /* Show the dropdown */
                }
            </style>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="idnumber">ID Number</label>
                <input type="text" name="idnumber" id="idnumber" placeholder="ID Number" value="<?php echo $idnumber; ?>" readonly>
            </div>
            <div class="form-group">
                <label for="prefix">Prefix</label>
                <select name="prefix" id="prefix" disabled>
                    <option value="" disabled <?php echo $prefix == '' ? 'selected' : ''; ?>>Select Prefix</option>
                    <option value="Dr." <?php echo $prefix == 'Dr.' ? 'selected' : ''; ?>>Dr.</option>
                    <option value="Prof." <?php echo $prefix == 'Prof.' ? 'selected' : ''; ?>>Prof.</option>
                    <option value="Rev." <?php echo $prefix == 'Rev.' ? 'selected' : ''; ?>>Rev.</option>
                    <option value="None" <?php echo $prefix == 'None' ? 'selected' : ''; ?>>None</option>
                </select>
            </div>
            <div class="form-group">
                <label for="firstname">First Name</label>
                <input type="text" name="firstname" id="firstname" placeholder="First Name" value="<?php echo $firstname; ?>" readonly>
            </div>
            <div class="form-group">
                <label for="lastname">Last Name</label>
                <input type="text" id="lastname" name="lastname" value="<?php echo $lastname; ?>" readonly>
            </div>
            <div class="form-group">
                <label for="middlename">Middle Initial</label>
                <input type="text" name="middlename" id="middlename" placeholder="Middle Initial" value="<?php echo $middlename; ?>" maxlength="2" pattern="[A-Z]\." title="Please enter a single capital letter followed by a period" readonly>
            </div>
            <div class="form-group">
                <label for="suffix">Suffix</label>
                <input type="text" name="suffix" id="suffix" placeholder="Suffix" value="<?php echo $suffix; ?>" readonly>
            </div>
            <div class="form-group">
                <label for="gmail">Email</label>
                <input type="email" id="gmail" name="gmail" value="<?php echo $gmail; ?>" readonly>
            </div>
            <div class="form-group">
                <label for="gender">Gender</label>
                <select id="gender" name="gender" disabled>
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
                <input type="text" id="designation" name="designation" value="<?php echo $designation; ?>" readonly>
            </div>
            <div class="form-group">
                <label for="position">Academic Rank</label>
                <select name="position" id="position" disabled>
                    <option value="" class="parent-option" disabled style="background-color: #d3d3d3;" <?php echo $position == '' ? 'selected' : ''; ?>>Select Position</option>
                    <option value="instructor-1" class="instructors-option" <?php echo $position == 'instructor-1' ? 'selected' : ''; ?>>Instructor 1</option>
                    <option value="instructor-2" class="instructors-option" <?php echo $position == 'instructor-2' ? 'selected' : ''; ?>>Instructor 2</option>
                    <option value="instructor-3" class="instructors-option" <?php echo $position == 'instructor-3' ? 'selected' : ''; ?>>Instructor 3</option>
                    <option value="assistant-professor-1" class="instructors-option" <?php echo $position == 'assistant-professor-1' ? 'selected' : ''; ?>>Assistant Professor 1</option>
                    <option value="assistant-professor-2" class="instructors-option" <?php echo $position == 'assistant-professor-2' ? 'selected' : ''; ?>>Assistant Professor 2</option>
                    <option value="assistant-professor-3" class="instructors-option" <?php echo $position == 'assistant-professor-3' ? 'selected' : ''; ?>>Assistant Professor 3</option>
                    <option value="assistant-professor-4" class="instructors-option" <?php echo $position == 'assistant-professor-4' ? 'selected' : ''; ?>>Assistant Professor 4</option>
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
        </form>
    </div>
</div>

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
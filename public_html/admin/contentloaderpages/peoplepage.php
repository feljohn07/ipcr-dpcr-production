<?php

// PHP code for fetching and displaying users with images stored in the database
include '../../dbconnections/config.php'; // Replace with the correct path to your database connection file

// Query to count the number of users for each role: VPAA, President, Office Head, IPCR
$countQuery = "SELECT 
    SUM(CASE WHEN Role = 'VPAAQA' THEN 1 ELSE 0 END) AS vpaa_count,
    SUM(CASE WHEN Role = 'College President' THEN 1 ELSE 0 END) AS president_count,
    SUM(CASE WHEN Role = 'Office Head' THEN 1 ELSE 0 END) AS office_head_count,
    SUM(CASE WHEN Role = 'IPCR' THEN 1 ELSE 0 END) AS ipcr_count
FROM usersinfo";

// Execute the count query
$countResult = $conn->query($countQuery);

// Check if the count query returned results
if ($countResult && $countResult->num_rows > 0) {
    // Fetch the results
    $row = $countResult->fetch_assoc();
    $vpaaCount = $row['vpaa_count'];
    $presidentCount = $row['president_count'];
    $officeHeadCount = $row['office_head_count'];
    $ipcrCount = $row['ipcr_count'];
} else {
    echo "No data found for counts.";
}

// Query to fetch users
$sql = "SELECT pkid, idnumber, lastname, firstname, college, Role, picture FROM usersinfo ORDER BY lastname ASC";

$result = $conn->query($sql);
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User List</title>
    <style>
        /* Add styles for table, button, and modal */
        /* Styles for the user counts display */
.user-counts {
    margin: 20px 0;
    padding: 20px;
    background-color: #f4f4f4;
    border: 1px solid #ddd;
    border-radius: 12px;
    box-shadow: 0px 6px 12px rgba(0,0,0,0.1);
    display: flex;
    justify-content: space-between; /* Distribute items evenly */
    align-items: center; /* Center items vertically */
}

.user-counts .count-item {
    display: flex;
    align-items: center;
    gap: 10px;
    flex: 1; /* Allow items to grow and shrink */
    max-width: 200px; /* Set a max width to keep items compact */
    text-align: center; /* Center text inside items */
}

.user-counts .count-item img {
    width: 24px;
    height: 24px;
    border-radius: 50%;
}

.user-counts p {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: #333;
}

.user-counts p strong {
    color: #007BFF; /* Blue color for labels */
}

.user-counts p span {
    color: #555; /* Gray color for counts */
    font-weight: 500;
}

        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f4f4f4;
        }
        img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
        }
        .btn {
            background-color: #4CAF50;
            color: white;
            padding: 5px 10px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
            margin: 4px 2px;
            cursor: pointer;
            border: none;
            border-radius: 4px;
        }
        .btn-edit {
            background-color: #008CBA; /* Blue */
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgb(0,0,0);
            background-color: rgba(0,0,0,0.4);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 60%; /* Adjust the width as needed */
            max-width: 600px; /* Set a maximum width */
            border-radius: 8px; /* Add rounded corners */
            box-shadow: 0px 4px 8px rgba(0,0,0,0.2); /* Add shadow */
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }
        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        /* Styles for the edit form inside the modal */
        #editForm {
            display: flex;
            flex-direction: column;
        }
        #editForm label {
            font-weight: bold;
            margin-bottom: 5px;
        }
        #editForm input[type="text"] {
            padding: 8px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        #editForm input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        #editForm input[type="submit"]:hover {
            background-color: #45a049;
        }

        /* Container for scrollable table */
        .table-container {
            max-height: 410px; /* Set a fixed height */
            overflow-y: auto; /* Enable vertical scrolling */
        }
    </style>
</head>
<body>

    <!-- Display the user counts -->
<div class="user-counts">
<div class="count-item">
        <p><strong>President :</strong> <span><?php echo $presidentCount; ?></span></p>
    </div>
    <div class="count-item">
        <p><strong>VPAA :</strong> <span><?php echo $vpaaCount; ?></span></p>
    </div>
    <div class="count-item">
        <p><strong>Office Head :</strong> <span><?php echo $officeHeadCount; ?></span></p>
    </div>
    <div class="count-item">
        <p><strong>IPCR :</strong> <span><?php echo $ipcrCount; ?></span></p>
    </div>
</div>

<!-- Search Form -->
<div class="search-container">
    <form id="searchForm">
        <input type="text" id="searchInput" name="search" placeholder="Search by first name, last name, or college">
        <button type="submit" class="btn">Search</button>
    </form>
</div>

    <!-- Table Container -->
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Picture</th>
                    <th>ID Number</th>
                    <th>Last Name</th>
                    <th>First Name</th>
                    <th>College</th>
                    <th>Role</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="userTableBody">
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo '<tr>';
                        echo '<td>';
                        if (!empty($row['picture'])) {
                            echo '<img src="data:image/jpeg;base64,' . base64_encode($row['picture']) . '" alt="Picture">';
                        } else {
                            echo 'No picture uploaded';
                        }
                        echo '</td>';
                        echo '<input type="hidden" value="' . htmlspecialchars($row['pkid'], ENT_QUOTES, 'UTF-8') . '">';
                        
                        // Check if the role is "Office Head" and format the ID number
                        $idnumber = htmlspecialchars($row['idnumber'], ENT_QUOTES, 'UTF-8');
                        if ($row['Role'] === 'Office Head') {
                            // Use regex to extract the ID number from parentheses
                            if (preg_match('/\((.*?)\)/', $idnumber, $matches)) {
                                $idnumber = $matches[1]; // Get the content inside the parentheses
                            }
                        }
                        
                        echo '<td>' . $idnumber . '</td>';
                        echo '<td>' . htmlspecialchars($row['lastname'], ENT_QUOTES, 'UTF-8') . '</td>';
                        echo '<td>' . htmlspecialchars($row['firstname'], ENT_QUOTES, 'UTF-8') . '</td>';
                        echo '<td>' . htmlspecialchars($row['college'], ENT_QUOTES, 'UTF-8') . '</td>';
                        echo '<td>' . htmlspecialchars($row['Role'], ENT_QUOTES, 'UTF-8') . '</td>';
                        echo '<td>';
                        echo '<button class="btn btn-edit" onclick="openEditModal(\'' . htmlspecialchars($row['pkid'], ENT_QUOTES, 'UTF-8') . '\', \'' . htmlspecialchars($row['idnumber'], ENT_QUOTES, 'UTF-8') . '\', \'' . htmlspecialchars($row['lastname'], ENT_QUOTES, 'UTF-8') . '\', \'' . htmlspecialchars($row['firstname'], ENT_QUOTES, 'UTF-8') . '\', \'' . htmlspecialchars($row['college'], ENT_QUOTES, 'UTF-8') . '\', \'' . htmlspecialchars($row['Role'], ENT_QUOTES, 'UTF-8') . '\')">Edit</button>';
                        echo ' <button class="btn btn-delete" onclick="deleteUser (\'' . htmlspecialchars($row['idnumber'], ENT_QUOTES, 'UTF-8') . '\')">Delete</button>';
                        echo '</td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="7">No users found</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeEditModal()">&times;</span>
        <h2>Edit User</h2>
        <form id="editForm">
            <input type="hidden" id="pkid" name="pkid"> <!-- Hidden field for pkid -->

            <label for="userId">ID Number:</label>
            <input type="text" id="userId" name="idnumber" required>
            <br><br>
            <label for="lastname">Last Name:</label>
            <input type="text" id="lastname" name="lastname">
            <br><br>
            <label for="firstname">First Name:</label>
            <input type="text" id="firstname" name="firstname">
            <br><br>
            <label for="college">College:</label>
            <select id="college" name="college">
                <option value="COLLEGE OF COMPUTING AND INFORMATION SCIENCES">CCIS</option>
                <option value="COLLEGE OF ENGINEERING AND INDUSTRIAL TECHNOLOGY">CEIT</option>
                <option value="COLLEGE OF TEACHER EDUCATION">CTE</option>
                <option value="COLLEGE OF ARTS AND SCIENCES">CAS</option>
                <option value="COLLEGE OF AGRICULTURE">CA</option>
                <option value="COLLEGE OF BUSINESS ADMINISTRATION">CBA</option>
            </select>
            <br><br>
            <label for="role">Role:</label>
            <select id="role" name="role" required>
                <option value="VPAAQA">VPAAQA</option>
                <option value="College President">College President</option>
                <option value="Office Head">Office Head</option>
                <option value="IPCR">IPCR</option>
                <option value="Immediate Supervisor">Immediate Supervisor</option>
            </select>
            <br><br>
            <input type="submit" value="Save Changes">
        </form>
    </div>
</div>

<script>
    function openEditModal(pkid, idnumber, lastname, firstname, college, role) {
        document.getElementById('pkid').value = pkid; // Set the pkid
        document.getElementById('userId').value = idnumber; // Set the ID number
        document.getElementById('lastname').value = lastname; // Set the last name
        document.getElementById('firstname').value = firstname; // Set the first name
        document.getElementById('college').value = college; // Set the college
        document.getElementById('role').value = role; // Set the role

        toggleCollegeDropdown(role); // Check and toggle college dropdown

        document.getElementById('editModal').style.display = 'block';
    }

    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
    }

    // Disable or enable the college dropdown based on role
    function toggleCollegeDropdown(role) {
        const collegeDropdown = document.getElementById('college');
        const rolesToDisable = ['VPAAQA', 'College President', 'Immediate Supervisor'];
        if (rolesToDisable.includes(role)) {
            collegeDropdown.disabled = true;
        } else {
            collegeDropdown.disabled = false;
        }
    }

    // Listen for changes in the role dropdown
    document.getElementById('role').addEventListener('change', function () {
        toggleCollegeDropdown(this.value);
    });

    // Handle form submission
    document.getElementById('editForm').addEventListener('submit', function (event) {
        event.preventDefault();

        var formData = new FormData(this);
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'functions/edit_user_action.php', true);
        xhr.onload = function () {
            if (xhr.status === 200) {
                const response = xhr.responseText;
                if (response.includes("already exists")) {
                    alert(response); // Show the error message
                } else {
                    alert('User updated successfully!');
                    location.reload();
                }
            } else {
                alert('An error occurred while updating the user.');
            }
        };
        xhr.send(formData);
    });



                // Handle search form submission
                document.getElementById('searchForm').addEventListener('submit', function(event) {
                event.preventDefault();

                var searchQuery = document.getElementById('searchInput').value;

                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'functions/search_users.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function () {
                    if (xhr.status === 200) {
                        document.getElementById('userTableBody').innerHTML = xhr.responseText;
                    } else {
                        alert('An error occurred while searching for users.');
                    }
                };
                xhr.send('search=' + encodeURIComponent(searchQuery));
            });

        function deleteUser(id) {
            if (confirm('Are you sure you want to delete this user?')) {
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'functions/delete_user_action.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        alert('User deleted successfully!');
                        location.reload();
                    } else {
                        alert('An error occurred while deleting the user.');
                    }
                };
                xhr.send('idnumber=' + encodeURIComponent(id));
            }
        }

        window.onclick = function(event) {
            if (event.target === document.getElementById('editModal')) {
                closeEditModal();
            }
        }
    </script>
</body>
</html>
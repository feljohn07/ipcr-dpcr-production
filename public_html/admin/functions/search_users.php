<?php
// Include the database connection file
include '../../dbconnections/config.php'; // Replace with the correct path to your database connection file

$search = isset($_POST['search']) ? htmlspecialchars($_POST['search'], ENT_QUOTES, 'UTF-8') : '';

$sql = "SELECT pkid, picture, idnumber, lastname, firstname, college, Role 
        FROM usersinfo 
        WHERE firstname LIKE ? OR lastname LIKE ? OR college LIKE ? OR idnumber LIKE ?
        ORDER BY lastname ASC";

$likeSearch = "%" . $search . "%";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}

// Bind the parameters, now including the idnumber
$stmt->bind_param("ssss", $likeSearch, $likeSearch, $likeSearch, $likeSearch);
$stmt->execute();
$result = $stmt->get_result();

if ($result === false) {
    die("Get result failed: " . $stmt->error);
}

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

$stmt->close();
$conn->close();
?>
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
<?php
include '../../dbconnections/config.php'; // Adjust the path as needed

// Handle form submission to save or update designation
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $designation_id = isset($_POST['designation_id']) ? intval($_POST['designation_id']) : null;
    $designation = trim($_POST['designation']);

    if (!empty($designation)) {
        if ($designation_id) {
            // Update existing designation
            $stmt = $conn->prepare("UPDATE designations SET designation = ? WHERE id = ?");
            $stmt->bind_param("si", $designation, $designation_id);
            $message = "Designation updated successfully!";
        } else {
            // Insert new designation
            $stmt = $conn->prepare("INSERT INTO designations (designation) VALUES (?)");
            $stmt->bind_param("s", $designation);
            $message = "Designation saved successfully!";
        }

        if ($stmt->execute()) {
            echo "<script>alert('$message'); window.location.href='../admindash.php';</script>";
        } else {
            echo "<script>alert('Error saving designation.'); window.location.href='../admindash.php';</script>";
        }

        $stmt->close();
    } else {
        echo "<script>alert('Designation cannot be empty.'); window.location.href='../admindash.php';</script>";
    }
}

// Handle deletion of designation
if (isset($_GET['delete'])) {
    $designation_id = intval($_GET['delete']);

    $stmt = $conn->prepare("DELETE FROM designations WHERE id = ?");
    $stmt->bind_param("i", $designation_id);

    if ($stmt->execute()) {
        echo "<script>alert('Designation deleted successfully!'); window.location.href='../admindash.php';</script>";
    } else {
        echo "<script>alert('Error deleting designation.'); window.location.href='../admindash.php';</script>";
    }

    $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Designation Input</title>
    <style>
        body { font-family: Arial, sans-serif; }
        h2 { color: #333; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f4f4f4;
            color: #333;
            text-align: center; 
        }
        .edit, .delete {
            padding: 5px 10px;
            cursor: pointer;
            border: none;
        }
        .edit {
            background-color: #4CAF50;
            color: white;
        }
        .delete {
            background-color: #f44336;
            color: white;
        }
    </style>
    <script>
        function editDesignation(id, designation) {
            document.getElementById('designation_id').value = id; // Set hidden input for ID
            document.getElementById('designation').value = designation; // Set text input for designation
        }
    </script>
</head>
<body>
    <h2>Input Designation</h2>
    <form action="contentloaderpages/designation.php" method="post">
        <input type="hidden" id="designation_id" name="designation_id"> <!-- Hidden field for editing -->
        <label for="designation">Designation:</label>
        <input type="text" id="designation" name="designation" required>
        <button type="submit">Save</button>
    </form>

    <h2>List of Designations</h2>
    <div id="designationList">
        <table>
            <thead>
                <tr>
                    <th>Designation</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Fetch and display designations
                $query = "SELECT * FROM designations ORDER BY id DESC";
                $result = $conn->query($query);

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>
                                <td>" . htmlspecialchars($row['designation']) . "</td>
                                <td>
                                    <button class='edit' onclick=\"editDesignation('" . $row['id'] . "', '" . htmlspecialchars($row['designation']) . "')\">Edit</button>
                                    <a href='contentloaderpages/designation.php?delete=" . $row['id'] . "' onclick=\"return confirm('Are you sure you want to delete this designation?');\">
                                        <button class='delete'>Delete</button>
                                    </a>
                                </td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='2'>No designations found.</td></tr>";
                }

                $conn->close();
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>

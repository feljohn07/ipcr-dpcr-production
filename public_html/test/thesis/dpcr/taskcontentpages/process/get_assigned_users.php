<?php
// Connect to the database
include '../../../dbconnections/config.php'; // Database connection

// Get the task ID, task type, and semester ID from the URL parameters
$taskId = $_POST['idoftask'];
$taskType = $_POST['task_type'];
$semesterId = $_POST['semester_id'];

// Use prepared statements to prevent SQL injection
$query = $conn->prepare("SELECT id, firstname, lastname, target, status 
                        FROM task_assignments 
                        WHERE idoftask = ? AND task_type = ? AND semester_id = ?");
$query->bind_param('isi', $taskId, $taskType, $semesterId);
$query->execute();
$result = $query->get_result();

if ($result->num_rows > 0) {
  echo '<div class="table-container">';
  echo '<table class="user-table">';
  echo '<thead><tr><th>First Name</th><th>Last Name</th><th>Target</th><th>Status</th><th>Action</th></tr></thead>';
  echo '<tbody>';
  while($row = $result->fetch_assoc()) {
    echo '<tr id="row_'.$row['id'].'">';
    echo '<td>' . htmlspecialchars($row['firstname']) . '</td>';
    echo '<td>' . htmlspecialchars($row['lastname']) . '</td>';
    
    // Editable Target field
    echo '<td><input type="text" id="target_'.$row['id'].'" value="'.htmlspecialchars($row['target']).'" disabled></td>';
    
    // Display the status
    echo '<td>' . htmlspecialchars($row['status']) . '</td>'; // Added status column
    
    // Action buttons for Edit, Save, and Delete
    echo '<td>';
    
    echo '<button class="edit-btn" onclick="enableEdit(' . $row['id'] . ')">Edit</button>';
    echo '<button class="save-btn" onclick="saveEdit(' . $row['id'] . ')" id="saveBtn_'.$row['id'].'" style="display:none;">Save</button>';
    
    // Check the status to determine if the delete button should be enabled or disabled
    if ($row['status'] === 'approved') {
        echo '<button class="delete-btn" disabled>Cannot Delete</button>'; // Disabled button if approved
    } else {
        echo '<button class="delete-btn" onclick="deleteUser  (' . $row['id'] . ')">Delete</button>'; // Enabled for pending and declined
    }
    

    echo '</td>';
    
    echo '</tr>';
}
  echo '</tbody></table>';
  echo '</div>';
} else {
  echo "<p class='no-users'>No assigned users found.</p>";
}

// Close the database connection
$query->close();
$conn->close();
?>
<!-- CSS for better styling -->
<style>
  .table-container {
    max-height: 400px; /* Set a maximum height for the scrollable area */
    overflow-y: auto; /* Enable vertical scrolling */
    margin: 20px 0; /* Spacing around the table */
    border: 1px solid #ddd; /* Add a border around the table */
    border-radius: 5px; /* Round the corners of the border */
  }
  
  .user-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 16px;
    text-align: left;
  }

  .user-table th, .user-table td {
    padding: 12px;
    border: 1px solid #ddd;
  }

  .user-table th {
    background-color: #f2f2f2;
    position: sticky; /* Make the header sticky */
    top: 0; /* Stick to the top */
    z-index: 10; /* Ensure it is above other elements */
  }

  .edit-btn, .save-btn, .delete-btn {
    padding: 5px 10px;
    margin-right: 5px;
    border: none;
    color: #fff;
    cursor: pointer;
    border-radius: 5px;
  }

  .edit-btn { background-color: #4CAF50; }
  .save-btn { background-color: #007BFF; }
  .delete-btn { background-color: #F44336; }

  .edit-btn:hover, .save-btn:hover, .delete-btn:hover {
    opacity: 0.8;
  }

  .no-users {
    color: #ff0000;
    text-align: center;
  }
</style>
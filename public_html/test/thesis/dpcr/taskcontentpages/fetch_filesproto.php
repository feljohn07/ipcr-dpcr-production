<?php
session_start();
include '../../dbconnections/config.php'; // Update path as needed

// Retrieve task_id, owner_id, semester_id, and task_type from POST request
$task_id = $_POST['task_id'];
$owner_id = $_POST['owner_id'];
$semester_id = $_POST['semester_id']; // Get semester_id
$task_type = $_POST['task_type']; // Get task_type

// Prepare a statement to fetch files and additional details from task_attachments and task_assignments
// Prepare a statement to fetch files and additional details from task_attachments and task_assignments
// Prepare a statement to fetch files and additional details from task_attachments and task_assignments
$query = "
    SELECT 
        tas.quality, 
        tas.efficiency,
        tas.timeliness, 
        tas.deansmessage, 
        tas.firstname, 
        tas.lastname, 
        ta.file_name
    FROM task_attachments ta
    JOIN task_assignments tas 
        ON tas.idoftask = ta.id_of_task
    WHERE ta.id_of_task = ? 
      AND ta.user_idnumber = ?  -- Correctly using user_idnumber for owner_id
      AND ta.id_of_semester = ? -- Matching semester_id
      AND tas.task_type = ? -- Matching task_type
";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("ssss", $task_id, $owner_id, $semester_id, $task_type); // Assuming user_idnumber is a string
$stmt->execute();
$result = $stmt->get_result();

// Fetch the result and group file names
$files = [];
$quality = '';
$efficiency = '';
$timeliness = '';
$deansmessage = '';

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Store unique file names and create clickable links
        $file_name = htmlspecialchars($row['file_name']);
        $files[$file_name] = '<a href="view_file.php?task_id=' . htmlspecialchars($task_id) . '&file_name=' . $file_name . '" target="_blank">' . $file_name . '</a>'; // Create clickable link

        // Capture quality, efficiency, timeliness, and dean's message
        $quality = htmlspecialchars($row['quality']);
        $efficiency = htmlspecialchars($row['efficiency']);
        $timeliness = htmlspecialchars($row['timeliness']);
        $deansmessage = htmlspecialchars($row['deansmessage']);
    }

    echo '<style>
            table {
                width: 100%;
                border-collapse: collapse;
                margin: 20px 0;
            }
            th, td {
                border: 1px solid #ddd;
                padding: 8px;
                text-align: left;
            }
            th {
                background-color: #f2f2f2;
            }
            tr:hover {
                background-color: #f1f1f1;
            }
          </style>';

    echo '<table>';
    echo '<tr>
            <th>Files</th>
            <th>Quality</th>
            <th>Efficiency</th>
            <th>Timeliness</th>
            <th>Dean\'s Message</th>
          </tr>';

    // Join all unique file links into a single string
    $fileLinksString = implode('<br>', $files);

    // Output the table row with aggregated file names and metrics
    echo '<tr>
            <td>' . $fileLinksString . '</td>
             <td>
                <span class="quality-display">' . $quality . '</span>
                <button class="edit-quality" onclick="editQuality(this)" style="float: right;">Edit</button>
            </td>
            <td>
                <span class="efficiency-display">' . $efficiency . '</span>
                <button class="edit-efficiency" onclick="editEfficiency(this)" style="float: right;">Edit</button>
            </td>
            <td>
                <span class="timeliness-display">' . $timeliness . '</span>
                <button class="edit-timeliness" onclick="editTimeliness(this)" style="float: right;">Edit</button>
            </td>
            <td>
                <span class="deansmessage-display">' . $deansmessage . '</span>
                <button class="edit-deansmessage" onclick="editDeansMessage(this)" style="float: right;">Edit</button>
            </td>
          </tr>';
    
    echo '</table>';
} else {
    echo 'No matching records found.';
}

$stmt->close();
$conn->close();
?>

<script>
function editQuality(button) {
    const row = button.closest('tr');
    const qualityDisplay = row.querySelector('.quality-display');
    const currentQuality = qualityDisplay.textContent;
    let newQuality;

    // Prompt user for new quality value
    do {
        newQuality = prompt("Enter new quality value (0 - 5):", currentQuality);

        // If user cancels, break the loop
        if (newQuality === null) {
            return;
        }

        // Parse the input to an integer
        newQuality = parseInt(newQuality);

        // Check if the input is a valid number and within the range
        if (isNaN(newQuality) || newQuality < 0 || newQuality > 5) {
            alert("Please enter a valid quality value between 0 and 5.");
        }
    } while (isNaN(newQuality) || newQuality < 0 || newQuality > 5);

    // Update the display with the new value
    qualityDisplay.textContent = newQuality;

    // Send the updated value to the server via AJAX
    const xhr = new XMLHttpRequest();
    xhr.open("POST", "update_ipcr_quality.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
            const response = JSON.parse(xhr.responseText);
            if (!response.success) {
                alert("Error updating quality: " + response.error);
            }
        }
    };
    xhr.send("task_id=<?php echo htmlspecialchars($task_id); ?>&owner_id=<?php echo htmlspecialchars($owner_id); ?>&semester_id=<?php echo htmlspecialchars($semester_id); ?>&task_type=<?php echo htmlspecialchars($task_type); ?>&quality=" + encodeURIComponent(newQuality));
}

function editEfficiency(button) {
    const row = button.closest('tr');
    const efficiencyDisplay = row.querySelector('.efficiency-display');
    const currentEfficiency = efficiencyDisplay.textContent;
    let newEfficiency;

    // Prompt user for new efficiency value
    do {
        newEfficiency = prompt("Enter new efficiency value (0 - 5):", currentEfficiency);

        // If user cancels, break the loop
        if (newEfficiency === null) {
            return;
        }

        // Parse the input to an integer
        newEfficiency = parseInt(newEfficiency);

        // Check if the input is a valid number and within the range
        if (isNaN(newEfficiency) || newEfficiency < 0 || newEfficiency > 5) {
            alert("Please enter a valid efficiency value between 0 and 5.");
        }
    } while (isNaN(newEfficiency) || newEfficiency < 0 || newEfficiency > 5);

    // Update the display with the new value
    efficiencyDisplay.textContent = newEfficiency;

    // Send the updated value to the server via AJAX
    const xhr = new XMLHttpRequest();
    xhr.open("POST", "update_ipcr_efficiency.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
            const response = JSON.parse(xhr.responseText);
            if (!response.success) {
                alert("Error updating efficiency: " + response.error);
            }
        }
    };
    xhr.send("task_id=<?php echo htmlspecialchars($task_id); ?>&owner_id=<?php echo htmlspecialchars($owner_id); ?>&semester_id=<?php echo htmlspecialchars($semester_id); ?>&task_type=<?php echo htmlspecialchars($task_type); ?>&efficiency=" + encodeURIComponent(newEfficiency));
}

function editTimeliness(button) {
    const row = button.closest('tr');
    const timelinessDisplay = row.querySelector('.timeliness-display');
    const currentTimeliness = timelinessDisplay.textContent;
    let newTimeliness;

    // Prompt user for new timeliness value
    do {
        newTimeliness = prompt("Enter new timeliness value (0 - 5):", currentTimeliness);

        // If user cancels, break the loop
        if (newTimeliness === null) {
            return;
        }

        // Parse the input to an integer
        newTimeliness = parseInt(newTimeliness);

        // Check if the input is a valid number and within the range
        if (isNaN(newTimeliness) || newTimeliness < 0 || newTimeliness > 5) {
            alert("Please enter a valid timeliness value between 0 and 5.");
        }
    } while (isNaN(newTimeliness) || newTimeliness < 0 || newTimeliness > 5);

    // Update the display with the new value
    timelinessDisplay.textContent = newTimeliness;

    // Send the updated value to the server via AJAX
    const xhr = new XMLHttpRequest();
    xhr.open("POST", "update_ipcr_timeliness.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
            const response = JSON.parse(xhr.responseText);
            if (!response.success) {
                alert("Error updating timeliness: " + response.error);
            }
        }
    };
    xhr.send("task_id=<?php echo htmlspecialchars($task_id); ?>&owner_id=<?php echo htmlspecialchars($owner_id); ?>&semester_id=<?php echo htmlspecialchars($semester_id); ?>&task_type=<?php echo htmlspecialchars($task_type); ?>&timeliness=" + encodeURIComponent(newTimeliness));
}

function editDeansMessage(button) {
    const row = button.closest('tr');
    const deansMessageDisplay = row.querySelector('.deansmessage-display');
    const currentDeansMessage = deansMessageDisplay.textContent;
    let newDeansMessage = prompt("Enter new dean's message:", currentDeansMessage);

    // If user cancels, do nothing
    if (newDeansMessage === null) {
        return;
    }

    // Update the display with the new message
    deansMessageDisplay.textContent = newDeansMessage;

    // Send the updated value to the server via AJAX
    const xhr = new XMLHttpRequest();
    xhr.open("POST", "update_ipcr_deansmessage.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
            const response = JSON.parse(xhr.responseText);
            if (!response.success) {
                alert("Error updating dean's message: " + response.error);
            }
        }
    };
    xhr.send("task_id=<?php echo htmlspecialchars($task_id); ?>&owner_id=<?php echo htmlspecialchars($owner_id); ?>&semester_id=<?php echo htmlspecialchars($semester_id); ?>&task_type=<?php echo htmlspecialchars($task_type); ?>&deansmessage=" + encodeURIComponent(newDeansMessage));
}
</script>

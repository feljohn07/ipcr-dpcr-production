<?php
// Database connection settings
$host = 'localhost'; // Your database host
$username = 'root'; // Your database username
$password = ''; // Your database password
$database = '04_task'; // Your database name

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$idnumber = '03352'; // Replace with the actual ID number
$semester_id = '231'; // Replace with the actual semester ID

$query = "
    SELECT 
        ta.newtask_type,
        st.task_name AS strategic_task_name,
        st.description AS strategic_description,
        st.documents_req AS strategic_documents_req,
        st.documents_uploaded AS strategic_documents_uploaded,
        st.quality AS strategic_quality,
        st.efficiency AS strategic_efficiency,
        st.timeliness AS strategic_timeliness,
        st.average AS strategic_average,
        ct.task_name AS core_task_name,
        ct.description AS core_description,
        ct.documents_req AS core_documents_req,
        ct.documents_uploaded AS core_documents_uploaded,
        ct.quality AS core_quality,
        ct.efficiency AS core_efficiency,
        ct.timeliness AS core_timeliness,
        ct.average AS core_average,
        spt.task_name AS support_task_name,
        spt.description AS support_description,
        spt.documents_req AS support_documents_req,
        spt.documents_uploaded AS support_documents_uploaded,
        spt.quality AS support_quality,
        spt.efficiency AS support_efficiency,
        spt.timeliness AS support_timeliness,
        spt.average AS support_average
    FROM 
        task_assignments ta
    LEFT JOIN 
        strategic_tasks st ON ta.idoftask = st.task_id AND ta.semester_id = st.semester_id
    LEFT JOIN 
        core_tasks ct ON ta.idoftask = ct.task_id AND ta.semester_id = ct.semester_id
    LEFT JOIN 
        support_tasks spt ON ta.idoftask = spt.task_id AND ta.semester_id = spt.semester_id
    WHERE 
        ta.assignuser = ? 
        AND ta.semester_id = ?;
";

$stmt = $conn->prepare($query);
$stmt->bind_param('ss', $idnumber, $semester_id); // Assuming both are strings
$stmt->execute();
$result = $stmt->get_result();

$grouped_tasks = [
    'strategic' => [],
    'core' => [],
    'support' => []
];

while ($row = $result->fetch_assoc()) {
    $newtask_type = $row['newtask_type'];
    
    // Check if newtask_type is one of the expected types
    if (isset($grouped_tasks[$newtask_type])) {
        // Add the task data to the appropriate category
        $grouped_tasks[$newtask_type][] = [
            'strategic_task_name' => $row['strategic_task_name'],
            'strategic_description' => $row['strategic_description'],
            'strategic_documents_req' => $row['strategic_documents_req'],
            'strategic_documents_uploaded' => $row['strategic_documents_uploaded'],
            'strategic_quality' => $row['strategic_quality'],
            'strategic_efficiency' => $row['strategic_efficiency'],
            'strategic_timeliness' => $row['strategic_timeliness'],
            'strategic_average' => $row['strategic_average'],
            'core_task_name' => $row['core_task_name'],
            'core_description' => $row['core_description'],
            'core_documents_req' => $row['core_documents_req'],
            'core_documents_uploaded' => $row['core_documents_uploaded'],
            'core_quality' => $row['core_quality'],
            'core_efficiency' => $row['core_efficiency'],
            'core_timeliness' => $row['core_timeliness'],
            'core_average' => $row['core_average'],
            'support_task_name' => $row['support_task_name'],
            'support_description' => $row['support_description'],
            'support_documents_req' => $row['support_documents_req'],
            'support_documents_uploaded' => $row['support_documents_uploaded'],
            'support_quality' => $row['support_quality'],
            'support_efficiency' => $row['support_efficiency'],
            'support_timeliness' => $row['support_timeliness'],
            'support_average' => $row['support_average']
        ];
    }
}

// Now echo the grouped tasks
// Now echo the grouped tasks
foreach ($grouped_tasks as $task_type => $tasks) {
    if (!empty($tasks)) {
        echo "<h3>Task Type: " . ucfirst($task_type) . "</h3>";
        foreach ($tasks as $task) {
            // Display strategic tasks
            if ($task_type === 'strategic') {
                echo "<p><strong>Strategic Task Name:</strong> {$task['strategic_task_name']}</p>";
                echo "<p><strong>Description:</strong> {$task['strategic_description']}</p>";
                echo "<p><strong>Documents Required:</strong> {$task['strategic_documents_req']}</p>";
                echo "<p><strong>Documents Uploaded:</strong> {$task['strategic_documents_uploaded']}</p>";
                echo "<p><strong>Quality:</strong> {$task['strategic_quality']}</p>";
                echo "<p><strong>Efficiency:</strong> {$task['strategic_efficiency']}</p>";
                echo "<p><strong>Timeliness:</strong> {$task['strategic_timeliness']}</p>";
                echo "<p><strong>Average:</strong> {$task['strategic_average']}</p>";
                echo "<hr>";
            }
            // Display core tasks
            elseif ($task_type === 'core') {
                echo "<p><strong>Core Task Name:</strong> {$task['core_task_name']}</p>";
                echo "<p><strong>Description:</strong> {$task['core_description']}</p>";
                echo "<p><strong>Documents Required:</strong> {$task['core_documents_req']}</p>";
                echo "<p><strong>Documents Uploaded:</strong> {$task['core_documents_uploaded']}</p>";
                echo "<p><strong>Quality:</strong> {$task['core_quality']}</p>";
                echo "<p><strong>Efficiency:</strong> {$task['core_efficiency']}</p>";
                echo "<p><strong>Timeliness:</strong> {$task['core_timeliness']}</p>";
                echo "<p><strong>Average:</strong> {$task['core_average']}</p>";
                echo "<hr>";
            }
            // Display support tasks
            elseif ($task_type === 'support') {
                echo "<p><strong>Support Task Name:</strong> {$task['support_task_name']}</p>";
                echo "<p><strong>Description:</strong> {$task['support_description']}</p>";
                echo "<p><strong>Documents Required:</strong> {$task['support_documents_req']}</p>";
                echo "<p><strong>Documents Uploaded:</strong> {$task['support_documents_uploaded']}</p>";
                echo "<p><strong>Quality:</strong> {$task['support_quality']}</p>";
                echo "<p><strong>Efficiency:</strong> {$task['support_efficiency']}</p>";
                echo "<p><strong>Timeliness:</strong> {$task['support_timeliness']}</p>";
                echo "<p><strong>Average:</strong> {$task['support_average']}</p>";
                echo "<hr>";
            }
        }
    } else {
        // If no tasks are available, you may want to display a message
        echo "<p>No tasks available for this type.</p>";
    }
}

// Close the database connection
$conn->close();
?>

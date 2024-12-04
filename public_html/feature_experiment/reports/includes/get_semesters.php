<?php
include __DIR__ . '/../../../dbconnections/config.php';

function get_semesters($college)
{
    global $conn; // Make sure $conn is available inside the function

    $query = "SELECT DISTINCT semester_id, semester_name FROM semester_tasks WHERE college = ?";

    $query_statement = $conn->prepare($query);
    $query_statement->bind_param("s", $college);
    $query_statement->execute();

    $result = $query_statement->get_result();

    $semesters = [];

    // Fetch the results
    while ($row = $result->fetch_assoc()) {
        $semesters[] = $row;
    }

    return $semesters;
}


function get_latest_semester($college, $semesterId = null)
{
    global $conn; // Ensure $conn is available inside the function

    if($semesterId == null) {
        
        // SQL query to fetch the latest semester based on the created_at column
        $query = "
            SELECT semester_id, semester_name, created_at 
            FROM semester_tasks 
            WHERE college = ? 
            ORDER BY created_at DESC 
            LIMIT 1
        ";

        // Prepare and execute the statement
        $query_statement = $conn->prepare($query);
        $query_statement->bind_param("s", $college);


    } else {
            
        $query_by_id = "
            SELECT semester_id, semester_name, created_at 
            FROM semester_tasks 
            WHERE college = ? and semester_id = ?
            ORDER BY created_at DESC 
            LIMIT 1
        ";

        // Prepare and execute the statement
        $query_statement = $conn->prepare($query_by_id);
        $query_statement->bind_param("ss", $college, $semesterId);

    }

    $query_statement->execute();

    // Get the result
    $result = $query_statement->get_result();

    // Fetch and return the latest semester or null if no result
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    } else {
        return null;
    }
}

?>

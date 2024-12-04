<?php
include __DIR__ . '/../../../dbconnections/config.php';

function getUserSemesterData($userId, $college, $semesterId = null) {
    global $conn;

    // Base SQL query
    $query = "
        SELECT 
            u.idnumber,
            u.firstname, 
            u.lastname,
            u.picture,
            u.college,
            st.semester_id,
            st.semester_name,
            ipr.average_subtotal_strategic,
            ipr.average_subtotal_core,
            ipr.average_subtotal_support,
            ipr.final_average,
            ipr.final_rating
        FROM usersinfo AS u
        INNER JOIN semester_tasks AS st ON u.college = st.college  
        LEFT JOIN ipcr_performance_rating AS ipr ON u.idnumber = ipr.idnumber 
            AND st.semester_id = ipr.semester_id
        WHERE u.college = ? AND u.idnumber != ? AND u.role != 'Office Head'";

    // Add semester filter if $semesterId is provided
    if ($semesterId !== null) {
        $query .= " AND st.semester_id = ?";
    }

    $query .= " ORDER BY u.idnumber, st.semester_id";

    // Prepare the statement
    $stmt = $conn->prepare($query);

    // Bind parameters
    if ($semesterId !== null) {
        $stmt->bind_param("sss", $college, $userId, $semesterId);
    } else {
        $stmt->bind_param("ss", $college, $userId);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    // Initialize data array
    $data = [];

    // Check if there are results
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $idnumber = $row['idnumber'];
            $semester = $row['semester_name'];
            $semesterId = $row['semester_id'];

            // Only include users with valid performance ratings
            if ($row['average_subtotal_strategic'] !== null || 
                $row['average_subtotal_core'] !== null || 
                $row['average_subtotal_support'] !== null) {

                // Set user name and picture (base64 encode if picture exists)
                $data[$idnumber]['name'] = $row['firstname'] . ' ' . $row['lastname'];
                $data[$idnumber]['picture'] = $row['picture'] ? base64_encode($row['picture']) : null;

                // Initialize semesters array if not set
                if (!isset($data[$idnumber]['semesters'])) {
                    $data[$idnumber]['semesters'] = [];
                }

                // Create a unique key for each semester
                $semesterKey = $semester . '_' . $semesterId;

                // Assign weighted averages and final average
                $data[$idnumber]['semesters'][$semesterKey] = [
                    'strategic' => ($row['average_subtotal_strategic'] > 0) ? (float)$row['average_subtotal_strategic'] : null,
                    'core' => ($row['average_subtotal_core'] > 0) ? (float)$row['average_subtotal_core'] : null,
                    'support' => ($row['average_subtotal_support'] > 0) ? (float)$row['average_subtotal_support'] : null,
                    'final_average' => $row['final_average'],
                    'name' => $semester
                ];
            }
        }
    }

    // Return the data array
    return $data;
}

function get_user_ipcr_ratings($userId) {
    global $conn; // Ensure $conn is available in the function

    // SQL query to fetch IPCR performance ratings for a specific user
    $query = "SELECT * FROM `ipcr_performance_rating` WHERE idnumber = ?";

    // Prepare the statement
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return ["error" => "Failed to prepare statement: " . $conn->error];
    }

    // Bind parameters
    $stmt->bind_param("s", $userId);

    // Execute the query
    if (!$stmt->execute()) {
        return ["error" => "Failed to execute statement: " . $stmt->error];
    }

    // Get the result
    $result = $stmt->get_result();

    // Initialize an array to store the user's ratings
    $ratings = [];

    // Fetch data and add it to the ratings array
    while ($row = $result->fetch_assoc()) {
        $ratings[] = [
            "semester_id" => $row['semester_id'],
            "average_subtotal_strategic" => $row['average_subtotal_strategic'],
            "average_subtotal_core" => $row['average_subtotal_core'],
            "average_subtotal_support" => $row['average_subtotal_support'],
            "final_average" => $row['final_average'],
            "final_rating" => $row['final_rating']
        ];
    }

    // Return the ratings array
    return $ratings;

}
?>

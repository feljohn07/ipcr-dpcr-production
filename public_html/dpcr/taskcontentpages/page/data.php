<?php
// Include database connection
include '../../../dbconnections/config.php';

// Query to fetch instructor performance
$query = "
    SELECT 
        u.firstname,
        u.lastname,
        ipr.average_subtotal_strategic AS strategic,
        ipr.average_subtotal_core AS core,
        ipr.average_subtotal_support AS support,
        ipr.final_average
    FROM usersinfo AS u
    LEFT JOIN ipcr_performance_rating AS ipr ON u.idnumber = ipr.idnumber
    WHERE ipr.average_subtotal_strategic IS NOT NULL 
      OR ipr.average_subtotal_core IS NOT NULL 
      OR ipr.average_subtotal_support IS NOT NULL";

// Execute query
$result = $conn->query($query);

// Prepare data for the chart
$data = [];
$labels = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $labels[] = $row['firstname'] . ' ' . $row['lastname'];
        $data['strategic'][] = $row['strategic'];
        $data['core'][] = $row['core'];
        $data['support'][] = $row['support'];
        $data['final_average'][] = $row['final_average'];
    }
}

// Send data as JSON
echo json_encode(['labels' => $labels, 'datasets' => $data]);
?>

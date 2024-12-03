    <?php
    session_start();
    include '../../../dbconnections/config.php';

    $currentUserId = $_SESSION['idnumber'];
    $currentUserCollegeQuery = "SELECT college FROM usersinfo WHERE idnumber = ?";
    $stmt = $conn->prepare($currentUserCollegeQuery);
    $stmt->bind_param("s", $currentUserId);
    $stmt->execute();
    $currentUserCollegeResult = $stmt->get_result();
    $currentUserCollege = $currentUserCollegeResult->fetch_assoc()['college'];

    // SQL query to retrieve data from semester_tasks and ipcr_performance_rating
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
    INNER JOIN semester_tasks AS st ON u.college = st.college  -- Adjust this join as necessary
    LEFT JOIN ipcr_performance_rating AS ipr ON u.idnumber = ipr.idnumber 
        AND st.semester_id = ipr.semester_id
    WHERE u.college = ? AND u.idnumber != ? AND u.role != 'Office Head'
    ORDER BY u.idnumber, st.semester_id";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $currentUserCollege, $currentUserId);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $idnumber = $row['idnumber'];
            $semester = $row['semester_name'];
            $semesterId = $row['semester_id']; // Get the semester ID
        
            // Only include users who have valid performance ratings
            if ($row['average_subtotal_strategic'] !== null || 
                $row['average_subtotal_core'] !== null || 
                $row['average_subtotal_support'] !== null) {
                
                $data[$idnumber]['name'] = $row['firstname'] . ' ' . $row['lastname'];
                $data[$idnumber]['picture'] = $row['picture'] ? base64_encode($row['picture']) : null;
        
                // Initialize semesters array if not already set
                if (!isset($data[$idnumber]['semesters'])) {
                    $data[$idnumber]['semesters'] = [];
                }
        
                // Create a unique key for each semester using both name and ID
                $semesterKey = $semester . '_' . $semesterId;
        
                // Assign weighted averages; replace 0.0 values with null
                // Add final_average to the data
                $data[$idnumber]['semesters'][$semesterKey] = [
                    'strategic' => ($row['average_subtotal_strategic'] > 0) ? (float)$row['average_subtotal_strategic'] : null,
                    'core' => ($row['average_subtotal_core'] > 0) ? (float)$row['average_subtotal_core'] : null,
                    'support' => ($row['average_subtotal_support'] > 0) ? (float)$row['average_subtotal_support'] : null,
                    'final_average' => $row['final_average'],  // Store the final_average here
                    'name' => $semester
                ];
            }
        }
    }

// Function to get task names and their corresponding final ratings count
function getSemesterRatings($college, $conn) {
    $ratingsCount = [];

    // Query to get semester names and their corresponding semester IDs
    $taskNamesQuery = "SELECT semester_id, semester_name FROM semester_tasks WHERE college = ?";
    $stmt = $conn->prepare($taskNamesQuery);
    $stmt->bind_param("s", $college);
    $stmt->execute();
    $taskNamesResult = $stmt->get_result();

    if ($taskNamesResult && $taskNamesResult->num_rows > 0) {
        while ($row = $taskNamesResult->fetch_assoc()) {
            $semesterId = $row['semester_id'];
            $semesterName = $row['semester_name'];

            // Count the occurrences of final_rating for this semester_id
            $ratingCountQuery = "SELECT final_rating, COUNT(*) as count FROM ipcr_performance_rating WHERE semester_id = ? GROUP BY final_rating";
            $ratingStmt = $conn->prepare($ratingCountQuery);
            $ratingStmt->bind_param("s", $semesterId);
            $ratingStmt->execute();
            $ratingResult = $ratingStmt->get_result();

            // Initialize an array to hold counts for this semester
            $ratingsCount[$semesterName] = [
                'O' => 0,
                'VS' => 0,
                'S' => 0,
                'P' => 0,
                'U' => 0,
                'P' => 0
            ];

            // Populate the counts based on the final_rating values
            while ($ratingRow = $ratingResult->fetch_assoc()) {
                $ratingsCount[$semesterName][$ratingRow['final_rating']] = $ratingRow['count'];
            }
        }
    }

    return $ratingsCount;
}

/*
// Call the function and get the ratings count
$ratingsCount = getSemesterRatings($currentUserCollege, $conn);

// Output the results
if (!empty($ratingsCount)) {
    echo "<h2>Final Ratings Count for College '" . htmlspecialchars($currentUserCollege) . "':</h2>"; // Header added here
    foreach ($ratingsCount as $semesterName => $counts) {
        echo "<h3>Semester: " . htmlspecialchars($semesterName) . "</h3>"; // Use a smaller header for each semester
        foreach ($counts as $rating => $count) {
            echo "Rating '$rating': " . $count . "<br>";
        }
        echo "<br>";
    }
} else {
    echo "<h2>No ratings found for college '" . htmlspecialchars($currentUserCollege) . "'.</h2>"; // Header for no results
}
*/
$conn->close();
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
<style>
    .user-group {
        display: flex;
        align-items: center;
        margin: 20px;
        padding: 20px;
        background: #f7f9fc;
        border: 1px solid #e1e8ed;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        min-height: 300px;
    }
    .user-info {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        margin-right: 20px;
    }
    .user-picture {
        width: 250px;
        height: 170px;
        margin-bottom: 10px;
        border: 2px solid #c4cdd5;
        border-radius: 10px;
        background-color: #dfe6ec;
    }
    .no-image {
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 16px;
        color: #6b778c;
        text-align: center;
        width: 250px;
        height: 170px;
        background: #dfe6ec;
        border-radius: 10px;
    }
    h1 {
        font-size: 24px;
        color: #344563;
        margin: 10px  0;
    }
    h2 {
        color: #344563;
        font-size: 20px;
        margin-bottom: 15px;
    }
    .chart-container {
        flex: 2;
        padding-left: 20px;
        min-width: 600px;
        overflow-x: auto; /* Enable horizontal scrolling */
    }
    .chart-wrapper {
        min-width: 1000px; /* Adjust based on expected number of semesters */
    }
    .close-btn {
        position: fixed;
        top: 10px;
        right: 10px;
        background-color: #ff5c5c;
        color: white;
        border: none;
        border-radius: 5px;
        padding: 10px 15px;
        cursor: pointer;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        z-index: 1000;
    }
</style>

<script>
    function closeTab() {
        window.close(); // Attempt to close the current tab
    }
</script>

<button class="close-btn" onclick="closeTab()">Close This Tab</button>

<?php
if (!empty($data)) {
    foreach ($data as $idnumber => $user) {
        echo '<div class="user-group">';
        echo '<div class="user-info">';

        // Display the profile picture
        if ($user['picture']) {
            $profilePicture = 'data:image/jpeg;base64,' . $user['picture'];
            echo '<img src="' . $profilePicture . '" alt="Profile Picture" class="user-picture">';
        } else {
            echo '<div class="no-image">No image uploaded</div>';
        }

        echo '<h1>' . htmlspecialchars($user['name']) . '</h1>';
        echo '</div>'; // End of user-info

        // Prepare data for the chart
        $strategicData = [];
        $coreData = [];
        $supportData = [];
        $semesterLabels = [];
        $finalAverage = [];
        $finalRatings = []; // Initialize the final ratings array

        foreach ($user['semesters'] as $semesterKey => $taskAverages) {
            $semester = $taskAverages['name']; // Use the original name stored
            $semesterLabels[] = $semester; // Add semester name to labels
            $strategicData[] = $taskAverages['strategic'] ?? null;
            $coreData[] = $taskAverages['core'] ?? null;
            $supportData[] = $taskAverages['support'] ?? null;
            $finalAverage[] = $taskAverages['final_average'] ?? null;

            // Calculate ratings based on final_average
            if ($taskAverages['final_average'] !== null) {
                if ($taskAverages['final_average'] >= 4.20) {
                    $finalRatings[] = 'O'; // Poor
                } elseif ($taskAverages['final_average'] >= 3.40) {
                    $finalRatings[] = 'VS'; // Unsatisfactory
                } elseif ($taskAverages['final_average'] >= 2.60) {
                    $finalRatings[] = 'S'; // Satisfactory
                } elseif ($taskAverages['final_average'] >= 1.80) {
                    $finalRatings[] = 'U'; // Very Satisfactory
                } else {
                    $finalRatings[] = 'P'; // Outstanding
                }
            } else {
                $finalRatings[] = ''; // No rating if final_average is null
            }
        }


        echo '<div class="chart-container">';
        echo '<div class="chart-wrapper">';
        echo '<canvas id="chart_' . $idnumber . '" width="400" height="130"></canvas>';
        echo '</div>'; // End of chart-wrapper
        
        // Prepare the chart data
        echo '<script>
        var ctx = document.getElementById("chart_' . $idnumber . '").getContext("2d");
        var chart = new Chart(ctx, {
            type: "bar",
            data: {
                labels: ' . json_encode($semesterLabels) . ',
                datasets: [
                    {
                        label: "Strategic Average Sub-total",
                        data: ' . json_encode($strategicData) . ',
                        backgroundColor: "rgba(75, 192, 192, 0.6)",
                        borderColor: "rgba(75, 192, 192, 1)",
                        borderWidth: 1
                    },
                    {
                        label: "Core Average Sub-total",
                        data: ' . json_encode($coreData) . ',
                        backgroundColor: "rgba(153, 102, 255, 0.6)",
                        borderColor: "rgba(153, 102, 255, 1)",
                        borderWidth: 1
                    },
                    {
                        label: "Support Average Sub-total",
                        data: ' . json_encode($supportData) . ',
                        backgroundColor: "rgba(255, 159, 64, 0.6)",
                        borderColor: "rgba(255, 159, 64, 1)",
                        borderWidth: 1
                    },
                    {
                        label: "Final Average",
                        data: ' . json_encode($finalAverage) . ',
                        backgroundColor: "rgba(255, 99, 132, 0.6)", // You can change the color
                        borderColor: "rgba(255, 99, 132, 1)", // You can change the color
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true,
                        position: "top"
                    },
                   datalabels: {
                anchor: "center", // Center the label within the bar
                align: "center",  // Align the label to the center of the bar
                formatter: function(value, context) {
                    // Show the final rating inside the Final Average bar
                    if (context.dataset.label === "Final Average") {
                        return ' . json_encode($finalAverage) . '[ context.dataIndex] || ""; // Use the rating for the corresponding index
                    }
                    return value !== null ? value : "";
                },
                color: "black", // Change the color of the labels for visibility
                font: {
                    size: 12
                }
            }
        },
                scales: {
                    x: {
                        stacked: false // Ensure bars are NOT stacked
                    },
                    y: {
                        beginAtZero: true,
                        min: 0,
                        max: 5,
                        ticks: {
                            stepSize: 0.5
                        }
                    }
                }
            },
            plugins: [ChartDataLabels] // Register the plugin here
        });
        </script>';

        echo '</div>'; // End of chart-container
        echo '</div>'; // End of user-group
    }
} else {
    echo '<p>No data available.</p>';
}
?>

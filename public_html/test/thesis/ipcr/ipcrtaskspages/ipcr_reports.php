<?php
session_start();
include '../../dbconnections/config.php';

$currentUserId = $_SESSION['idnumber'];

// SQL query to retrieve data for the current user only
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
    ipr.final_average
FROM usersinfo AS u
INNER JOIN semester_tasks AS st ON u.college = st.college
INNER JOIN ipcr_performance_rating AS ipr ON u.idnumber = ipr.idnumber 
    AND st.semester_id = ipr.semester_id
WHERE u.idnumber = ?
ORDER BY st.semester_name";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $currentUserId);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $semesterName = $row['semester_name'];
        $semesterId = $row['semester_id']; // Get the semester ID

        $data['name'] = $row['firstname'] . ' ' . $row['lastname'];
        $data['picture'] = $row['picture'] ? base64_encode($row['picture']) : null;

        // Create a unique key for each semester using both name and ID
        $semesterKey = $semesterName . '_' . $semesterId;

        // Assign weighted averages; replace 0.0 values with null
        $data['semesters'][$semesterKey] = [
            'strategic' => ($row['average_subtotal_strategic'] > 0) ? (float)$row['average_subtotal_strategic'] : null,
            'core' => ($row['average_subtotal_core'] > 0) ? (float)$row['average_subtotal_core'] : null,
            'support' => ($row['average_subtotal_support'] > 0) ? (float)$row['average_subtotal_support'] : null,
            'final_average' => $row['final_average'], // Store the final average here
            'name' => $semesterName // Store the original semester name
        ];
    }
}

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
        margin: 10px 0;
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
    echo '<div class="user-group">';
    echo '<div class="user-info">';

    // Display the profile picture
    if ($data['picture']) {
        $profilePicture = 'data:image/jpeg;base64,' . $data['picture'];
        echo '<img src="' . $profilePicture . '" alt="Profile Picture" class="user-picture">';
    } else {
        echo '<div class="no-image">No image uploaded</div>';
    }

    echo '<h1>' . htmlspecialchars($data['name']) . '</h1>';
    echo '</div>'; // End of user-info

    // Prepare data for the chart
    $strategicData = [];
    $coreData = [];
    $supportData = [];
    $finalAverage = [];
    $semesterLabels = [];

    foreach ($data['semesters'] as $semesterKey => $taskAverages) {
        $semesterLabels[] = $taskAverages['name']; // Use the stored original semester name
        $strategicData[] = $taskAverages['strategic'] ?? null;
        $coreData[] = $taskAverages['core'] ?? null;
        $supportData[] = $taskAverages['support'] ?? null;
        $finalAverage[] = $taskAverages['final_average'] ?? null; // Include final average
    }

    // Create the chart for the current user
    echo '<div class="chart-container">';
    echo '<div class="chart-wrapper">'; // Start of scrollable wrapper
    echo '<h2>Performance Metrics Per Semester</h2>';
    echo '<canvas id="chart_' . $currentUserId . '" width="400" height="200"></canvas>';
    echo '</div>'; // End of chart-wrapper
    echo '<script>
        var ctx = document.getElementById("chart_' . $currentUserId . '").getContext("2d");
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
} else {
    echo '<p>No data available.</p>';
}
?>
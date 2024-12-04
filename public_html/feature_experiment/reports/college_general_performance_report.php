<?php
session_start();
include __DIR__ . '/../../dbconnections/config.php';

include __DIR__ . '/includes/current_college_by_user_id.php';
include __DIR__ . '/includes/get_semesters.php';
include __DIR__ . '/includes/get_report_data.php';
include __DIR__ . '/includes/get_final_rating.php';

$college = get_college($_SESSION['idnumber']);

$semesters = get_semesters($college);

if (isset($_GET['semester_id'])) {

  $latest_semester_task = get_latest_semester($college, $_GET['semester_id']);

  // Retrieve the 'semester_id' parameter value
  $semesterId = $_GET['semester_id'] ?? null;
  $data = getUserSemesterData($_SESSION['idnumber'], $college, $semesterId);

  // Output the result as JSON
  // echo json_encode($data, JSON_PRETTY_PRINT);

} else {

  $latest_semester_task = get_latest_semester($college);

  if ($latest_semester_task) {
    // echo "Latest Semester: " . $latest_semester_task['semester_name'] . " (ID: " . $latest_semester_task['semester_id'] . ")";
    $semesterId =  $latest_semester_task['semester_id'];
    $data = getUserSemesterData($_SESSION['idnumber'], $college, $semesterId);

  } else {
    // echo "No semester found for the specified college.";
  }
}

$data =  transformUserSemesterData(json_encode(getUserSemesterData($_SESSION['idnumber'], $college, $semesterId), true));

// Initialize counters
$total_ratee = 0;

$total_outstanding = 0;
$total_very_satisfactory = 0;
$total_satisfactory = 0;
$total_unsatisfactory = 0;
$total_poor = 0;

$total_pending_ratee = 0;

// Loop through transformed data and populate counters based on the final average
foreach ($data as $user) {
    // Increase total ratee counter
    $total_ratee++;


    $finalAverage = $user['final_average'];

    if ($finalAverage >= 4.20) {
        // Outstanding rating
        $total_outstanding++;
    } elseif ($finalAverage >= 3.40) {
        // Very Satisfactory rating
        $total_very_satisfactory++;
    } elseif ($finalAverage >= 2.60) {
        // Satisfactory rating
        $total_satisfactory++;
    } elseif ($finalAverage >= 1.80) {
        // Unsatisfactory rating
        $total_unsatisfactory++;
    } else {
        // Poor rating
        $total_poor++;
    }
}


// Calculate percentages for each category
$percentage_outstanding = ($total_ratee > 0) ? ($total_outstanding / $total_ratee) * 100 : 0;
$percentage_very_satisfactory = ($total_ratee > 0) ? ($total_very_satisfactory / $total_ratee) * 100 : 0;
$percentage_satisfactory = ($total_ratee > 0) ? ($total_satisfactory / $total_ratee) * 100 : 0;
$percentage_unsatisfactory = ($total_ratee > 0) ? ($total_unsatisfactory / $total_ratee) * 100 : 0;
$percentage_poor = ($total_ratee > 0) ? ($total_poor / $total_ratee) * 100 : 0; // Assuming no "Poor" category in this case

// Arrays for dynamically building the chart
$labels = [];
$dataValues = [];
$backgroundColors = [];
$hoverOffsets = 4;

// Add categories to the chart only if the percentage is greater than 0
if ($percentage_outstanding > 0) {
    $labels[] = 'Outstanding';
    $dataValues[] = $percentage_outstanding;
    $backgroundColors[] = '#06BA63';  // Green
}

if ($percentage_very_satisfactory > 0) {
    $labels[] = 'Very Satisfactory';
    $dataValues[] = $percentage_very_satisfactory;
    $backgroundColors[] = '#A4C639';  // Yellow-Green
}

if ($percentage_satisfactory > 0) {
    $labels[] = 'Satisfactory';
    $dataValues[] = $percentage_satisfactory;
    $backgroundColors[] = '#F8D351';  // Yellow
}

if ($percentage_unsatisfactory > 0) {
    $labels[] = 'Unsatisfactory';
    $dataValues[] = $percentage_unsatisfactory;
    $backgroundColors[] = '#FF6347';  // Red
}

if ($percentage_poor > 0) {
    $labels[] = 'Poor';
    $dataValues[] = $percentage_poor;
    $backgroundColors[] = '#F48282';  // Light Red
}

// Check if there's any data
if (empty($labels) || empty($dataValues) || empty($backgroundColors)) {
  // Placeholder data
  $labels = ['No Data'];
  $dataValues = [100]; // A single slice to represent "No Data"
  $backgroundColors = ['#E0E0E0']; // Gray color for the placeholder
  $hoverOffsets = 0;
}

function transformUserSemesterData($jsonData) {
  // Decode JSON into associative array
  $dataArray = json_decode($jsonData, true);

  // Initialize the transformed data array
  $data = [];

  // Loop through each user in the data
  foreach ($dataArray as $userId => $userInfo) {
      $name = $userInfo['name'];

      // Loop through each semester
      foreach ($userInfo['semesters'] as $semesterKey => $semesterData) {
          $strategic = $semesterData['strategic'] !== null ? (float)$semesterData['strategic'] : 0;
          $core = $semesterData['core'] !== null ? (float)$semesterData['core'] : 0;
          $support = $semesterData['support'] !== null ? (float)$semesterData['support'] : 0;
          $finalAverage = (float)$semesterData['final_average'];

          $semesterName = $semesterData['name'] ?? $semesterKey;

          // Add transformed data to the $data array
          $data[] = [
              'idnumber' => $userId,
              'name' => $name,
              'semester' => $semesterName,
              'strategic' => $strategic,
              'core' => $core,
              'support' => $support,
              'final_average' => $finalAverage,
          ];
      }
  }

  return $data;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Performance</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    .chart-container {
      width: 100px;
      height: 100px;
      position: relative;
    }

    .chart-text {
      position: absolute;
      font-size: 14px;
      color: #333;
      font-weight: bold;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
    }

    .table th, .table td {
      vertical-align: middle;
      text-align: center;
    }

    .table {
      width: 100%;
      margin-top: 20px;
    }

    .card-chart {
      display: flex;
      align-items: center;
      justify-content: space-between;
      border: 1px solid #ddd;
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      background-color: #fff;
      width: 100%;
    }

    .chart-container {
        flex: 2; 
        display: flex;
        justify-content: center;
    }

    .legend-container {
      flex: 1;
      max-width: 30%;
      display: flex;
      flex-direction: column;
      gap: 10px;
      padding-left: 20px;
    }

    .legend-box {
      display: inline-block;
      width: 20px;
      height: 20px;
      margin-right: 10px;
      border-radius: 4px;
    }

    .legend-text {
      font-size: 14px;
      color: #333;
    }

    .legend-item {
      display: flex;
      align-items: center;
    }

    body {
      background-color: #f8f9fa;
    }

    #pieChart {
      width: 300px;
      height: 300px;
      margin: 0 auto;
    }
  </style>
</head>
<body>

  <div class="container-fluid px-4">
    <div class="card-chart mb-4">
        <div >
            <h5><?php echo $college ?></h5>
            <br>
            <h6>Has <?php echo $percentage_outstanding . '%'?> of Outstanding Personels for <?php echo $latest_semester_task['semester_name'] ?></h6>
            <br>
            <!-- Dropdown Button -->
            <div class="dropdown">
                <button class="btn btn-outline-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                    Select Semester
                </button>
                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                    <?php

                      foreach ($semesters as $semester) {
                        // Assuming 'semester_name' is the key in the array
                        // echo "<li><a class='dropdown-item' href='#'>" .  htmlspecialchars($semester['semester_id']) ." :: " . htmlspecialchars($semester['semester_name']) . "</a></li>";
                        echo "<li><a class='dropdown-item' href='?semester_id=" . htmlspecialchars($semester['semester_id']) . "'>" 
                              . htmlspecialchars($semester['semester_id']) . " :: " 
                              . htmlspecialchars($semester['semester_name']) . "</a></li>";
                      }
                      
                    ?>
                </ul>
            </div>
        </div>
        <div class="pie-chart-container">
            <canvas id="pieChart"></canvas>
        </div>
        <div class="legend-container">
            <div class="legend-item">
                <span class="legend-box" style="background-color: #06BA63;"></span>
                <span class="legend-text">Outstanding (O)</span>
            </div>
            <div class="legend-item">
                <span class="legend-box" style="background-color: #A4C639;"></span>
                <span class="legend-text">Very Satisfactory (VS)</span>
            </div>
            <div class="legend-item">
                <span class="legend-box" style="background-color: #F8D351;"></span>
                <span class="legend-text">Satisfactory (S)</span>
            </div>
            <div class="legend-item">
                <span class="legend-box" style="background-color: #FF6347;"></span>
                <span class="legend-text">Unsatisfactory (U)</span>
            </div>
            <div class="legend-item">
                <span class="legend-box" style="background-color: #F48282;"></span>
                <span class="legend-text">Poor (P)</span>
            </div>
      </div>
    </div>

    <table class="table table-bordered table-hover">
      <thead class="table-light">
        <tr>
          <th></th>
          <th>Name</th>
          <th>Strategic Tasks</th>
          <th>Core Tasks</th>
          <th>Support Tasks</th>
          <th>Final Average</th>
          <th>Rating</th>
          <th>Description</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($data as $index => $row): 

          if( $row['strategic'] != null && $row['core'] != null && $row['support'] != null) {
            $finalRating = getFinalRating($row['final_average']);
          } else {
            $finalRating = ['rating'=> 'N/A', 'description'=> 'N/A'];
          }
          // echo json_encode($data);
        ?>
          <tr onclick="window.location.href='personel_performance_report.php?id=<?php echo $row['idnumber'] ?>';" style="cursor: pointer;">
            <td style="text-align: center; vertical-align: middle;">
              <?php if (isset($row['image']) && $row['image']): ?>
                <img src="path_to_image/<?php echo $row['image']; ?>" alt="Profile Image" style="width: 40px; height: 40px; border-radius: 50%;">
              <?php else: ?>
                <div style="width: 40px; height: 40px; border-radius: 50%; background-color: #ccc; color: #fff; display: flex; justify-content: center; align-items: center;">
                  <?php echo strtoupper(substr($row['name'], 0, 2)); ?> <!-- Show first two initials of the name -->
                </div>
              <?php endif; ?>
            </td>     
          
            <td style="text-align: start;">
              <?php echo $row['name']; ?>
            </td>              
            <td>
              <div class="chart-container">
                <canvas id="chart-strategic-<?php echo $index; ?>" hidden></canvas>
                <div class="chart-text"><?php echo $row['strategic'] ?: 'N/A'; ?></div>
              </div>
            </td>
            <td>
              <div class="chart-container">
                <canvas id="chart-core-<?php echo $index; ?>" hidden></canvas>
                <div class="chart-text"><?php echo $row['core'] ?: 'N/A'; ?></div>
              </div>
            </td>
            <td align="center">
              <div class="chart-container">
                <canvas id="chart-support-<?php echo $index; ?>" hidden></canvas>
                <div class="chart-text"><?php echo $row['support'] ?: 'N/A'; ?></div>
              </div>
            </td>
            <td  style="display: flex; justify-content: center; align-items: center;">
              <div class="chart-container">
                <canvas id="chart-final-<?php echo $index; ?>"></canvas>
                <div class="chart-text"><?php echo number_format($row['final_average'], 2); ?></div>
              </div>
            </td>
            <td><?php echo $finalRating['rating']; ?></td>
            <td><?php echo $finalRating['description']; ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
  <script>

    // Data for the pie chart
    const data = {
      labels: <?php echo json_encode($labels); ?>,  // Labels array dynamically generated
      datasets: [{
        data: <?php echo json_encode($dataValues); ?>,  // Data array dynamically generated
        backgroundColor: <?php echo json_encode($backgroundColors); ?>,  // Background color array dynamically generated
        hoverOffset: <?php echo $hoverOffsets; ?>,
        datalabels: {
            clip: false // Prevents clipping
        }
      }]
    };

    // Configuration for the pie chart
    const config = {
        type: 'pie',
        data: data,
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false // Hides default Chart.js legend
                },
                tooltip: {
                    callbacks: {
                        label: function (tooltipItem) {
                            return `${tooltipItem.label}: ${tooltipItem.raw}`;
                        }
                    }
                },
                datalabels: {
                    color: '#fff', // Label color
                    font: {
                        weight: 'bold',
                        size: 14
                    },
                    formatter: (value, context) => {
                        const label = context.chart.data.labels[context.dataIndex];
                        return `${value}% \n${label == 'Outstanding' ? 'Outstanding' : ''}`; // Combines label and value
                    },
                    align: 'center', // Aligns label to the end of the slice
                    anchor: 'center', // Prevents overlap by keeping labels close to the edge
                    padding: 5, // Adds padding to create space
                    clamp: true, // Ensures labels stay within the chart
                },
                title: {
                    display: true,
                    text: 'Rating Distribution', // Your desired title
                    position: 'bottom', // Places the title below the chart
                    font: {
                        size: 16, // Adjusts title font size
                        weight: 'bold'
                    },
                    padding: {
                        top: 10,
                        bottom: 10
                    },
                    color: '#333' // Adjust title color
                }
            },
            
        },
        plugins: [ChartDataLabels] // Include the datalabels plugin
    };

    // Render the pie chart
    const pieChart = new Chart(
      document.getElementById('pieChart'),
      config
    );

    <?php foreach ($data as $index => $row): ?>
      // Strategic chart
      new Chart(document.getElementById('chart-strategic-<?php echo $index; ?>'), {
        type: 'doughnut',
        data: {
          datasets: [{
            data: [<?php echo $row['strategic'] ?: 0; ?>, 5 - <?php echo $row['strategic'] ?: 0; ?>],
            backgroundColor: ['<?php echo getRatingColor($row['strategic']); ?>', 'lightgray'],
            borderWidth: 0
          }]
        },
        options: {
          cutout: '80%',
          plugins: { tooltip: { enabled: false }, legend: { display: false } },
          responsive: true,
          maintainAspectRatio: false,
          rotation: -90,
          circumference: 180
        }
      });

      // Core chart
      new Chart(document.getElementById('chart-core-<?php echo $index; ?>'), {
        type: 'doughnut',
        data: {
          datasets: [{
            data: [<?php echo $row['core'] ?: 0; ?>, 5 - <?php echo $row['core'] ?: 0; ?>],
            backgroundColor: ['<?php echo getRatingColor($row['core']); ?>', 'lightgray'],
            borderWidth: 0
          }]
        },
        options: {
          cutout: '80%',
          plugins: { tooltip: { enabled: false }, legend: { display: false } },
          responsive: true,
          maintainAspectRatio: false,
          rotation: -90,
          circumference: 180
        }
      });

      // Support chart
      new Chart(document.getElementById('chart-support-<?php echo $index; ?>'), {
        type: 'doughnut',
        data: {
          datasets: [{
            data: [<?php echo $row['support'] ?: 0; ?>, 5 - <?php echo $row['support'] ?: 0; ?>],
            backgroundColor: ['<?php echo getRatingColor($row['support']); ?>', 'lightgray'],
            borderWidth: 0
          }]
        },
        options: {
          cutout: '80%',
          plugins: { tooltip: { enabled: false }, legend: { display: false } },
          responsive: true,
          maintainAspectRatio: false,
          rotation: -90,
          circumference: 180
        }
      });

      // Final Average chart
      new Chart(document.getElementById('chart-final-<?php echo $index; ?>'), {
        type: 'doughnut',
        data: {
          datasets: [{
            data: [<?php echo $row['final_average']; ?>, 5 - <?php echo $row['final_average']; ?>],
            backgroundColor: ['<?php echo getRatingColor($row['final_average']); ?>', 'lightgray'],
            borderWidth: 0
          }]
        },
        options: {
          cutout: '70%',
          plugins: { tooltip: { enabled: false }, legend: { display: false } },
          responsive: true,
          maintainAspectRatio: false,
          rotation: 0,
          circumference: 360
        }
      });
    <?php endforeach; ?>
  </script>

  <!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script> -->
</body>
</html>


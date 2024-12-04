<?php
session_start();
include __DIR__ . '/../../dbconnections/config.php';
include __DIR__ . '/includes/get_final_rating.php';
include __DIR__ . '/includes/get_user_info.php';

if (isset($_GET['id'])) {

  $user_info = get_user_info($_GET['id']);

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
    INNER JOIN semester_tasks AS st ON u.college = st.college -- Adjust this join as necessary
    INNER JOIN ipcr_performance_rating AS ipr ON u.idnumber = ipr.idnumber 
        AND st.semester_id = ipr.semester_id
    WHERE u.idnumber = ?
    ORDER BY u.idnumber, st.semester_id";

  $statement = $conn->prepare($query);

  if ($statement === false) {
    die("Error preparing statement: " . $conn->error);
  }

  $statement->bind_param("s", $_GET['id']);
  $statement->execute();

  $result = $statement->get_result();

  if ($result === false) {
    die("Error executing query: " . $conn->error);
  }

  // Fetch all rows as an associative array
  $data = $result->fetch_all(MYSQLI_ASSOC);

  // echo json_encode($data);
}



?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Ratings Per Semester</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body {
      background-color: #f8f9fa;
    }

    .table th, .table td {
      vertical-align: middle;
      text-align: center;
    }

    .table {
      width: 100%;
      margin-top: 20px;
    }

    .chart-container {
      width: 100px;
      height: 100px;
      position: relative;
    }

    .card-chart {
      display: flex;
      justify-content: space-between;
      padding: 20px;
      border: 1px solid #ddd;
      border-radius: 8px;
      background-color: #fff;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
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

    .chart-text {
      position: absolute;
      font-size: 14px;
      color: #333;
      font-weight: bold;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
    }

  </style>
</head>
<body>

  <div class="container-fluid px-4 mt-3">
    <div class="card-chart mb-4">
        <div>
            <h5>Employee Performance</h5>
            <h6><?php echo $user_info['college']; ?></h6>
            <br>
            <span>Employee Name</span>
            <h5><?php echo $user_info['firstname'] . " ". $user_info['lastname']; ?></h5>
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
          <th>Semester</th>
          <th>Strategic Rating</th>
          <th>Core Rating</th>
          <th>Support Rating</th>
          <th>Final Average</th>
          <th>Rating</th>
        </tr>
      </thead>
      <tbody>
        
      <?php foreach ($data as $index => $row): 
        
        if( $row['average_subtotal_strategic'] != null && $row['average_subtotal_core'] != null && $row['average_subtotal_support'] != null) {
          $finalRating = getFinalRating($row['final_average']);
        } else {
          $finalRating = ['rating'=> 'N/A', 'description'=> 'N/A'];
        }

      ?>

        <tr>
          <td style="text-align: start;" ><?php echo $row['semester_name'];?></td>
          <td><?php echo $row['average_subtotal_strategic']; ?></td>
          <td><?php echo $row['average_subtotal_core']; ?></td>
          <td><?php echo $row['average_subtotal_support']; ?></td>
          <td><?php echo $row['final_average']; ?></td>
          <td><?php echo $finalRating['description'] ?></td>
          <td>
              <div class="chart-container">
                <canvas id="chart-final-<?php echo $index; ?>"></canvas>
                <div class="chart-text"><?php echo number_format($row['final_average'], 2); ?></div>
              </div>
            </td>
        </tr>

        <?php endforeach ?>

        <!-- More rows as needed -->
      </tbody>
    </table>
  </div>

  <script>

  <?php foreach ($data as $index => $row): ?>

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

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

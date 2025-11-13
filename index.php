<?php
session_start();
require_once 'db/db.php';

if (!isset($_SESSION['username'])) {
  header("Location: login.php");
  exit();
} 

$registerMsg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
  $username = $_POST['username'];
  $password = $_POST['password'];

  $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
  $stmt->bind_param("s", $username);
  $stmt->execute();
  $res = $stmt->get_result();

  if ($res->num_rows > 0) {
    $registerMsg = '<div class="alert alert-danger">Username already exists.</div>';
  } else {
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $insert = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    $insert->bind_param("ss", $username, $hashed);
    $insert->execute();
    $registerMsg = '<div class="alert alert-success">Account created successfully.</div>';
  }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>E-Repel Dashboard</title>
    
    <link rel="stylesheet" href="css/bootstrap.min.css" />
    <link rel="stylesheet" href="css/stylesheet.css" />
    <link rel="stylesheet" href="css/sidebar.css" />
    <link rel="stylesheet" href="icons/bootstrap-icons.css">
    <script src="js/bootstrap.bundle.min.js"></script>
    <script src="js/chart.umd.js"></script>

    <style>
        body {
            min-height: 100vh;
            overflow-x: hidden;
        }
        #content {
            margin-left: 200px;
            transition: margin-left 0.3s;
        }
        #sidebar.collapsed ~ #content {
            margin-left: 60px;
        }
        .hamburger {
            cursor: pointer;
            font-size: 1.5rem;
        }
        .navbar {
        transition: all 0.3s ease;
        }

        .dropdown-toggle::after {
        display: none;
        }

        .form-select {
        max-width: 200px;
        }

        .chart-container {
        background-color: #fff;
        border-radius: 10px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        padding: 1rem 1.5rem;
        margin-bottom: 1.5rem;
        }

        .chart-container h5 {
        font-weight: 600;
        font-size: 1.25rem;
        margin-bottom: 0.75rem;
        }

        .detection-log {
        background-color: #e9f7ef;
        border: 1px solid #a3d9a5;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 0 8px rgba(3, 66, 85, 0.5);
        font-size: 1.1rem;
        color:rgb(5, 77, 103);
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <nav id="sidebar" class="bg-dark text-white vh-100 position-fixed shadow-sm">
            <div class="d-flex align-items-center justify-content-between p-3 border-bottom border-secondary">
                <div class="d-flex align-items-center sidebar-brand">
                    <span class="hamburger me-3 fs-4" id="toggleSidebar">&#9776;</span>
                    <img src="img/e-repel-logo.png" alt="Logo" class="me-2 sidebar-logo">
                    <span class="fw-bold fs-5 sidebar-text">E-Repel</span>
                </div>
            </div>

            <ul class="nav flex-column mt-4">
                <li class="nav-item">
                    <a class="nav-link text-white d-flex align-items-center px-3 py-2" href="index1.php">
                        <i class="bi bi-house fs-5 me-2"></i> <span class="fw-medium">Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white d-flex align-items-center px-3 py-2" href="reports.php">
                        <i class="bi bi-bar-chart fs-5 me-2"></i> <span class="fw-medium">Reports</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white d-flex align-items-center px-3 py-2" href="mng_acc.php">
                        <i class="bi bi-person fs-5 me-2"></i> <span class="fw-medium">Manage Account</span>
                    </a>
                </li>
                <li class="nav-item mt-auto">
                    <a class="nav-link text-white d-flex align-items-center px-3 py-2" href="db/logout.php">
                        <i class="bi bi-box-arrow-right fs-5 me-2"></i> <span class="fw-medium">Logout</span>
                    </a>
                </li>
            </ul>
        </nav>

        <div id="content" class="container my-5">
            <div class="mx-auto rounded-4 shadow p-4" style="background-color: white; max-width: 1000px;">

            <h4 class="text-primary fw-bold mb-4">Bird Detection Overview</h4>

            <div class="m-2 d-flex flex-wrap gap-4 justify-content-between">

                <!-- 📊 Chart Area -->
                <div class="flex-grow-1" style="min-width: 300px;">
                <div class="mb-3">
                    <label for="timeRange" class="form-label fw-medium">Select Time Range:</label>
                    <select id="timeRange" class="form-select w-75">
                    <option value="today">Today</option>
                    <option value="week">This Week</option>
                    <option value="month">This Month</option>
                    </select>
                </div>
                <div class="bg-white rounded shadow-sm p-3">
                    <canvas id="myChart" style="max-height: 300px; width: 100%;"></canvas>
                </div>
                </div>

                <!-- 📊detection Log -->
                <div class="row" style="width: 400px;">
                <h5 class="fw-semibold mb-3">Latest Bird Detections</h5>
                <div class="bg-white rounded shadow-sm p-2" id="detection-item" style="max-height: 300px; overflow-y: auto;">
                    <?php if (!empty($latestDetections)): ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($latestDetections as $detection): ?>
                        <li
                            class="list-group-item d-flex justify-content-between align-items-center detection-item"
                            data-bs-toggle="modal"
                            data-bs-target="#detectionModal"
                            data-datetime="<?= htmlspecialchars($detection['detection_time']) ?>"
                            data-details='<?= json_encode($detection['counts']) ?>'
                        >
                            <div>
                            <div class="text-muted small"><?= htmlspecialchars($detection['detection_time']) ?></div>
                            <div class="fw-semibold">Birds</div>
                            </div>
                            <span class="badge bg-info text-dark fw-medium rounded-pill">
                            <?= htmlspecialchars(array_sum($detection['counts'])) ?>
                            </span>
                        </li>
                        <?php endforeach; ?>

                    </ul>
                    <?php else: ?>
                    <div class="text-muted p-2">No detections yet.</div>
                    <?php endif; ?>
                </div>
            </div>


                <!-- Modal -->
                <div class="modal fade" id="detectionModal" tabindex="-1" aria-labelledby="detectionModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="detectionModalLabel">Detection Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p><strong>Date & Time:</strong> <span id="modalDatetime"></span></p>
                        <canvas id="birdChart" height="200"></canvas>
                    </div>
                    </div>
                </div>
                </div>


                <!-- 🎥 Live Camera Area 
                <div class="row w-100">
                <h5 class="fw-semibold mt-4">🎥 Live Detection Camera</h5>
                <div class="bg-white rounded shadow-sm p-3 mb-4">
                    <img src="http://localhost:5000/video_feed" class="img-fluid rounded" style="width: 100%; max-height: 400px;" alt="Live Camera Feed">
                </div>
                </div>  -->

            </div>

            <!-- 🚨 Deterrent Controls -->
            <div class="container my-4">
                <div class="row g-3">
                <div class="col-12 col-md-6">
                    <div class="card shadow border-0">
                    <div class="card-body text-white bg-danger rounded-3">
                        <h5 class="card-title mb-2">
                        🕊️ Birds Before Deterrence
                        </h5>
                        <h2 class="fw-bold"><?php echo $before_count; ?></h2>
                    </div>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="card shadow border-0">
                    <div class="card-body text-white bg-success rounded-3">
                        <h5 class="card-title mb-2">
                        🐦 Birds After Deterrence
                        </h5>
                        <h2 class="fw-bold"><?php echo $after_count; ?></h2>
                    </div>
                    </div>
                </div>
                </div>
            </div>




            <div class="row">
            <div class="col-12 col-md-6 mb-3">
                <div class="form-check form-switch text-dark">
                <input class="form-check-input" type="checkbox" id="speakerSwitch" />
                <label class="form-check-label" for="speakerSwitch">Speaker</label>
                </div>
            </div>
            <div class="col-12 col-md-6 mb-3">
                <div class="form-check form-switch text-dark">
                <input class="form-check-input" type="checkbox" id="physicalSwitch" />
                <label class="form-check-label" for="physicalSwitch">Physical Deterrent</label>
                </div>
            </div>  
        </div>
    </div>

    <script>
        const sidebar = document.getElementById('sidebar');
        document.getElementById('toggleSidebar').onclick = function() {
            sidebar.classList.toggle('collapsed');
        };

        let chartInstance;

        document.querySelectorAll('.detection-item').forEach(item => {
        item.addEventListener('click', function () {
            const datetime = this.dataset.datetime;
            const details = JSON.parse(this.dataset.details);

            document.getElementById('modalDatetime').textContent = datetime;

            const labels = Object.keys(details);
            const data = Object.values(details);

            const ctx = document.getElementById('birdChart').getContext('2d');

            // Destroy previous chart if it exists
            if (chartInstance) {
            chartInstance.destroy();
            }

            chartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                label: 'Bird Count',
                data: data,
                backgroundColor: ['#36a2eb', '#ff6384'],
                borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                plugins: {
                legend: { display: false }
                },
                scales: {
                y: {
                    beginAtZero: true,
                    min: 0,
                    max: 10,
                    ticks: {
                    stepSize: 1,
                    precision: 0
                    }
                }
                }
            }
            });
        });
        });


        //const isChecked = this.checked;


        const chartData = {
            today: {
                labels: <?= $todayLabelsJson ?>,
                data: <?= $todayDataJson ?>
            },
            week: {
                labels: <?= $weekLabelsJson ?>,
                data: <?= $weekDataJson ?>
            },
            month: {
                labels: <?= $monthLabelsJson ?>,
                data: <?= $monthDataJson ?>
            }
            };


            // Chart config unchanged except set y-axis properly


            const ctx = document.getElementById("myChart").getContext("2d");
            let myChart = new Chart(ctx, {
            type: "line",
            data: {
                labels: chartData.today.labels,
                datasets: [{
                label: "Max Birds Detected per Time Frame",
                data: chartData.today.data,
                borderColor: "rgba(75, 192, 192, 1)",
                backgroundColor: "rgba(17, 240, 240, 0.1)",
                fill: true,
                tension: 0.3
                }]
            },
            options: {
                responsive: true,
                plugins: {
                title: {
                    display: true,
                    text: "Bird Count"
                }
                },
                scales: {
                y: {
                    beginAtZero: false,
                    min: 0,
                    max: 10,
                    ticks: {
                    stepSize: 1
                    }
                }
                }
            }

            });

            document.getElementById("timeRange").addEventListener("change", function () {
            const selected = this.value;
            myChart.data.labels = chartData[selected].labels;
            myChart.data.datasets[0].data = chartData[selected].data;
            myChart.update();
            });
    </script>
</body>

</html>


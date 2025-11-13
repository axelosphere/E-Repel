<?php
session_start();
require_once 'db/db.php';
include 'db/data_rep.php';

if (!isset($_SESSION['username'])) {
  header("Location: login.php");
  exit();
} 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports</title>

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
        #sidebar {
            min-width: 200px;
            max-width: 200px;
            transition: all 0.3s;
        }
        #sidebar.collapsed {
            min-width: 60px;
            max-width: 60px;
        }
        #sidebar .nav-link span {
            transition: opacity 0.3s;
        }
        #sidebar.collapsed .nav-link span {
            opacity: 0;
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
                    <a class="nav-link text-white d-flex align-items-center px-3 py-2" href="index.php">
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
            <!-- Main Card Container -->
            <div class="mx-auto rounded-4 shadow p-4" style="background-color: white; max-width: 1000px;">
                <h4 class="text-primary fw-bold mb-4">Bird Detection Overview</h4>
                
                <!-- Chart Area -->
                <div class="mb-4">
                    <label for="timeRange" class="form-label fw-medium">Select Time Range:</label>
                    <select id="timeRange" class="form-select w-50 mb-3">
                        <option value="today">Today</option>
                        <option value="week">This Week</option>
                        <option value="month">This Month</option>
                        <option value="prediction">Predicted Next 24 Hours</option>
                    </select>


                    <div class="bg-white rounded shadow-sm p-3">
                        <canvas id="myChart" style="max-height: 300px; width: 100%;"></canvas>
                    </div>
                </div>

                <!-- Detection Logs Table -->
                <div class="mt-4">
                    <h5 class="fw-bold">Detection Logs</h5>

                    <!-- Filters -->
                    <div class="d-flex gap-2 mb-3">
                        <!-- Date Filter -->
                        <input type="date" id="filterDate" class="form-control" style="max-width: 200px;">

                        <!-- Hour Filter -->
                        <select id="filterHour" class="form-select" style="max-width: 150px;">
                            <option value="">All Hours</option>
                            <?php for ($h = 0; $h < 24; $h++): ?>
                                <option value="<?= sprintf("%02d", $h) ?>">
                                    <?= sprintf("%02d:00", $h) ?>
                                </option>
                            <?php endfor; ?>
                        </select>

                        <button class="btn btn-primary" onclick="applyFilters()">Filter</button>
                    </div>

                    <button class="btn btn-warning mb-3" onclick="printTable()">Print PDF</button>

                    <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                        <table class="table table-bordered table-striped align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">Bird Type</th>
                                    <th scope="col">Detection Time</th>
                                    <th scope="col">Count</th>
                                </tr>
                            </thead>
                            <tbody id="detectionTableBody">
                                <?php
                                $rows = $result->fetchAll(PDO::FETCH_ASSOC); // fetch all rows
                                ?>
                                <?php if (count($rows) > 0): ?>
                                    <?php foreach ($rows as $row): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['bird_type']) ?></td>
                                            <td><?= htmlspecialchars($row['detection_time']) ?></td>
                                            <td><?= htmlspecialchars($row['count']) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="3" class="text-center">No detections found.</td></tr>
                                    <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>

        function applyFilters() {
            const selectedDate = document.getElementById('filterDate').value;
            const selectedHour = document.getElementById('filterHour').value;

            const rows = document.querySelectorAll("#detectionTableBody tr");
            rows.forEach(row => {
                const timeCell = row.cells[1]?.innerText; // Detection Time column
                if (!timeCell) return;

                const rowDate = timeCell.split(' ')[0];  // Extract date (YYYY-MM-DD)
                const rowHour = timeCell.split(' ')[1]?.split(':')[0]; // Extract hour (HH)

                let show = true;

                if (selectedDate && rowDate !== selectedDate) {
                    show = false;
                }

                if (selectedHour && rowHour !== selectedHour) {
                    show = false;
                }

                row.style.display = show ? '' : 'none';
            });
        }


        const sidebar = document.getElementById('sidebar');
        document.getElementById('toggleSidebar').onclick = function() {
            sidebar.classList.toggle('collapsed');
        };

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
            },
            prediction: {
                labels: <?= $predictedLabelsJson ?>,
                data: <?= $predictedDataJson ?>
            }
        };

        // Dynamically compute max Y-axis based on all datasets
        const allValues = [
            ...<?= $todayDataJson ?>,
            ...<?= $weekDataJson ?>,
            ...<?= $monthDataJson ?>,
            ...<?= $predictedDataJson ?>
        ];
        const yAxisMax = Math.ceil(Math.max(...allValues) / 10) * 10 || 10; // fallback to 10 if empty

        const ctx = document.getElementById("myChart").getContext("2d");
        let myChart = new Chart(ctx, {
            type: "line",
            data: {
                labels: chartData.today.labels,
                datasets: [
                    {
                        label: "Max Birds Detected per Time Frame",
                        data: chartData.today.data,
                        borderColor: "rgba(75, 192, 192, 1)",
                        backgroundColor: "rgba(17, 240, 240, 0.1)",
                        fill: true,
                        tension: 0.3
                    },
                    {
                        label: "Predicted Bird Activity",
                        data: chartData.prediction.data,
                        borderColor: "rgba(255, 99, 132, 1)",
                        borderDash: [5, 5],
                        fill: false,
                        tension: 0.3,
                        hidden: true
                    }
                ]
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
                        beginAtZero: true,
                        min: 0,
                        max: yAxisMax,
                        ticks: {
                            stepSize: Math.ceil(yAxisMax / 10)
                        }
                    }
                }
            }
        });

        document.getElementById("timeRange").addEventListener("change", function () {
            const selected = this.value;

            if (selected === "prediction") {
                myChart.data.labels = chartData.prediction.labels;
                myChart.data.datasets[0].hidden = true; // Hide historical
                myChart.data.datasets[1].hidden = false; // Show prediction
                myChart.data.datasets[1].data = chartData.prediction.data;
            } else {
                myChart.data.labels = chartData[selected].labels;
                myChart.data.datasets[0].hidden = false; // Show historical
                myChart.data.datasets[1].hidden = true;  // Hide prediction
                myChart.data.datasets[0].data = chartData[selected].data;
            }

            myChart.update();
        });

        function printTable() {
            const printContents = document.querySelector('.table-responsive').innerHTML;
            const originalContents = document.body.innerHTML;

            document.body.innerHTML = `
                <html>
                <head>
                    <title>Detection Logs</title>
                    <style>
                        body { font-family: Arial; padding: 20px; }
                        table { width: 100%; border-collapse: collapse; }
                        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
                        th { background-color: #f2f2f2; }
                    </style>
                </head>
                <body>
                    <h2>Detection Logs</h2>
                    ${printContents}
                </body>
                </html>
            `;
            window.print();
            document.body.innerHTML = originalContents;
            location.reload();
        }
    </script>

</body>

</html>



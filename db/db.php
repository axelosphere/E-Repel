<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "capstone_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}



// Get the latest 'before' bird count
$sql_before = "SELECT before_count FROM birds_before ORDER BY before_id DESC LIMIT 1";
$result_before = $conn->query($sql_before);
$before_count = ($result_before && $result_before->num_rows > 0) ? $result_before->fetch_assoc()['before_count'] : 0;

// Get the latest 'after' bird count
$sql_after = "SELECT after_count FROM birds_after ORDER BY after_id DESC LIMIT 1";
$result_after = $conn->query($sql_after);
$after_count = ($result_after && $result_after->num_rows > 0) ? $result_after->fetch_assoc()['after_count'] : 0;

// --- Birds Before and After Deterrence ---

// Set your deterrence start datetime here (adjust as needed)
$deterrence_start_time = '2025-05-20 15:00:00';

// Query birds before deterrence (sum counts from both tables)
$query_before = "
    SELECT
      (SELECT IFNULL(SUM(egret_count), 0) FROM egret_detections WHERE egret_detection_time < '$deterrence_start_time') +
      (SELECT IFNULL(SUM(kingfisher_count), 0) FROM kingfisher_detections WHERE kingfisher_detection_time < '$deterrence_start_time')
      AS total_before
";
$result_before = $conn->query($query_before);
$row_before = $result_before->fetch_assoc();
$birds_before = $row_before['total_before'] ?? 0;

// Query birds after deterrence
$query_after = "
    SELECT
      (SELECT IFNULL(SUM(egret_count), 0) FROM egret_detections WHERE egret_detection_time >= '$deterrence_start_time') +
      (SELECT IFNULL(SUM(kingfisher_count), 0) FROM kingfisher_detections WHERE kingfisher_detection_time >= '$deterrence_start_time')
      AS total_after
";
$result_after = $conn->query($query_after);
$row_after = $result_after->fetch_assoc();
$birds_after = $row_after['total_after'] ?? 0;



// Query each bird type with a fixed bird_type field
$sql = "
    SELECT egret_detection_time AS detection_time, egret_count AS count, 'Egret' AS bird_type
    FROM egret_detections
    UNION ALL
    SELECT kingfisher_detection_time AS detection_time, kingfisher_count AS count, 'Kingfisher' AS bird_type
    FROM kingfisher_detections
";

$result = $conn->query($sql);

$groupedDetections = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $time = $row['detection_time'];
        $count = (int)$row['count'];
        $birdType = $row['bird_type'];

        // Initialize sub-array if needed
        if (!isset($groupedDetections[$time])) {
            $groupedDetections[$time] = [
                'Egret' => 0,
                'Kingfisher' => 0
            ];
        }

        $groupedDetections[$time][$birdType] += $count;
    }
}

// Convert to array for sorting
$allDetections = [];
foreach ($groupedDetections as $time => $counts) {
    $allDetections[] = [
        'detection_time' => $time,
        'counts' => $counts,
    ];
}

// Sort by newest first
usort($allDetections, function ($a, $b) {
    return strtotime($b['detection_time']) - strtotime($a['detection_time']);
});

// Get only the latest 10
$latestDetections = array_slice($allDetections, 0, 10);




date_default_timezone_set('Asia/Manila'); // Set your timezone
$todayLabels = $todayData = array_fill(0, 12, 0);
$weekLabels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
$weekData = array_fill(0, 7, 0);
$monthLabels = ['Week 1', 'Week 2', 'Week 3', 'Week 4'];
$monthData = array_fill(0, 4, 0);

// === Helper Function ===
function getDetectionData($conn, $table, $timeCol, $countCol) {
    return $conn->query("SELECT $timeCol AS dt, $countCol AS cnt FROM $table");
}

// === Fetch Data ===
$egret = getDetectionData($conn, 'egret_detections', 'egret_detection_time', 'egret_count');
$kingfisher = getDetectionData($conn, 'kingfisher_detections', 'kingfisher_detection_time', 'kingfisher_count');

$allData = [];

foreach ([$egret, $kingfisher] as $result) {
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $allData[] = ['datetime' => $row['dt'], 'count' => (int)$row['cnt']];
        }
    }
}

// === Process Data ===
$now = new DateTime();

foreach ($allData as $entry) {
    $dt = new DateTime($entry['datetime']);
    $hour = (int)$dt->format('g'); // 1-12
    $dow = (int)$dt->format('N') - 1; // 0 (Mon) to 6 (Sun)
    $day = (int)$dt->format('j');
    $weekOfMonth = (int)ceil($day / 7) - 1; // 0-based

    // Today
    if ($dt->format('Y-m-d') == $now->format('Y-m-d')) {
        $todayLabels[$hour - 1] = $hour;
        $todayData[$hour - 1] = max($todayData[$hour - 1], $entry['count']);
    }

    // This Week
    $startOfWeek = clone $now;
    $startOfWeek->modify('monday this week');
    $endOfWeek = clone $startOfWeek;
    $endOfWeek->modify('+6 days');
    if ($dt >= $startOfWeek && $dt <= $endOfWeek) {
        $weekData[$dow] = max($weekData[$dow], $entry['count']);
    }

    // This Month
    if ($dt->format('Y-m') == $now->format('Y-m')) {
        if (isset($monthData[$weekOfMonth])) {
            $monthData[$weekOfMonth] = max($monthData[$weekOfMonth], $entry['count']);
        }
    }
}

// JSON encode for Chart.js
$todayLabelsJson = json_encode(range(1, 12));
$todayDataJson = json_encode($todayData);
$weekLabelsJson = json_encode($weekLabels);
$weekDataJson = json_encode($weekData);
$monthLabelsJson = json_encode($monthLabels);
$monthDataJson = json_encode($monthData);


?>

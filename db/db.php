<?php
// ================================
// PostgreSQL (Supabase) Connection via Environment Variables
// ================================

// Load environment variables (Render automatically provides them)
$host = getenv('DB_HOST');
$port = getenv('DB_PORT') ?: 5432;
$dbname = getenv('DB_NAME');
$dbuser = getenv('DB_USER');
$dbpass = getenv('DB_PASS');

$dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";

try {
    $pdo = new PDO($dsn, $dbuser, $dbpass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// ================================
// Latest before/after bird counts
// ================================

$sql_before = "SELECT before_count FROM birds_before ORDER BY before_id DESC LIMIT 1";
$stmt = $pdo->query($sql_before);
$row = $stmt->fetch();
$before_count = $row['before_count'] ?? 0;

$sql_after = "SELECT after_count FROM birds_after ORDER BY after_id DESC LIMIT 1";
$stmt = $pdo->query($sql_after);
$row = $stmt->fetch();
$after_count = $row['after_count'] ?? 0;

// ================================
// Birds Before and After Deterrence
// ================================

$deterrence_start_time = '2025-05-20 15:00:00';

$query_before = "
    SELECT
      (SELECT COALESCE(SUM(egret_count), 0) FROM egret_detections WHERE egret_detection_time < :det_start) +
      (SELECT COALESCE(SUM(kingfisher_count), 0) FROM kingfisher_detections WHERE kingfisher_detection_time < :det_start)
      AS total_before
";
$stmt = $pdo->prepare($query_before);
$stmt->execute([':det_start' => $deterrence_start_time]);
$birds_before = $stmt->fetchColumn();

$query_after = "
    SELECT
      (SELECT COALESCE(SUM(egret_count), 0) FROM egret_detections WHERE egret_detection_time >= :det_start) +
      (SELECT COALESCE(SUM(kingfisher_count), 0) FROM kingfisher_detections WHERE kingfisher_detection_time >= :det_start)
      AS total_after
";
$stmt = $pdo->prepare($query_after);
$stmt->execute([':det_start' => $deterrence_start_time]);
$birds_after = $stmt->fetchColumn();

// ================================
// Combined Bird Detections
// ================================

$sql = "
    SELECT egret_detection_time AS detection_time, egret_count AS count, 'Egret' AS bird_type
    FROM egret_detections
    UNION ALL
    SELECT kingfisher_detection_time AS detection_time, kingfisher_count AS count, 'Kingfisher' AS bird_type
    FROM kingfisher_detections
";
$stmt = $pdo->query($sql);

$groupedDetections = [];

if ($stmt) {
    while ($row = $stmt->fetch()) {
        $time = $row['detection_time'];
        $count = (int)$row['count'];
        $birdType = $row['bird_type'];

        if (!isset($groupedDetections[$time])) {
            $groupedDetections[$time] = ['Egret' => 0, 'Kingfisher' => 0];
        }
        $groupedDetections[$time][$birdType] += $count;
    }
}

// Sort detections (latest first)
$allDetections = [];
foreach ($groupedDetections as $time => $counts) {
    $allDetections[] = [
        'detection_time' => $time,
        'counts' => $counts,
    ];
}
usort($allDetections, fn($a, $b) => strtotime($b['detection_time']) - strtotime($a['detection_time']));
$latestDetections = array_slice($allDetections, 0, 10);

// ================================
// Data for Charts
// ================================

date_default_timezone_set('Asia/Manila');
$todayLabels = $todayData = array_fill(0, 12, 0);
$weekLabels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
$weekData = array_fill(0, 7, 0);
$monthLabels = ['Week 1', 'Week 2', 'Week 3', 'Week 4'];
$monthData = array_fill(0, 4, 0);

function getDetectionData(PDO $pdo, $table, $timeCol, $countCol) {
    $sql = "SELECT {$timeCol} AS dt, {$countCol} AS cnt FROM {$table}";
    return $pdo->query($sql);
}

$egret = getDetectionData($pdo, 'egret_detections', 'egret_detection_time', 'egret_count');
$kingfisher = getDetectionData($pdo, 'kingfisher_detections', 'kingfisher_detection_time', 'kingfisher_count');

$allData = [];
foreach ([$egret, $kingfisher] as $result) {
    if ($result) {
        while ($row = $result->fetch()) {
            $allData[] = ['datetime' => $row['dt'], 'count' => (int)$row['cnt']];
        }
    }
}

$now = new DateTime();

foreach ($allData as $entry) {
    $dt = new DateTime($entry['datetime']);
    $hour = (int)$dt->format('g');
    $dow = (int)$dt->format('N') - 1;
    $day = (int)$dt->format('j');
    $weekOfMonth = (int)ceil($day / 7) - 1;

    if ($dt->format('Y-m-d') == $now->format('Y-m-d')) {
        $todayLabels[$hour - 1] = $hour;
        $todayData[$hour - 1] = max($todayData[$hour - 1], $entry['count']);
    }

    $startOfWeek = clone $now;
    $startOfWeek->modify('monday this week');
    $endOfWeek = clone $startOfWeek;
    $endOfWeek->modify('+6 days');

    if ($dt >= $startOfWeek && $dt <= $endOfWeek) {
        $weekData[$dow] = max($weekData[$dow], $entry['count']);
    }

    if ($dt->format('Y-m') == $now->format('Y-m')) {
        if (isset($monthData[$weekOfMonth])) {
            $monthData[$weekOfMonth] = max($monthData[$weekOfMonth], $entry['count']);
        }
    }
}

// Encode for Chart.js
$todayLabelsJson = json_encode(range(1, 12));
$todayDataJson = json_encode($todayData);
$weekLabelsJson = json_encode($weekLabels);
$weekDataJson = json_encode($weekData);
$monthLabelsJson = json_encode($monthLabels);
$monthDataJson = json_encode($monthData);
?>

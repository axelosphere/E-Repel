<?php
require 'db.php'; // ensure this defines $pdo (your PDO connection)

// ============================
// Detection logs query
// ============================

$query = "
    SELECT 
        egret_detection_time AS detection_time,
        egret_count AS count,
        'Egret' AS bird_type
    FROM egret_detections
    UNION ALL
    SELECT 
        kingfisher_detection_time AS detection_time,
        kingfisher_count AS count,
        'Kingfisher' AS bird_type
    FROM kingfisher_detections
    ORDER BY detection_time DESC
";

$stmt = $pdo->query($query);
$detectionLogs = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

// ============================
// Trend analysis for prediction
// ============================
//
// PostgreSQL doesn’t have HOUR(), so we use EXTRACT(HOUR FROM column)

$trendQuery = "
    SELECT 
        EXTRACT(HOUR FROM detection_time) AS hour,
        SUM(count) AS total_count
    FROM (
        SELECT egret_detection_time AS detection_time, egret_count AS count FROM egret_detections
        UNION ALL
        SELECT kingfisher_detection_time AS detection_time, kingfisher_count AS count FROM kingfisher_detections
    ) AS combined
    GROUP BY hour
    ORDER BY hour ASC
";

$stmt = $pdo->query($trendQuery);

$predictedLabels = [];
$predictedData = [];

if ($stmt) {
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $predictedLabels[] = sprintf("%02d:00", $row['hour']); // e.g., "00:00", "01:00"
        $predictedData[] = (int)$row['total_count'];
    }
}

// Default empty JSON arrays
$predictedLabelsJson = json_encode($predictedLabels ?: []);
$predictedDataJson = json_encode($predictedData ?: []);

// ============================
// Run Python prediction script
// ============================
//
// Make sure your Python script path is correct relative to this file.
// You can also log errors if needed for Render debugging.

$command = escapeshellcmd('python3 ../predict_birds.py'); // Use python3 for Render/Supabase
$output = shell_exec($command);

if ($output) {
    $predictionData = json_decode($output, true);
    if (is_array($predictionData)) {
        $predictedLabelsJson = json_encode($predictionData['labels']);
        $predictedDataJson = json_encode($predictionData['data']);
    }
}
?>

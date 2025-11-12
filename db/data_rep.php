<?php

// Existing query for detection logs
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

$result = $conn->query($query);

// --- Trend analysis for prediction ---
$trendQuery = "
    SELECT 
        HOUR(detection_time) AS hour,
        SUM(count) AS total_count
    FROM (
        SELECT egret_detection_time AS detection_time, egret_count AS count FROM egret_detections
        UNION ALL
        SELECT kingfisher_detection_time AS detection_time, kingfisher_count AS count FROM kingfisher_detections
    ) AS combined
    GROUP BY hour
    ORDER BY hour ASC
";

$trendResult = $conn->query($trendQuery);

$predictedLabels = [];
$predictedData = [];

if ($trendResult && $trendResult->num_rows > 0) {
    while ($row = $trendResult->fetch_assoc()) {
        $predictedLabels[] = sprintf("%02d:00", $row['hour']); // 00:00, 01:00, etc.
        $predictedData[] = (int)$row['total_count'];
    }
}

// Fallback if no data
$predictedLabelsJson = json_encode($predictedLabels ?: []);
$predictedDataJson = json_encode($predictedData ?: []);


// --- Run Python prediction script ---
$command = escapeshellcmd('python ../predict_birds.py'); // Adjust path to your script
$output = shell_exec($command);

$predictedLabelsJson = '[]';
$predictedDataJson = '[]';

if ($output) {
    $predictionData = json_decode($output, true);
    if ($predictionData) {
        $predictedLabelsJson = json_encode($predictionData['labels']);
        $predictedDataJson = json_encode($predictionData['data']);
    }
}


?>

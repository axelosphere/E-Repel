<?php
$statusFile = "status.txt";

// If there's a query parameter like ?status=ON or ?status=OFF
if (isset($_GET['status'])) {
    $status = $_GET['status'];
    if ($status === "ON" || $status === "OFF") {
        file_put_contents($statusFile, $status);
        echo "Status updated to $status";
    } else {
        echo "Invalid status";
    }
} else {
    // If no parameter, return the current status
    if (file_exists($statusFile)) {
        echo file_get_contents($statusFile);
    } else {
        echo "OFF";
    }
}
?>

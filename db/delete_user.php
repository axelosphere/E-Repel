<?php
session_start();
include 'db.php';  // Include your database connection

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $user_id = intval($_GET['id']); // Always sanitize

    $delete = $conn->prepare("DELETE FROM users WHERE id = ?");
    $delete->bind_param("i", $user_id);

    if ($delete->execute()) {
        $_SESSION['flash_msg'] = ['type' => 'success', 'text' => 'User deleted successfully.'];
    } else {
        $_SESSION['flash_msg'] = ['type' => 'danger', 'text' => 'Error deleting user.'];
    }
} else {
    $_SESSION['flash_msg'] = ['type' => 'danger', 'text' => 'User ID not provided.'];
}

header("Location: ../mng_acc.php");
exit();
?>

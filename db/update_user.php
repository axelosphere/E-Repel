<?php
session_start(); 
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {
    $id = intval($_POST['id']);
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Check if user exists
    $check = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows === 0) {
        $_SESSION['flash_msg'] = ['type' => 'danger', 'text' => 'User not found.'];
        header("Location: ../mng_acc.php");
        exit();
    }

    // Update query
    if (!empty($password)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET username = ?, password = ? WHERE id = ?");
        $stmt->bind_param("ssi", $username, $hashed, $id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET username = ? WHERE id = ?");
        $stmt->bind_param("si", $username, $id);
    }

    if ($stmt->execute()) {
        $_SESSION['flash_msg'] = ['type' => 'success', 'text' => 'Account updated successfully.'];
    } else {
        $_SESSION['flash_msg'] = ['type' => 'danger', 'text' => 'Failed to update account.'];
    }

    header("Location: ../mng_acc.php");
    exit();
}
?>

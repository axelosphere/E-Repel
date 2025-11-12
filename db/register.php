<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (strlen($username) < 3 || strlen($password) < 6) {
        $_SESSION['flash_msg'] = [
            'type' => 'danger',
            'text' => 'Username must be at least 3 characters and password at least 6 characters.'
        ];
        header("Location: ../mng_acc.php");
        exit();
    }

    // Check if user exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $_SESSION['flash_msg'] = [
            'type' => 'danger',
            'text' => 'Username already exists.'
        ];
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $insert = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
        $insert->bind_param("ss", $username, $hashed);

        if ($insert->execute()) {
            $_SESSION['flash_msg'] = [
                'type' => 'success',
                'text' => 'Account created successfully.'
            ];
        } else {
            $_SESSION['flash_msg'] = [
                'type' => 'danger',
                'text' => 'Error occurred while creating account.'
            ];
        }
    }

    $stmt->close();
    $conn->close();

    // Redirect back to manage accounts page
    header("Location: ../mng_acc.php");
    exit();
}
?>

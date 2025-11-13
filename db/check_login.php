<?php
session_start();
require 'db.php'; // Make sure this returns a $pdo connection (from your new PDO setup)

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $_SESSION['error'] = 'Please fill in all fields.';
        header("Location: ../login.php");
        exit();
    }

    try {
        // Use prepared statement to prevent SQL injection
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Verify password hash
            if (password_verify($password, $user['password'])) {
                $_SESSION['username'] = $user['username'];
                header("Location: ../index.php");
                exit();
            } else {
                $_SESSION['error'] = 'Invalid password.';
                header("Location: ../login.php");
                exit();
            }
        } else {
            $_SESSION['error'] = 'User not found.';
            header("Location: ../login.php");
            exit();
        }

    } catch (PDOException $e) {
        // Optional: log $e->getMessage() in a file if needed
        $_SESSION['error'] = 'Database error. Please try again later.';
        header("Location: ../login.php");
        exit();
    }
}
?>


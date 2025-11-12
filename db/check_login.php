<?php
session_start();    
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Check user in database
    $sql = "SELECT * FROM users WHERE username='$username'";
    $result = $conn->query($sql);

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();      

        // Password check
        if (password_verify($password,  hash: $row['password'])) {
            $_SESSION['username'] = $username;
            header("Location: ../index1.php"); // Redirect to dashboard
            exit();
        } else {
            $_SESSION['error'] = 'Invalid password.';
            header("Location: ../login.php"); // Redirect back to login
            exit();
        }
    } else {
        $_SESSION['error'] = 'User not found.';
        header("Location: ../login.php"); // Redirect back to login
        exit();
    }
}
?>

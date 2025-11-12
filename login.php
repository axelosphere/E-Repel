<?php
session_start();
include 'db/db.php'
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login - E-Repel</title>
  <link rel="stylesheet" href="css/bootstrap.min.css" />
  <link rel="stylesheet" href="css/stylesheet.css" />
</head>
<body style="background-color: cadetblue;">

  <div class="login-wrapper d-flex justify-content-center align-items-center min-vh-100">
    <div class="login-card shadow-lg p-5 rounded bg-white" style="max-width: 400px; width: 100%;">
      <img src="img/e-repel-logo.png" alt="E-Repel Logo" class="mb-1 mx-auto d-block" style="width: 100px; height: 40;">
      <h3 class="text-center mb-4 text-primary fw-bold">Login to E-Repel</h3>

      <?php
      if (isset($_SESSION['error'])) {
          echo '<div class="alert alert-danger" role="alert">' . $_SESSION['error'] . '</div>';
          unset($_SESSION['error']);
      }
      ?>

      <form action="db/check_login.php" method="post">
        <div class="mb-3">
          <label for="username" class="form-label">Username</label>
          <input type="text" class="form-control rounded-pill" id="username" name="username" placeholder="Enter username" required />
        </div>
        <div class="mb-4">
          <label for="password" class="form-label">Password</label>
          <input type="password" class="form-control rounded-pill" id="password" name="password" placeholder="Enter password" required />
        </div>
        <div class="d-grid">
          <button type="submit" class="btn btn-primary rounded-pill fw-semibold">Login</button>
        </div>
      </form>
    </div>
  </div>


</body>
</html>

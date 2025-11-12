<?php
include 'db/db.php';

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$registerMsg = "";

// Handle registration
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['register'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $registerMsg = '<div class="alert alert-danger">Invalid CSRF token.</div>';
    } else {
        $username = trim($_POST['username']);
        $password = $_POST['password'];

        if (strlen($username) < 3 || strlen($password) < 6) {
            $registerMsg = '<div class="alert alert-danger">Username must be at least 3 characters and password at least 6 characters.</div>';
        } else {
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $res = $stmt->get_result();

            if ($res->num_rows > 0) {
                $registerMsg = '<div class="alert alert-danger">Username already exists.</div>';
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $insert = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
                $insert->bind_param("ss", $username, $hashed);
                $insert->execute();
                $registerMsg = '<div class="alert alert-success">Account created successfully.</div>';
            }
        }
    }
}

// Fetch users
$sql = "SELECT id, username FROM users";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Manage Users - E-Repel</title>
  <link rel="stylesheet" href="css/bootstrap.min.css" />
  <link rel="stylesheet" href="css/stylesheet.css" />
  <script src="js/bootstrap.bundle.min.js"></script>

  <?php if (!empty($registerMsg)): ?>
    <script>
      window.addEventListener('DOMContentLoaded', () => {
        const modalElement = document.getElementById('addAccountModal');
        const modalInstance = new bootstrap.Modal(modalElement);
        modalInstance.show();
      });
    </script>
  <?php endif; ?>

  <style>
    .dropdown-toggle::after { display: none; }
    .img-dropdown { cursor: pointer; object-fit: cover; }
    .navbar { transition: all 0.3s ease; }
    .chart-container { background-color: #fff; border-radius: 10px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
    body { background-color: #f8f9fa; }
    .form-select { max-width: 200px; }
  </style>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const updateModal = document.getElementById('updateAccountModal');
      if (updateModal) {
        updateModal.addEventListener('show.bs.modal', function (event) {
          const button = event.relatedTarget;
          const userId = button.getAttribute('data-id');
          const username = button.getAttribute('data-username');

          document.getElementById('updateUserId').value = userId;
          document.getElementById('updateUsername').value = username;
        });
      }
    });
  </script>
</head>

<body style="background-color: cadetblue;">
<!-- Navbar -->
<nav class="navbar navbar-expand-sm bg-dark navbar-dark p-3 sticky-top">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.php">
      <img src="img/e-repel-logo.png" alt="E-Repel Logo" width="70" height="40"  class="d-inline-block align-text-top">
      E-Repel: Bird Deterrent System
    </a>
    <button class="navbar-toggler ms-auto me-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapsibleNavbar">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse justify-content-end" id="collapsibleNavbar">
      <ul class="navbar-nav align-items-start">
        <li class="nav-item me-3">
          <div class="form-check form-switch text-white nav-link">
            <label class="form-check-label me-2" for="flexSwitchCheckDefault">Off/On</label>
            <input class="form-check-input" type="checkbox" id="flexSwitchCheckDefault" />
          </div>
        </li>
        <li>
          <div class="dropdown">
            <a href="#" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown" aria-expanded="false">
              <img src="img/Avatar.png" alt="Avatar" class="rounded-pill img-dropdown" width="40" />
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuLink">
              <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#addAccountModal">Add Account</a></li>
              <li><a class="dropdown-item" href="view_users.php">Accounts</a></li>
              <li><a class="dropdown-item" href="db/logout.php">Logout</a></li>
            </ul>
          </div>
        </li>
      </ul>
    </div>
  </div>
</nav>

<!-- Add Account Modal -->
<div class="modal fade" id="addAccountModal" tabindex="-1" aria-labelledby="addAccountModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" action="index.php" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Account</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <?= $registerMsg ?>
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <div class="mb-3">
          <label class="form-label">Username</label>
          <input type="text" class="form-control" name="username" required value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Password</label>
          <input type="password" class="form-control" name="password" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" name="register" class="btn btn-primary">Register</button>
      </div>
    </form>
  </div>
</div>

<!-- Main Content -->
<div class="container mt-4">
  <h3 class="text-center text-light mb-4">Manage Users</h3>

  <?php if ($result->num_rows > 0): ?>
    <div class="container my-4">
  <div class="card shadow rounded-3">
    <div class="card-header bg-secondary text-white fw-bold">
      User Accounts
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th scope="col">Username</th>
              <th scope="col">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
              <tr>
                <td><?= htmlspecialchars($row['username']) ?></td>
                <td>
                  <a href="db/delete_user.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-trash"></i> Delete
                  </a>
                  <button class="btn btn-sm btn-outline-primary ms-1"
                          data-bs-toggle="modal"
                          data-bs-target="#updateAccountModal"
                          data-id="<?= $row['id'] ?>"
                          data-username="<?= htmlspecialchars($row['username']) ?>">
                    <i class="bi bi-pencil-square"></i> Update
                  </button>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

  <?php else: ?>
    <div class="alert alert-warning">No users found.</div>
  <?php endif; ?>
</div>

<!-- Update Account Modal -->
<div class="modal fade" id="updateAccountModal" tabindex="-1" aria-labelledby="updateAccountModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" action="db/update_user.php" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Update Account</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id" id="updateUserId">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <div class="mb-3">
          <label class="form-label">Username</label>
          <input type="text" class="form-control" name="username" id="updateUsername" required>
        </div>
        <div class="mb-3">
          <label class="form-label">New Password (leave blank to keep current)</label>
          <input type="password" class="form-control" name="password">
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" name="update" class="btn btn-success">Update</button>
      </div>
    </form>
  </div>
</div>

</body>
</html>

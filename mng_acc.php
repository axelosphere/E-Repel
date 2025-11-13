<?php
session_start();
require_once __DIR__ . '/db/db.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Account</title>

    <link rel="stylesheet" href="css/bootstrap.min.css" />
    <link rel="stylesheet" href="css/stylesheet.css" />
    <link rel="stylesheet" href="css/sidebar.css" />
    <link rel="stylesheet" href="icons/bootstrap-icons.css">
    <script src="js/bootstrap.bundle.min.js"></script>
    <script src="js/chart.umd.js"></script>

    <style>
        body {
            min-height: 100vh;
            overflow-x: hidden;
        }
        #sidebar {
            min-width: 200px;
            max-width: 200px;
            transition: all 0.3s;
        }
        #sidebar.collapsed {
            min-width: 60px;
            max-width: 60px;
        }
        #sidebar .nav-link span {
            transition: opacity 0.3s;
        }
        #sidebar.collapsed .nav-link span {
            opacity: 0;
        }
        #content {
            margin-left: 200px;
            transition: margin-left 0.3s;
        }
        #sidebar.collapsed ~ #content {
            margin-left: 60px;
        }
        .hamburger {
            cursor: pointer;
            font-size: 1.5rem;
        }
        .navbar {
        transition: all 0.3s ease;
        }
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
<body>
    <div class="d-flex">
        <nav id="sidebar" class="bg-dark text-white vh-100 position-fixed shadow-sm">
            <div class="d-flex align-items-center justify-content-between p-3 border-bottom border-secondary">
                <div class="d-flex align-items-center sidebar-brand">
                    <span class="hamburger me-3 fs-4" id="toggleSidebar">&#9776;</span>
                    <img src="img/e-repel-logo.png" alt="Logo" class="me-2 sidebar-logo">
                    <span class="fw-bold fs-5 sidebar-text">E-Repel</span>
                </div>
            </div>

            <ul class="nav flex-column mt-4">
                <li class="nav-item">
                    <a class="nav-link text-white d-flex align-items-center px-3 py-2" href="index.php">
                        <i class="bi bi-house fs-5 me-2"></i> <span class="fw-medium">Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white d-flex align-items-center px-3 py-2" href="reports.php">
                        <i class="bi bi-bar-chart fs-5 me-2"></i> <span class="fw-medium">Reports</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white d-flex align-items-center px-3 py-2" href="mng_acc.php">
                        <i class="bi bi-person fs-5 me-2"></i> <span class="fw-medium">Manage Account</span>
                    </a>
                </li>
                <li class="nav-item mt-auto">
                    <a class="nav-link text-white d-flex align-items-center px-3 py-2" href="db/logout.php">
                        <i class="bi bi-box-arrow-right fs-5 me-2"></i> <span class="fw-medium">Logout</span>
                    </a>
                </li>
            </ul>
        </nav>
        <div id="content" class="container my-5">

            <?php if (isset($_SESSION['flash_msg'])): ?>
                <div class="alert alert-<?= $_SESSION['flash_msg']['type']; ?> alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['flash_msg']['text']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['flash_msg']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['flash_msg'])): ?>
                <div class="alert alert-<?= $_SESSION['flash_msg']['type']; ?> alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['flash_msg']['text']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['flash_msg']); ?>
            <?php endif; ?>

            <div class="mx-auto rounded-4 shadow p-4" style="background-color: white; max-width: 1000px;">
                <h3 class="text-center text-dark mb-4">Manage Users</h3>

                <?php if ($result->num_rows > 0): ?>
                <div class="container my-4">
                    <div class="card shadow rounded-3">
                        <div class="card-header bg-secondary text-white fw-bold">
                        User Accounts
                        <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#addAccountModal">
                            <i class="bi bi-plus-circle"></i> Add Account
                        </button>
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

        </div>
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

    <!-- Add Account Modal -->
    <div class="modal fade" id="addAccountModal" tabindex="-1" aria-labelledby="addAccountModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="post" action="db/register.php" class="modal-content"> <!-- Posts back to same file -->
                <div class="modal-header">
                    <h5 class="modal-title">Add New Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="register" class="btn btn-success">Add Account</button>
                </div>
            </form>
        </div>
    </div>

    
    <script>
        const sidebar = document.getElementById('sidebar');
        document.getElementById('toggleSidebar').onclick = function() {
            sidebar.classList.toggle('collapsed');
        };
    </script>
</body>

</html>


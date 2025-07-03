<?php
session_start();
include 'db.php';

// Check if there are any users in the database
$user_count = 0;
$res = $conn->query('SELECT COUNT(*) FROM users');
if ($res) {
    $row = $res->fetch_row();
    $user_count = $row[0];
}

$first_time = ($user_count == 0);

if (!$first_time && !isset($_SESSION['user_id'])) {
    echo '<div class="alert alert-warning text-center mt-5">Please log in as an admin to register new users.<br><a href="../index.php" class="btn btn-primary mt-3">Go to Login</a></div>';
    exit();
}

if (!$first_time) {
    // Fetch user role
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare('SELECT role FROM users WHERE id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->bind_result($role);
    $stmt->fetch();
    $stmt->close();

    if ($role !== 'admin') {
        echo '<div class="alert alert-danger">Access denied. Only admins can register new users.</div>';
        exit();
    }
}

$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $user_role = isset($_POST['role']) ? $_POST['role'] : 'admin';
    if (strlen($username) < 3 || strlen($password) < 4) {
        $error = 'Username must be at least 3 characters and password at least 4 characters.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare('INSERT INTO users (username, password, role) VALUES (?, ?, ?)');
        $stmt->bind_param('sss', $username, $hash, $user_role);
        if ($stmt->execute()) {
            $success = 'User registered successfully!';
            if ($first_time) {
                echo '<div class="alert alert-success text-center mt-5">Admin user created! <a href="../index.php" class="btn btn-success mt-3">Go to Login</a></div>';
                exit();
            }
        } else {
            $error = 'Error: Username may already exist.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register User - POS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .register-container { max-width: 500px; margin: 60px auto; padding: 2rem; background: #fff; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        @media (max-width: 600px) {
            .register-container { padding: 1rem; margin: 2vh auto; }
        }
    </style>
</head>
<body>
<div class="register-container">
    <h2 class="mb-4 text-center">Register New User</h2>
    <?php if ($first_time): ?>
        <div class="alert alert-info text-center">First-time setup: Please create your admin account.</div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    <form method="POST" autocomplete="off">
        <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <input type="text" class="form-control" id="username" name="username" required minlength="3">
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" id="password" name="password" required minlength="4">
        </div>
        <?php if ($first_time): ?>
            <input type="hidden" name="role" value="admin">
        <?php else: ?>
        <div class="mb-3">
            <label for="role" class="form-label">Role</label>
            <select class="form-select" id="role" name="role" required>
                <option value="cashier">Cashier</option>
                <option value="admin">Admin</option>
            </select>
        </div>
        <?php endif; ?>
        <button type="submit" class="btn btn-primary w-100">Register User</button>
    </form>
    <div class="mt-3 text-center">
        <a href="users.php">&larr; Back to Users</a>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 
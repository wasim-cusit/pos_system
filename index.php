<?php
session_start();
include 'pos/db.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $stmt = $conn->prepare('SELECT id, username, password FROM users WHERE username = ?');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $user, $hash);
        $stmt->fetch();
        if (password_verify($password, $hash)) {
            $_SESSION['user_id'] = $id;
            $_SESSION['username'] = $user;
            header('Location: pos/pos.php');
            exit();
        } else {
            $error = 'Invalid username or password.';
        }
    } else {
        $error = 'Invalid username or password.';
    }
    $stmt->close();
}
// Handle register button
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    header('Location: pos/register.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mindgigs POS Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #0d6efd 0%, #6610f2 100%); min-height: 100vh; }
        .login-container { max-width: 400px; margin: 8vh auto; padding: 2.5rem 2rem; background: #fff; border-radius: 16px; box-shadow: 0 0 24px rgba(13,110,253,0.10); }
        .logo {
            display: flex; flex-direction: column; align-items: center; margin-bottom: 1.5rem;
        }
        .logo img { width: 56px; height: 56px; margin-bottom: 0.5rem; border-radius: 50%; background: #f4f6fb; }
        .logo span { font-size: 1.5rem; font-weight: bold; color: #0d6efd; letter-spacing: 1px; }
        @media (max-width: 500px) {
            .login-container { padding: 1rem; margin: 2vh auto; }
        }
    </style>
</head>
<body>
<div class="login-container">
    <div class="logo">
        <img src="https://placehold.co/56x56?text=M" alt="Mindgigs Logo">
        <span>Mindgigs POS</span>
    </div>
    <h2 class="mb-4 text-center">Login</h2>
    <?php if ($error): ?>
        <div class="alert alert-danger text-center small"><?php echo $error; ?></div>
    <?php endif; ?>
    <form method="POST" autocomplete="off">
        <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <input type="text" class="form-control" id="username" name="username" required autofocus>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <button type="submit" name="login" class="btn btn-primary w-100 mb-2">Login</button>
        <a href="../pos_system/pos/register.php" class="btn btn-outline-secondary w-100 mb-2">Register</a>
<div class="form-text text-center mt-2">Only admins can register new users. Please log in as admin first.</div>
    </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

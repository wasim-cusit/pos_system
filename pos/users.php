<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}
include 'db.php';
// Fetch users
$users = $conn->query('SELECT id, username, role FROM users ORDER BY id DESC');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - Mindgigs POS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background: #f4f6fb; }
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, #0d6efd 0%, #6610f2 100%);
            color: #fff;
            box-shadow: 2px 0 8px rgba(0,0,0,0.04);
        }
        .sidebar .logo {
            font-size: 2rem;
            font-weight: bold;
            letter-spacing: 2px;
            padding: 2rem 1rem 1rem 1rem;
            text-align: center;
        }
        .sidebar .logo img {
            width: 48px; height: 48px; margin-bottom: 8px;
        }
        .sidebar a {
            color: #fff;
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 0.9rem 1.2rem;
            font-size: 1.08rem;
            border-radius: 0 2rem 2rem 0;
            margin-bottom: 0.2rem;
            transition: background 0.2s;
        }
        .sidebar a.active, .sidebar a:hover {
            background: rgba(255,255,255,0.12);
        }
        .sidebar i {
            margin-right: 0.8rem;
            font-size: 1.2rem;
        }
        .header {
            background: linear-gradient(90deg, #0d6efd 0%, #6610f2 100%);
            color: #fff;
            padding: 1.5rem 2rem 1rem 2rem;
            border-radius: 0 0 1.5rem 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(13,110,253,0.08);
        }
        .main-content {
            padding: 2rem 2rem 2rem 2rem;
        }
        @media (max-width: 768px) {
            .sidebar { min-height: auto; }
            .main-content { padding: 1rem; }
            .header { padding: 1rem; }
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-2 d-none d-md-block sidebar">
            <div class="logo">
                <img src="https://placehold.co/48x48?text=M" alt="Mindgigs Logo" class="mb-2 rounded-circle bg-white">
                <div>Mindgigs POS</div>
            </div>
            <a href="pos.php"><i class="bi bi-house-door"></i>Dashboard</a>
            <a href="users.php" class="active"><i class="bi bi-people"></i>Users</a>
            <a href="clients.php"><i class="bi bi-person-badge"></i>Clients</a>
            <a href="products.php"><i class="bi bi-box-seam"></i>Products</a>
            <a href="purchases.php"><i class="bi bi-cart-plus"></i>Purchases</a>
            <a href="sales.php"><i class="bi bi-cash-coin"></i>Sales</a>
            <a href="stock.php"><i class="bi bi-archive"></i>Stock</a>
            <a href="logout.php"><i class="bi bi-box-arrow-right"></i>Logout</a>
        </nav>
        <main class="col-md-10 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="header d-flex align-items-center justify-content-between">
                <div>
                    <h2 class="mb-0">Users</h2>
                    <div class="small">Manage system users and permissions</div>
                </div>
                <a href="register.php" class="btn btn-primary"><i class="bi bi-person-plus"></i> Register User</a>
            </div>
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <table class="table table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $users->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                                    <td><?php echo ucfirst($row['role']); ?></td>
                                    <td><!-- Future actions (edit/delete) --></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 
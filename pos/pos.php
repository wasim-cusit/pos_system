<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}
include 'db.php';
// Fetch counts for dashboard
$user_count = $conn->query('SELECT COUNT(*) FROM users')->fetch_row()[0];
$client_count = $conn->query('SELECT COUNT(*) FROM clients')->fetch_row()[0];
$product_count = $conn->query('SELECT COUNT(*) FROM products')->fetch_row()[0];
$purchase_count = $conn->query('SELECT COUNT(*) FROM purchases')->fetch_row()[0];
$sale_count = $conn->query('SELECT COUNT(*) FROM sales')->fetch_row()[0];
$stock_count = $conn->query('SELECT SUM(stock) FROM products')->fetch_row()[0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mindgigs POS Dashboard</title>
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
        .dashboard-count {
            font-size: 2.2rem;
            font-weight: bold;
            margin-bottom: 0.2rem;
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
            <a href="pos.php" class="active"><i class="bi bi-house-door"></i>Dashboard</a>
            <a href="users.php"><i class="bi bi-people"></i>Users</a>
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
                    <h1 class="h3 mb-0">Welcome to Mindgigs POS</h1>
                    <div class="small">Your smart, modern point of sale system</div>
                </div>
                <div class="d-none d-md-block">
                    <span class="fw-bold"><i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                </div>
            </div>
            <div class="row g-4 mt-2">
                <div class="col-md-4">
                    <div class="card shadow-sm border-0">
                        <div class="card-body text-center">
                            <i class="bi bi-people display-5 text-primary"></i>
                            <div class="dashboard-count text-primary"><?php echo $user_count; ?></div>
                            <h5 class="card-title mt-2">Users</h5>
                            <p class="card-text">Manage system users and permissions.</p>
                            <a href="users.php" class="btn btn-outline-primary btn-sm">View Users</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm border-0">
                        <div class="card-body text-center">
                            <i class="bi bi-box-seam display-5 text-success"></i>
                            <div class="dashboard-count text-success"><?php echo $product_count; ?></div>
                            <h5 class="card-title mt-2">Products</h5>
                            <p class="card-text">View and manage your product inventory.</p>
                            <a href="products.php" class="btn btn-outline-success btn-sm">View Products</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm border-0">
                        <div class="card-body text-center">
                            <i class="bi bi-cash-coin display-5 text-warning"></i>
                            <div class="dashboard-count text-warning"><?php echo $sale_count; ?></div>
                            <h5 class="card-title mt-2">Sales</h5>
                            <p class="card-text">Record and review sales transactions.</p>
                            <a href="sales.php" class="btn btn-outline-warning btn-sm">View Sales</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row g-4 mt-2">
                <div class="col-md-4">
                    <div class="card shadow-sm border-0">
                        <div class="card-body text-center">
                            <i class="bi bi-person-badge display-5 text-info"></i>
                            <div class="dashboard-count text-info"><?php echo $client_count; ?></div>
                            <h5 class="card-title mt-2">Clients</h5>
                            <p class="card-text">Manage your clients and their information.</p>
                            <a href="clients.php" class="btn btn-outline-info btn-sm">View Clients</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm border-0">
                        <div class="card-body text-center">
                            <i class="bi bi-cart-plus display-5 text-danger"></i>
                            <div class="dashboard-count text-danger"><?php echo $purchase_count; ?></div>
                            <h5 class="card-title mt-2">Purchases</h5>
                            <p class="card-text">Record and manage product purchases.</p>
                            <a href="purchases.php" class="btn btn-outline-danger btn-sm">View Purchases</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm border-0">
                        <div class="card-body text-center">
                            <i class="bi bi-archive display-5 text-secondary"></i>
                            <div class="dashboard-count text-secondary"><?php echo $stock_count ? $stock_count : 0; ?></div>
                            <h5 class="card-title mt-2">Stock</h5>
                            <p class="card-text">Monitor and manage your stock levels.</p>
                            <a href="stock.php" class="btn btn-outline-secondary btn-sm">View Stock</a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

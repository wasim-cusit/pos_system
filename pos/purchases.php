<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}
include 'db.php';
// Fetch purchases (for table, not shown in this snippet)
$purchases = $conn->query('SELECT p.id, pr.name as product, p.quantity, p.purchase_date, p.supplier FROM purchases p JOIN products pr ON p.product_id = pr.id ORDER BY p.id DESC');
// Fetch products for dropdown
$products = $conn->query('SELECT id, name FROM products ORDER BY name');
// Fetch user role
$user_id = $_SESSION['user_id'];
$role = 'cashier';
$res = $conn->query("SELECT role FROM users WHERE id = $user_id");
if ($row = $res->fetch_assoc()) {
    $role = $row['role'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchases - Mindgigs POS</title>
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
            <a href="users.php"><i class="bi bi-people"></i>Users</a>
            <a href="clients.php"><i class="bi bi-person-badge"></i>Clients</a>
            <a href="products.php"><i class="bi bi-box-seam"></i>Products</a>
            <a href="purchases.php" class="active"><i class="bi bi-cart-plus"></i>Purchases</a>
            <a href="sales.php"><i class="bi bi-cash-coin"></i>Sales</a>
            <a href="stock.php"><i class="bi bi-archive"></i>Stock</a>
            <a href="logout.php"><i class="bi bi-box-arrow-right"></i>Logout</a>
        </nav>
        <main class="col-md-10 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="header d-flex align-items-center justify-content-between">
                <div>
                    <h2 class="mb-0">Purchases</h2>
                    <div class="small">Record and manage product purchases</div>
                </div>
                <?php if ($role === 'admin'): ?>
                <a href="#" class="btn btn-primary" id="openAddPurchaseModal"><i class="bi bi-cart-plus"></i> Add Purchase</a>
                <?php endif; ?>
            </div>
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <table class="table table-striped align-middle mb-0" id="purchasesTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Purchase Date</th>
                                <th>Supplier</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $purchases->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['product']); ?></td>
                                    <td><?php echo $row['quantity']; ?></td>
                                    <td><?php echo $row['purchase_date']; ?></td>
                                    <td><?php echo htmlspecialchars($row['supplier']); ?></td>
                                    <td><!-- Future actions (edit/delete) --></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Add Purchase Modal -->
            <div class="modal fade" id="addPurchaseModal" tabindex="-1" aria-labelledby="addPurchaseModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form id="addPurchaseForm">
                            <div class="modal-header">
                                <h5 class="modal-title" id="addPurchaseModalLabel">Add Purchase</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div id="add-purchase-modal-alert"></div>
                                <div class="mb-3">
                                    <label for="add-product-id" class="form-label">Product</label>
                                    <select class="form-select" id="add-product-id" name="product_id" required>
                                        <option value="">Select Product</option>
                                        <?php foreach ($products as $p): ?>
                                            <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="add-quantity" class="form-label">Quantity</label>
                                    <input type="number" class="form-control" id="add-quantity" name="quantity" required min="1">
                                </div>
                                <div class="mb-3">
                                    <label for="add-supplier" class="form-label">Supplier</label>
                                    <input type="text" class="form-control" id="add-supplier" name="supplier">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Add Purchase</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Add Purchase Modal logic
const addPurchaseModal = new bootstrap.Modal(document.getElementById('addPurchaseModal'));
const openAddPurchaseBtn = document.getElementById('openAddPurchaseModal');
if (openAddPurchaseBtn) {
    openAddPurchaseBtn.addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('addPurchaseForm').reset();
        document.getElementById('add-purchase-modal-alert').innerHTML = '';
        addPurchaseModal.show();
    });
}
document.getElementById('addPurchaseForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch('add_purchase.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        const alertDiv = document.getElementById('add-purchase-modal-alert');
        if (data.success) {
            alertDiv.innerHTML = '<div class="alert alert-success">' + data.success + '</div>';
            // Add new row to table
            const table = document.getElementById('purchasesTable').querySelector('tbody');
            const newRow = document.createElement('tr');
            newRow.innerHTML = `<td>${data.purchase.id}</td>
                <td>${data.purchase.product}</td>
                <td>${data.purchase.quantity}</td>
                <td>${data.purchase.purchase_date}</td>
                <td>${data.purchase.supplier}</td>
                <td>${data.actions}</td>`;
            table.prepend(newRow);
            setTimeout(() => { addPurchaseModal.hide(); }, 1000);
        } else {
            alertDiv.innerHTML = '<div class="alert alert-danger">' + data.error + '</div>';
        }
    })
    .catch(() => {
        document.getElementById('add-purchase-modal-alert').innerHTML = '<div class="alert alert-danger">An error occurred.</div>';
    });
});
</script>
</body>
</html> 
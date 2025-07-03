<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}
include 'db.php';
// Fetch sales for table
$sales = $conn->query('SELECT s.id, p.name as product, c.name as client, s.quantity, s.sale_date, s.total_price FROM sales s JOIN products p ON s.product_id = p.id JOIN clients c ON s.client_id = c.id ORDER BY s.id DESC');
// Fetch products for dropdown
$products = $conn->query('SELECT id, name, price, stock FROM products ORDER BY name');
// Fetch clients for dropdown
$clients = $conn->query('SELECT id, name FROM clients ORDER BY name');
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
    <title>Sales - Mindgigs POS</title>
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
            <a href="purchases.php"><i class="bi bi-cart-plus"></i>Purchases</a>
            <a href="sales.php" class="active"><i class="bi bi-cash-coin"></i>Sales</a>
            <a href="stock.php"><i class="bi bi-archive"></i>Stock</a>
            <a href="logout.php"><i class="bi bi-box-arrow-right"></i>Logout</a>
        </nav>
        <main class="col-md-10 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="header d-flex align-items-center justify-content-between">
                <div>
                    <h2 class="mb-0">Sales</h2>
                    <div class="small">Record and review sales transactions</div>
                </div>
                <?php if ($role === 'admin' || $role === 'cashier'): ?>
                <a href="#" class="btn btn-primary" id="openAddSaleModal"><i class="bi bi-cash-coin"></i> Add Sale</a>
                <?php endif; ?>
            </div>
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <table class="table table-striped align-middle mb-0" id="salesTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Product</th>
                                <th>Client</th>
                                <th>Quantity</th>
                                <th>Sale Date</th>
                                <th>Total Price</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $sales->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['product']); ?></td>
                                    <td><?php echo htmlspecialchars($row['client']); ?></td>
                                    <td><?php echo $row['quantity']; ?></td>
                                    <td><?php echo $row['sale_date']; ?></td>
                                    <td><?php echo $row['total_price']; ?></td>
                                    <td><!-- Future actions (edit/delete) --></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Add Sale Modal -->
            <div class="modal fade" id="addSaleModal" tabindex="-1" aria-labelledby="addSaleModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form id="addSaleForm">
                            <div class="modal-header">
                                <h5 class="modal-title" id="addSaleModalLabel">Add Sale</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div id="add-sale-modal-alert"></div>
                                <div class="mb-3">
                                    <label for="add-product-id" class="form-label">Product</label>
                                    <select class="form-select" id="add-product-id" name="product_id" required>
                                        <option value="">Select Product</option>
                                        <?php foreach ($products as $p): ?>
                                            <option value="<?php echo $p['id']; ?>" data-price="<?php echo $p['price']; ?>" data-stock="<?php echo $p['stock']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text" id="product-stock-info"></div>
                                </div>
                                <div class="mb-3">
                                    <label for="add-client-id" class="form-label">Client</label>
                                    <select class="form-select" id="add-client-id" name="client_id" required>
                                        <option value="">Select Client</option>
                                        <?php foreach ($clients as $c): ?>
                                            <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="add-quantity" class="form-label">Quantity</label>
                                    <input type="number" class="form-control" id="add-quantity" name="quantity" required min="1">
                                </div>
                                <div class="mb-3">
                                    <label for="add-total-price" class="form-label">Total Price</label>
                                    <input type="text" class="form-control" id="add-total-price" name="total_price" readonly>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Add Sale</button>
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
// Add Sale Modal logic
const addSaleModal = new bootstrap.Modal(document.getElementById('addSaleModal'));
const openAddSaleBtn = document.getElementById('openAddSaleModal');
if (openAddSaleBtn) {
    openAddSaleBtn.addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('addSaleForm').reset();
        document.getElementById('add-sale-modal-alert').innerHTML = '';
        document.getElementById('add-total-price').value = '';
        document.getElementById('product-stock-info').innerHTML = '';
        addSaleModal.show();
    });
}
// Update total price and stock info on product/quantity change
const productSelect = document.getElementById('add-product-id');
const quantityInput = document.getElementById('add-quantity');
productSelect.addEventListener('change', updatePriceAndStock);
quantityInput.addEventListener('input', updatePriceAndStock);
function updatePriceAndStock() {
    const selected = productSelect.options[productSelect.selectedIndex];
    const price = parseFloat(selected.getAttribute('data-price')) || 0;
    const stock = parseInt(selected.getAttribute('data-stock')) || 0;
    const qty = parseInt(quantityInput.value) || 0;
    document.getElementById('product-stock-info').innerHTML = stock ? `Stock: ${stock}` : '';
    document.getElementById('add-total-price').value = (qty > 0 && price > 0) ? (qty * price).toFixed(2) : '';
}
document.getElementById('addSaleForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch('add_sale.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        const alertDiv = document.getElementById('add-sale-modal-alert');
        if (data.success) {
            alertDiv.innerHTML = '<div class="alert alert-success">' + data.success + '</div>';
            // Add new row to table
            const table = document.getElementById('salesTable').querySelector('tbody');
            const newRow = document.createElement('tr');
            newRow.innerHTML = `<td>${data.sale.id}</td>
                <td>${data.sale.product}</td>
                <td>${data.sale.client}</td>
                <td>${data.sale.quantity}</td>
                <td>${data.sale.sale_date}</td>
                <td>${data.sale.total_price}</td>
                <td>${data.actions}</td>`;
            table.prepend(newRow);
            setTimeout(() => { addSaleModal.hide(); }, 1000);
        } else {
            alertDiv.innerHTML = '<div class="alert alert-danger">' + data.error + '</div>';
        }
    })
    .catch(() => {
        document.getElementById('add-sale-modal-alert').innerHTML = '<div class="alert alert-danger">An error occurred.</div>';
    });
});
</script>
</body>
</html> 
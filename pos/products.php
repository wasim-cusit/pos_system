<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}
include 'db.php';
// Fetch products
$products = $conn->query('SELECT id, name, description, price, stock FROM products ORDER BY id DESC');
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
    <title>Products - Mindgigs POS</title>
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
            <a href="products.php" class="active"><i class="bi bi-box-seam"></i>Products</a>
            <a href="purchases.php"><i class="bi bi-cart-plus"></i>Purchases</a>
            <a href="sales.php"><i class="bi bi-cash-coin"></i>Sales</a>
            <a href="stock.php"><i class="bi bi-archive"></i>Stock</a>
            <a href="logout.php"><i class="bi bi-box-arrow-right"></i>Logout</a>
        </nav>
        <main class="col-md-10 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="header d-flex align-items-center justify-content-between">
                <div>
                    <h2 class="mb-0">Products</h2>
                    <div class="small">View and manage your product inventory</div>
                </div>
                <a href="#" class="btn btn-primary" id="openAddProductModal"><i class="bi bi-plus-circle"></i> Add Product</a>
            </div>
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <table class="table table-striped align-middle mb-0" id="productsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $products->fetch_assoc()): ?>
                                <tr id="product-row-<?php echo $row['id']; ?>">
                                    <td><?php echo $row['id']; ?></td>
                                    <td class="prod-name"><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td class="prod-desc"><?php echo htmlspecialchars($row['description']); ?></td>
                                    <td class="prod-price"><?php echo number_format($row['price'], 2); ?></td>
                                    <td class="prod-stock"><?php echo $row['stock']; ?></td>
                                    <td>
                                        <?php if ($role === 'admin'): ?>
                                            <button class="btn btn-sm btn-warning edit-btn" 
                                                data-id="<?php echo $row['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($row['name'], ENT_QUOTES); ?>"
                                                data-desc="<?php echo htmlspecialchars($row['description'], ENT_QUOTES); ?>"
                                                data-price="<?php echo $row['price']; ?>"
                                                data-stock="<?php echo $row['stock']; ?>">
                                                <i class="bi bi-pencil"></i> Edit
                                            </button>
                                            <a href="delete_product.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this product?');"><i class="bi bi-trash"></i> Delete</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Edit Product Modal -->
            <div class="modal fade" id="editProductModal" tabindex="-1" aria-labelledby="editProductModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form id="editProductForm">
                            <div class="modal-header">
                                <h5 class="modal-title" id="editProductModalLabel">Edit Product</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div id="edit-modal-alert"></div>
                                <input type="hidden" id="edit-id" name="id">
                                <div class="mb-3">
                                    <label for="edit-name" class="form-label">Name</label>
                                    <input type="text" class="form-control" id="edit-name" name="name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="edit-description" class="form-label">Description</label>
                                    <input type="text" class="form-control" id="edit-description" name="description">
                                </div>
                                <div class="mb-3">
                                    <label for="edit-price" class="form-label">Price</label>
                                    <input type="number" step="0.01" class="form-control" id="edit-price" name="price" required>
                                </div>
                                <div class="mb-3">
                                    <label for="edit-stock" class="form-label">Stock</label>
                                    <input type="number" class="form-control" id="edit-stock" name="stock" required min="0">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Update Product</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <!-- Add Product Modal -->
            <div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form id="addProductForm">
                            <div class="modal-header">
                                <h5 class="modal-title" id="addProductModalLabel">Add Product</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div id="add-modal-alert"></div>
                                <div class="mb-3">
                                    <label for="add-name" class="form-label">Name</label>
                                    <input type="text" class="form-control" id="add-name" name="name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="add-description" class="form-label">Description</label>
                                    <input type="text" class="form-control" id="add-description" name="description">
                                </div>
                                <div class="mb-3">
                                    <label for="add-price" class="form-label">Price</label>
                                    <input type="number" step="0.01" class="form-control" id="add-price" name="price" required>
                                </div>
                                <div class="mb-3">
                                    <label for="add-stock" class="form-label">Stock</label>
                                    <input type="number" class="form-control" id="add-stock" name="stock" required min="0">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Add Product</button>
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
// Open modal and fill with product data
const editBtns = document.querySelectorAll('.edit-btn');
const editModal = new bootstrap.Modal(document.getElementById('editProductModal'));
editBtns.forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('edit-id').value = this.dataset.id;
        document.getElementById('edit-name').value = this.dataset.name;
        document.getElementById('edit-description').value = this.dataset.desc;
        document.getElementById('edit-price').value = this.dataset.price;
        document.getElementById('edit-stock').value = this.dataset.stock;
        document.getElementById('edit-modal-alert').innerHTML = '';
        editModal.show();
    });
});
// Handle AJAX update
const editForm = document.getElementById('editProductForm');
editForm.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(editForm);
    fetch('update_product.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        const alertDiv = document.getElementById('edit-modal-alert');
        if (data.success) {
            alertDiv.innerHTML = '<div class="alert alert-success">' + data.success + '</div>';
            // Update table row
            const row = document.getElementById('product-row-' + formData.get('id'));
            row.querySelector('.prod-name').textContent = formData.get('name');
            row.querySelector('.prod-desc').textContent = formData.get('description');
            row.querySelector('.prod-price').textContent = parseFloat(formData.get('price')).toFixed(2);
            row.querySelector('.prod-stock').textContent = formData.get('stock');
            setTimeout(() => { editModal.hide(); }, 1000);
        } else {
            alertDiv.innerHTML = '<div class="alert alert-danger">' + data.error + '</div>';
        }
    })
    .catch(() => {
        document.getElementById('edit-modal-alert').innerHTML = '<div class="alert alert-danger">An error occurred.</div>';
    });
});
// Add Product Modal logic
const addProductModal = new bootstrap.Modal(document.getElementById('addProductModal'));
document.getElementById('openAddProductModal').addEventListener('click', function(e) {
    e.preventDefault();
    document.getElementById('addProductForm').reset();
    document.getElementById('add-modal-alert').innerHTML = '';
    addProductModal.show();
});
document.getElementById('addProductForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch('add_product.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        const alertDiv = document.getElementById('add-modal-alert');
        if (data.success) {
            alertDiv.innerHTML = '<div class="alert alert-success">' + data.success + '</div>';
            // Add new row to table
            const table = document.getElementById('productsTable').querySelector('tbody');
            const newRow = document.createElement('tr');
            newRow.innerHTML = `<td>${data.product.id}</td>
                <td class='prod-name'>${data.product.name}</td>
                <td class='prod-desc'>${data.product.description}</td>
                <td class='prod-price'>${parseFloat(data.product.price).toFixed(2)}</td>
                <td class='prod-stock'>${data.product.stock}</td>
                <td>${data.actions}</td>`;
            table.prepend(newRow);
            setTimeout(() => { addProductModal.hide(); }, 1000);
        } else {
            alertDiv.innerHTML = '<div class="alert alert-danger">' + data.error + '</div>';
        }
    })
    .catch(() => {
        document.getElementById('add-modal-alert').innerHTML = '<div class="alert alert-danger">An error occurred.</div>';
    });
});
</script>
</body>
</html> 
<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}
include 'db.php';
// Fetch sales for table
$sales = $conn->query('SELECT s.id, c.name as client, s.sale_date, s.total_amount, s.amount_paid, s.remaining, GROUP_CONCAT(CONCAT(p.name, " (Qty: ", si.quantity, ", Price: ", si.price, ", Disc: ", si.discount, ")") SEPARATOR ", ") as products_summary FROM sales s JOIN clients c ON s.client_id = c.id LEFT JOIN sale_items si ON s.id = si.sale_id LEFT JOIN products p ON si.product_id = p.id GROUP BY s.id ORDER BY s.id DESC');
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
// Fetch summary data for dashboard tabs
$cashier_sales = $conn->query("SELECT COUNT(*) as count, COALESCE(SUM(total_amount),0) as total, COALESCE(SUM(amount_paid),0) as paid, COALESCE(SUM(remaining),0) as remaining FROM sales WHERE payment_type='cash'")->fetch_assoc();
$credit_sales = $conn->query("SELECT COUNT(*) as count, COALESCE(SUM(total_amount),0) as total, COALESCE(SUM(amount_paid),0) as paid, COALESCE(SUM(remaining),0) as remaining FROM sales WHERE payment_type='credit'")->fetch_assoc();
$debit_sales = $conn->query("SELECT COUNT(*) as count, COALESCE(SUM(total_amount),0) as total, COALESCE(SUM(amount_paid),0) as paid, COALESCE(SUM(remaining),0) as remaining FROM sales WHERE payment_type='debit'")->fetch_assoc();
// Convert $products (mysqli_result) to an array for JS
$products_array = [];
if ($products && $products instanceof mysqli_result) {
    while ($row = $products->fetch_assoc()) {
        $products_array[] = $row;
    }
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
        body {
            background: #f4f6fb;
        }

        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, #0d6efd 0%, #6610f2 100%);
            color: #fff;
            box-shadow: 2px 0 8px rgba(0, 0, 0, 0.04);
        }

        .sidebar .logo {
            font-size: 2rem;
            font-weight: bold;
            letter-spacing: 2px;
            padding: 2rem 1rem 1rem 1rem;
            text-align: center;
        }

        .sidebar .logo img {
            width: 48px;
            height: 48px;
            margin-bottom: 8px;
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

        .sidebar a.active,
        .sidebar a:hover {
            background: rgba(255, 255, 255, 0.12);
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
            box-shadow: 0 2px 8px rgba(13, 110, 253, 0.08);
        }

        .main-content {
            padding: 2rem 2rem 2rem 2rem;
        }

        @media (max-width: 768px) {
            .sidebar {
                min-height: auto;
            }

            .main-content {
                padding: 1rem;
            }

            .header {
                padding: 1rem;
            }
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
                <!-- Dashboard Tabs -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body">
                        <ul class="nav nav-tabs mb-3" id="dashboardTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="cashier-tab" data-bs-toggle="tab" data-bs-target="#cashier" type="button" role="tab" aria-controls="cashier" aria-selected="true"><i class="bi bi-person-badge"></i> Cashier</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="credit-tab" data-bs-toggle="tab" data-bs-target="#credit" type="button" role="tab" aria-controls="credit" aria-selected="false"><i class="bi bi-credit-card"></i> Credit</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="debit-tab" data-bs-toggle="tab" data-bs-target="#debit" type="button" role="tab" aria-controls="debit" aria-selected="false"><i class="bi bi-cash-coin"></i> Debit</button>
                            </li>
                        </ul>
                        <div class="tab-content" id="dashboardTabsContent">
                            <div class="tab-pane fade show active" id="cashier" role="tabpanel" aria-labelledby="cashier-tab">
                                <div class="row text-center">
                                    <div class="col-md-3 mb-2">
                                        <div class="card border-success">
                                            <div class="card-body">
                                                <div class="fw-bold text-success">Total Sales</div>
                                                <div class="fs-4"><?php echo $cashier_sales['count']; ?></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <div class="card border-primary">
                                            <div class="card-body">
                                                <div class="fw-bold text-primary">Total Amount</div>
                                                <div class="fs-4"><?php echo number_format($cashier_sales['total'], 2); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <div class="card border-info">
                                            <div class="card-body">
                                                <div class="fw-bold text-info">Total Paid</div>
                                                <div class="fs-4"><?php echo number_format($cashier_sales['paid'], 2); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <div class="card border-danger">
                                            <div class="card-body">
                                                <div class="fw-bold text-danger">Total Remaining</div>
                                                <div class="fs-4"><?php echo number_format($cashier_sales['remaining'], 2); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="credit" role="tabpanel" aria-labelledby="credit-tab">
                                <div class="row text-center">
                                    <div class="col-md-3 mb-2">
                                        <div class="card border-success">
                                            <div class="card-body">
                                                <div class="fw-bold text-success">Total Sales</div>
                                                <div class="fs-4"><?php echo $credit_sales['count']; ?></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <div class="card border-primary">
                                            <div class="card-body">
                                                <div class="fw-bold text-primary">Total Amount</div>
                                                <div class="fs-4"><?php echo number_format($credit_sales['total'], 2); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <div class="card border-info">
                                            <div class="card-body">
                                                <div class="fw-bold text-info">Total Paid</div>
                                                <div class="fs-4"><?php echo number_format($credit_sales['paid'], 2); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <div class="card border-danger">
                                            <div class="card-body">
                                                <div class="fw-bold text-danger">Total Remaining</div>
                                                <div class="fs-4"><?php echo number_format($credit_sales['remaining'], 2); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="debit" role="tabpanel" aria-labelledby="debit-tab">
                                <div class="row text-center">
                                    <div class="col-md-3 mb-2">
                                        <div class="card border-success">
                                            <div class="card-body">
                                                <div class="fw-bold text-success">Total Sales</div>
                                                <div class="fs-4"><?php echo $debit_sales['count']; ?></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <div class="card border-primary">
                                            <div class="card-body">
                                                <div class="fw-bold text-primary">Total Amount</div>
                                                <div class="fs-4"><?php echo number_format($debit_sales['total'], 2); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <div class="card border-info">
                                            <div class="card-body">
                                                <div class="fw-bold text-info">Total Paid</div>
                                                <div class="fs-4"><?php echo number_format($debit_sales['paid'], 2); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <div class="card border-danger">
                                            <div class="card-body">
                                                <div class="fw-bold text-danger">Total Remaining</div>
                                                <div class="fs-4"><?php echo number_format($debit_sales['remaining'], 2); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Dashboard Tabs -->
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <table class="table table-striped align-middle mb-0" id="salesTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Client</th>
                                    <th>Sale Date</th>
                                    <th>Total</th>
                                    <th>Paid</th>
                                    <th>Remaining</th>
                                    <th>Products</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $sales->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $row['id']; ?></td>
                                        <td><?php echo htmlspecialchars($row['client']); ?></td>
                                        <td><?php echo $row['sale_date']; ?></td>
                                        <td><?php echo $row['total_amount']; ?></td>
                                        <td><?php echo $row['amount_paid']; ?></td>
                                        <td><?php echo $row['remaining']; ?></td>
                                        <td>
                                            <?php
                                            if ($row['products_summary']) {
                                                $products = explode(',', $row['products_summary']);
                                                foreach ($products as $prod) {
                                                    echo "<span class='badge bg-gradient bg-info text-dark me-2 mb-2' style='font-size:0.95em;'>" . htmlspecialchars(trim($prod)) . "</span> ";
                                                }
                                                // Optionally, you can calculate and display total items and discount percentage if available in the row
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <button class="btn btn-sm btn-outline-primary" title="Edit" data-bs-toggle="tooltip"><i class="bi bi-pencil"></i></button>
                                                <button class="btn btn-sm btn-outline-danger" title="Delete" data-bs-toggle="tooltip"><i class="bi bi-trash"></i></button>
                                            </div>
                                        </td>
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
                                <div class="modal-header bg-primary text-white">
                                    <h5 class="modal-title" id="addSaleModalLabel"><i class="bi bi-cash-coin me-2"></i>Add Sale</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body bg-light">
                                    <div id="add-sale-modal-alert"></div>
                                    <div class="mb-3">
                                        <label for="add-client-id" class="form-label fw-bold"><i class="bi bi-person-badge me-1"></i>Client</label>
                                        <select class="form-select" id="add-client-id" name="client_id" required>
                                            <option value="">Select Client</option>
                                            <?php foreach ($clients as $c): ?>
                                                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="form-text text-info" id="client-balance-info"></div>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label fw-bold"><i class="bi bi-box-seam me-1"></i>Products</label>
                                        <div class="row g-2 small text-muted mb-1 d-none d-md-flex">
                                            <div class="col-12 col-md-5">Item</div>
                                            <div class="col-6 col-md-2">Quantity</div>
                                            <div class="col-6 col-md-2">Price</div>
                                            <div class="col-6 col-md-2">Discount</div>
                                            <div class="col-6 col-md-1">Remove</div>
                                        </div>
                                        <div id="products-list" class="mb-3"></div>
                                        <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="add-product-row"><i class="bi bi-plus-circle"></i> Add Product</button>
                                    </div>
                                    <div class="row g-2 mb-3">
                                        <div class="col-md-4">
                                            <label for="overall-discount" class="form-label">Overall Discount</label>
                                            <input type="number" class="form-control" id="overall-discount" name="overall_discount" min="0" value="0">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="amount-paid" class="form-label">Amount Paid</label>
                                            <input type="number" class="form-control" id="amount-paid" name="amount_paid" min="0" value="0">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="payment-type" class="form-label">Payment Type</label>
                                            <select class="form-select" id="payment-type" name="payment_type">
                                                <option value="cash">Cash</option>
                                                <option value="credit">Credit</option>
                                                <option value="debit">Debit</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="card p-3 mb-2 bg-white border-info">
                                        <div class="row text-center">
                                            <div class="col">
                                                <div class="fw-bold text-secondary">Total</div>
                                                <div id="total-amount" class="fs-5 text-success">0.00</div>
                                            </div>
                                            <div class="col">
                                                <div class="fw-bold text-secondary">Paid</div>
                                                <div id="paid-amount" class="fs-5 text-primary">0.00</div>
                                            </div>
                                            <div class="col">
                                                <div class="fw-bold text-secondary">Remaining</div>
                                                <div id="remaining" class="fs-5 text-danger">0.00</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer bg-light">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Add Sale</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- Edit Sale Modal -->
                <div class="modal fade" id="editSaleModal" tabindex="-1" aria-labelledby="editSaleModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form id="editSaleForm">
                                <div class="modal-header bg-warning text-dark">
                                    <h5 class="modal-title" id="editSaleModalLabel"><i class="bi bi-pencil-square me-2"></i>Edit Sale</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body bg-light">
                                    <div id="edit-sale-modal-alert"></div>
                                    <input type="hidden" id="edit-sale-id" name="sale_id">
                                    <div class="mb-3">
                                        <label for="edit-client-id" class="form-label fw-bold"><i class="bi bi-person-badge me-1"></i>Client</label>
                                        <select class="form-select" id="edit-client-id" name="client_id" required>
                                            <option value="">Select Client</option>
                                            <?php foreach ($clients as $c): ?>
                                                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold"><i class="bi bi-box-seam me-1"></i>Products</label>
                                        <div id="edit-products-list" class="mb-2"></div>
                                        <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="edit-add-product-row"><i class="bi bi-plus-circle"></i> Add Product</button>
                                    </div>
                                    <div class="row g-2 mb-3">
                                        <div class="col-md-4">
                                            <label for="edit-overall-discount" class="form-label">Overall Discount</label>
                                            <input type="number" class="form-control" id="edit-overall-discount" name="overall_discount" min="0" value="0">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="edit-amount-paid" class="form-label">Amount Paid</label>
                                            <input type="number" class="form-control" id="edit-amount-paid" name="amount_paid" min="0" value="0">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="edit-payment-type" class="form-label">Payment Type</label>
                                            <select class="form-select" id="edit-payment-type" name="payment_type">
                                                <option value="cash">Cash</option>
                                                <option value="credit">Credit</option>
                                                <option value="debit">Debit</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer bg-light">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-warning"><i class="bi bi-save me-1"></i>Update Sale</button>
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
        // --- Multi-product dynamic rows ---
        const productsData = <?php echo json_encode($products_array); ?>;

        function createProductRow(idx) {
            return `<div class=\"card mb-2 shadow-sm border-primary product-row\" data-idx=\"${idx}\">\n        <div class=\"card-body py-2 px-3\">\n            <div class=\"row g-2 align-items-end flex-wrap\">\n                <div class=\"col-12 col-md-5 mb-2 mb-md-0\">\n                    <div class=\"stock-info-label small text-muted mb-1\"></div>\n                    <select class=\"form-select product-id\" required>\n                        <option value=\"\">Product</option>\n                        ${productsData.map(p => `<option value=\"${p.id}\" data-price=\"${p.price}\" data-stock=\"${p.stock}\">${p.name}</option>`).join('')}\n                    </select>\n                </div>\n                <div class=\"col-6 col-md-2 mb-2 mb-md-0\">\n                    <input type=\"number\" class=\"form-control quantity\" min=\"1\" value=\"1\" required placeholder=\"Qty\">\n                </div>\n                <div class=\"col-6 col-md-2 mb-2 mb-md-0\">
                    <input type=\"number\" class=\"form-control price\" min=\"0\" step=\"0.01\" value=\"\" required placeholder=\"Price\">
                </div>\n                <div class=\"col-6 col-md-2 mb-2 mb-md-0\">
                    <input type=\"number\" class=\"form-control discount\" min=\"0\" step=\"0.01\" value=\"0\" placeholder=\"Disc\">
                </div>\n                <div class=\"col-6 col-md-1 text-end\">
                    <button type=\"button\" class=\"btn btn-outline-danger btn-sm remove-product-row w-100\"><i class=\"bi bi-x\"></i></button>\n                </div>\n            </div>\n        </div>\n    </div>`;
        }

        function updateProductRows() {
            const list = document.getElementById('products-list');
            if (list.childElementCount === 0) {
                list.innerHTML = createProductRow(0);
            }
        }
        document.getElementById('add-product-row').addEventListener('click', function() {
            const list = document.getElementById('products-list');
            const idx = list.childElementCount;
            list.insertAdjacentHTML('beforeend', createProductRow(idx));
        });
        document.getElementById('products-list').addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-product-row')) {
                e.target.closest('.product-row').remove();
                updateTotals();
            }
        });
        document.getElementById('products-list').addEventListener('change', function(e) {
            if (e.target.classList.contains('product-id')) {
                const row = e.target.closest('.product-row');
                const selected = e.target.options[e.target.selectedIndex];
                row.querySelector('.price').value = selected.getAttribute('data-price') || '';
                // Set stock label above select
                const stock = selected.getAttribute('data-stock');
                row.querySelector('.stock-info-label').textContent = stock ? `Stock: ${stock}` : '';
            }
            updateTotals();
        });
        document.getElementById('products-list').addEventListener('input', function(e) {
            if (['quantity', 'price', 'discount'].some(cls => e.target.classList.contains(cls))) {
                updateTotals();
            }
        });

        function updateTotals() {
            let total = 0;
            document.querySelectorAll('.product-row').forEach(row => {
                const qty = parseInt(row.querySelector('.quantity').value) || 0;
                const price = parseFloat(row.querySelector('.price').value) || 0;
                const discount = parseFloat(row.querySelector('.discount').value) || 0;
                if (qty > 0 && price > 0) {
                    total += (qty * price) - discount;
                }
            });
            const overallDiscount = parseFloat(document.getElementById('overall-discount').value) || 0;
            total -= overallDiscount;
            if (total < 0) total = 0;
            document.getElementById('total-amount').textContent = total.toFixed(2);
            const paid = parseFloat(document.getElementById('amount-paid').value) || 0;
            document.getElementById('paid-amount').textContent = paid.toFixed(2);
            let remaining = total - paid;
            if (remaining < 0) remaining = 0;
            document.getElementById('remaining').textContent = remaining.toFixed(2);
        }
        document.getElementById('overall-discount').addEventListener('input', updateTotals);
        document.getElementById('amount-paid').addEventListener('input', updateTotals);
        // --- Client balance fetch (AJAX) ---
        document.getElementById('add-client-id').addEventListener('change', function() {
            const clientId = this.value;
            if (!clientId) {
                document.getElementById('client-balance-info').textContent = '';
                return;
            }
            fetch('get_data.php?client_balance=1&client_id=' + clientId)
                .then(res => res.json())
                .then(data => {
                    document.getElementById('client-balance-info').textContent = data.balance !== undefined ? 'Current Balance: ' + data.balance : '';
                });
        });
        // --- Modal open logic ---
        const addSaleModal = new bootstrap.Modal(document.getElementById('addSaleModal'));
        const openAddSaleBtn = document.getElementById('openAddSaleModal');
        if (openAddSaleBtn) {
            openAddSaleBtn.addEventListener('click', function(e) {
                e.preventDefault();
                document.getElementById('addSaleForm').reset();
                document.getElementById('add-sale-modal-alert').innerHTML = '';
                document.getElementById('products-list').innerHTML = '';
                updateProductRows();
                document.getElementById('client-balance-info').textContent = '';
                document.getElementById('total-amount').textContent = '0.00';
                document.getElementById('paid-amount').textContent = '0.00';
                document.getElementById('remaining').textContent = '0.00';
                addSaleModal.show();
            });
        }
        // --- Form submit ---
        document.getElementById('addSaleForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData();
            formData.append('client_id', document.getElementById('add-client-id').value);
            formData.append('overall_discount', document.getElementById('overall-discount').value);
            formData.append('amount_paid', document.getElementById('amount-paid').value);
            formData.append('payment_type', document.getElementById('payment-type').value);
            // Collect products
            const products = [];
            document.querySelectorAll('.product-row').forEach(row => {
                const product_id = row.querySelector('.product-id').value;
                const quantity = row.querySelector('.quantity').value;
                const price = row.querySelector('.price').value;
                const discount = row.querySelector('.discount').value;
                if (product_id && quantity && price) {
                    products.push({
                        product_id,
                        quantity,
                        price,
                        discount
                    });
                }
            });
            formData.append('products', JSON.stringify(products));
            fetch('add_sale.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    const alertDiv = document.getElementById('add-sale-modal-alert');
                    console.log('Sale response:', data); // Debug log
                    if (data.success) {
                        alertDiv.innerHTML = '<div class="alert alert-success">' + data.success + '</div>';
                        const table = document.getElementById('salesTable').querySelector('tbody');
                        const newRow = document.createElement('tr');
                        let productsSummary = '';
                        // Defensive: ensure products is an array
                        if (Array.isArray(data.sale.products) && data.sale.products.length > 0) {
                            productsSummary = data.sale.products.map(p =>
                                `<span class='badge bg-gradient bg-info text-dark me-2 mb-2' style='font-size:0.95em;'>${p.product_name} <span class='text-secondary'>(Qty:${p.quantity}, Price:${p.price}, Disc:${p.discount})</span></span>`
                            ).join(' ');
                        } else if (data.sale.products_summary) {
                            productsSummary = data.sale.products_summary;
                        } else {
                            productsSummary = '<span class="text-danger">No products</span>';
                        }
                        if (typeof data.sale.total_items !== 'undefined' && typeof data.sale.discount_percentage !== 'undefined') {
                            productsSummary += `<div class='mt-2 small text-muted'>Total Items: <b>${data.sale.total_items}</b> &nbsp; | &nbsp; Discount: <b>${data.sale.discount_percentage}%</b></div>`;
                        }
                        newRow.innerHTML = `<td>${data.sale.id}</td>
                <td>${data.sale.client}</td>
                <td>${data.sale.sale_date}</td>
                <td><span class='fw-bold text-success'>${data.sale.total_amount}</span></td>
                <td><span class='fw-bold text-primary'>${data.sale.amount_paid}</span></td>
                <td><span class='fw-bold text-danger'>${data.sale.remaining}</span></td>
                <td>${productsSummary}</td>
                <td>${data.balance}</td>
                <td><!-- Actions --></td>`;
                        table.prepend(newRow);
                        setTimeout(() => {
                            addSaleModal.hide();
                        }, 1000);
                    } else {
                        alertDiv.innerHTML = '<div class="alert alert-danger">' + data.error + '</div>';
                    }
                })
                .catch(() => {
                    document.getElementById('add-sale-modal-alert').innerHTML = '<div class="alert alert-danger">An error occurred.</div>';
                });
        });
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.forEach(function(tooltipTriggerEl) {
                new bootstrap.Tooltip(tooltipTriggerEl);
            });
            // Delete action
            document.getElementById('salesTable').addEventListener('click', function(e) {
                if (e.target.closest('.btn-outline-danger')) {
                    const btn = e.target.closest('.btn-outline-danger');
                    const row = btn.closest('tr');
                    const saleId = row.querySelector('td').textContent.trim();
                    if (confirm('Are you sure you want to delete this sale?')) {
                        fetch('delete_sale.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded'
                                },
                                body: 'sale_id=' + encodeURIComponent(saleId)
                            })
                            .then(res => res.json())
                            .then(data => {
                                if (data.success) {
                                    row.remove();
                                    alert('Sale deleted and product stock restored.');
                                } else {
                                    alert(data.error || 'Failed to delete sale.');
                                }
                            })
                            .catch(() => alert('Error deleting sale.'));
                    }
                }
                // Edit action
                if (e.target.closest('.btn-outline-primary')) {
                    const btn = e.target.closest('.btn-outline-primary');
                    const row = btn.closest('tr');
                    const saleId = row.querySelector('td').textContent.trim();
                    // Fetch sale details via AJAX
                    fetch('get_data.php?sale_id=' + encodeURIComponent(saleId))
                        .then(res => res.json())
                        .then(data => {
                            if (data.success && data.sale) {
                                // Fill modal fields
                                document.getElementById('edit-sale-id').value = data.sale.id;
                                document.getElementById('edit-client-id').value = data.sale.client_id;
                                document.getElementById('edit-overall-discount').value = data.sale.overall_discount;
                                document.getElementById('edit-amount-paid').value = data.sale.amount_paid;
                                document.getElementById('edit-payment-type').value = data.sale.payment_type;
                                // Fill products
                                const productsList = document.getElementById('edit-products-list');
                                productsList.innerHTML = '';
                                (data.sale.products || []).forEach((p, idx) => {
                                    productsList.insertAdjacentHTML('beforeend', createEditProductRow(idx, p));
                                });
                                editSaleModal.show();
                            } else {
                                alert('Failed to fetch sale details.');
                            }
                        });
                }
            });
        });

        function createEditProductRow(idx, p) {
            return `<div class=\"card mb-2 shadow-sm border-primary product-row\" data-idx=\"${idx}\">
        <div class=\"card-body py-2 px-3\">
            <div class=\"row g-2 align-items-end flex-wrap\">
                <div class=\"col-12 col-md-5 mb-2 mb-md-0\">
                    <select class=\"form-select product-id\" required>
                        <option value=\"\">Product</option>
                        ${productsData.map(prod => `<option value=\"${prod.id}\" data-price=\"${prod.price}\" data-stock=\"${prod.stock}\"${prod.id==p.product_id?' selected':''}>${prod.name}</option>`).join('')}
                    </select>
                    <div class=\"form-text stock-info\"></div>
                </div>
                <div class=\"col-6 col-md-2 mb-2 mb-md-0\">
                    <input type=\"number\" class=\"form-control quantity\" min=\"1\" value=\"${p.quantity}\" required placeholder=\"Qty\">
                </div>
                <div class=\"col-6 col-md-2 mb-2 mb-md-0\">
                    <input type=\"number\" class=\"form-control price\" min=\"0\" step=\"0.01\" value=\"${p.price}\" required placeholder=\"Price\">
                </div>
                <div class=\"col-6 col-md-2 mb-2 mb-md-0\">
                    <input type=\"number\" class=\"form-control discount\" min=\"0\" step=\"0.01\" value=\"${p.discount}\" placeholder=\"Disc\">
                </div>
                <div class=\"col-6 col-md-1 text-end\">
                    <button type=\"button\" class=\"btn btn-outline-danger btn-sm remove-product-row w-100\"><i class=\"bi bi-x\"></i></button>
                </div>
            </div>
        </div>
    </div>`;
        }
        document.getElementById('edit-add-product-row').addEventListener('click', function() {
            const list = document.getElementById('edit-products-list');
            const idx = list.childElementCount;
            list.insertAdjacentHTML('beforeend', createEditProductRow(idx, {
                product_id: '',
                quantity: 1,
                price: '',
                discount: 0
            }));
        });
        document.getElementById('edit-products-list').addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-product-row')) {
                e.target.closest('.product-row').remove();
            }
        });
        document.getElementById('editSaleForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            // Collect products
            const products = [];
            document.querySelectorAll('#edit-products-list .product-row').forEach(row => {
                const product_id = row.querySelector('.product-id').value;
                const quantity = row.querySelector('.quantity').value;
                const price = row.querySelector('.price').value;
                const discount = row.querySelector('.discount').value;
                if (product_id && quantity && price) {
                    products.push({
                        product_id,
                        quantity,
                        price,
                        discount
                    });
                }
            });
            formData.append('products', JSON.stringify(products));
            fetch('update_sale.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    const alertDiv = document.getElementById('edit-sale-modal-alert');
                    if (data.success) {
                        alertDiv.innerHTML = '<div class="alert alert-success">' + data.success + '</div>';
                        setTimeout(() => {
                            editSaleModal.hide();
                            location.reload();
                        }, 1000);
                    } else {
                        alertDiv.innerHTML = '<div class="alert alert-danger">' + data.error + '</div>';
                    }
                })
                .catch(() => {
                    document.getElementById('edit-sale-modal-alert').innerHTML = '<div class="alert alert-danger">An error occurred.</div>';
                });
        });
    </script>
</body>

</html>
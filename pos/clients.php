<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}
include 'db.php';
// Fetch clients
$clients = $conn->query('SELECT id, name, phone, email, address FROM clients ORDER BY id DESC');
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
    <title>Clients - Mindgigs POS</title>
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
            <a href="clients.php" class="active"><i class="bi bi-person-badge"></i>Clients</a>
            <a href="products.php"><i class="bi bi-box-seam"></i>Products</a>
            <a href="purchases.php"><i class="bi bi-cart-plus"></i>Purchases</a>
            <a href="sales.php"><i class="bi bi-cash-coin"></i>Sales</a>
            <a href="stock.php"><i class="bi bi-archive"></i>Stock</a>
            <a href="logout.php"><i class="bi bi-box-arrow-right"></i>Logout</a>
        </nav>
        <main class="col-md-10 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="header d-flex align-items-center justify-content-between">
                <div>
                    <h2 class="mb-0">Clients</h2>
                    <div class="small">Manage your clients and their information</div>
                </div>
                <a href="#" class="btn btn-primary" id="openAddClientModal"><i class="bi bi-person-plus"></i> Add Client</a>
            </div>
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <table class="table table-striped align-middle mb-0" id="clientsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Address</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $clients->fetch_assoc()): ?>
                                <tr id="client-row-<?php echo $row['id']; ?>">
                                    <td><?php echo $row['id']; ?></td>
                                    <td class="client-name"><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td class="client-phone"><?php echo htmlspecialchars($row['phone']); ?></td>
                                    <td class="client-email"><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td class="client-address"><?php echo htmlspecialchars($row['address']); ?></td>
                                    <td>
                                        <?php if ($role === 'admin'): ?>
                                            <button class="btn btn-sm btn-warning edit-client-btn" 
                                                data-id="<?php echo $row['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($row['name'], ENT_QUOTES); ?>"
                                                data-phone="<?php echo htmlspecialchars($row['phone'], ENT_QUOTES); ?>"
                                                data-email="<?php echo htmlspecialchars($row['email'], ENT_QUOTES); ?>"
                                                data-address="<?php echo htmlspecialchars($row['address'], ENT_QUOTES); ?>">
                                                <i class="bi bi-pencil"></i> Edit
                                            </button>
                                            <!-- You can add delete here if needed -->
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Edit Client Modal -->
            <div class="modal fade" id="editClientModal" tabindex="-1" aria-labelledby="editClientModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form id="editClientForm">
                            <div class="modal-header">
                                <h5 class="modal-title" id="editClientModalLabel">Edit Client</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div id="edit-client-modal-alert"></div>
                                <input type="hidden" id="edit-client-id" name="id">
                                <div class="mb-3">
                                    <label for="edit-client-name" class="form-label">Name</label>
                                    <input type="text" class="form-control" id="edit-client-name" name="name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="edit-client-phone" class="form-label">Phone</label>
                                    <input type="text" class="form-control" id="edit-client-phone" name="phone">
                                </div>
                                <div class="mb-3">
                                    <label for="edit-client-email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="edit-client-email" name="email">
                                </div>
                                <div class="mb-3">
                                    <label for="edit-client-address" class="form-label">Address</label>
                                    <input type="text" class="form-control" id="edit-client-address" name="address">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Update Client</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <!-- Add Client Modal -->
            <div class="modal fade" id="addClientModal" tabindex="-1" aria-labelledby="addClientModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form id="addClientForm">
                            <div class="modal-header">
                                <h5 class="modal-title" id="addClientModalLabel">Add Client</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div id="add-client-modal-alert"></div>
                                <div class="mb-3">
                                    <label for="add-client-name" class="form-label">Name</label>
                                    <input type="text" class="form-control" id="add-client-name" name="name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="add-client-phone" class="form-label">Phone</label>
                                    <input type="text" class="form-control" id="add-client-phone" name="phone">
                                </div>
                                <div class="mb-3">
                                    <label for="add-client-email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="add-client-email" name="email">
                                </div>
                                <div class="mb-3">
                                    <label for="add-client-address" class="form-label">Address</label>
                                    <input type="text" class="form-control" id="add-client-address" name="address">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Add Client</button>
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
// Open modal and fill with client data
const editClientBtns = document.querySelectorAll('.edit-client-btn');
const editClientModal = new bootstrap.Modal(document.getElementById('editClientModal'));
editClientBtns.forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('edit-client-id').value = this.dataset.id;
        document.getElementById('edit-client-name').value = this.dataset.name;
        document.getElementById('edit-client-phone').value = this.dataset.phone;
        document.getElementById('edit-client-email').value = this.dataset.email;
        document.getElementById('edit-client-address').value = this.dataset.address;
        document.getElementById('edit-client-modal-alert').innerHTML = '';
        editClientModal.show();
    });
});
// Handle AJAX update
const editClientForm = document.getElementById('editClientForm');
editClientForm.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(editClientForm);
    fetch('update_client.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        const alertDiv = document.getElementById('edit-client-modal-alert');
        if (data.success) {
            alertDiv.innerHTML = '<div class="alert alert-success">' + data.success + '</div>';
            // Update table row
            const row = document.getElementById('client-row-' + formData.get('id'));
            row.querySelector('.client-name').textContent = formData.get('name');
            row.querySelector('.client-phone').textContent = formData.get('phone');
            row.querySelector('.client-email').textContent = formData.get('email');
            row.querySelector('.client-address').textContent = formData.get('address');
            setTimeout(() => { editClientModal.hide(); }, 1000);
        } else {
            alertDiv.innerHTML = '<div class="alert alert-danger">' + data.error + '</div>';
        }
    })
    .catch(() => {
        document.getElementById('edit-client-modal-alert').innerHTML = '<div class="alert alert-danger">An error occurred.</div>';
    });
});
// Add Client Modal logic
const addClientModal = new bootstrap.Modal(document.getElementById('addClientModal'));
document.getElementById('openAddClientModal').addEventListener('click', function(e) {
    e.preventDefault();
    document.getElementById('addClientForm').reset();
    document.getElementById('add-client-modal-alert').innerHTML = '';
    addClientModal.show();
});
document.getElementById('addClientForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch('add_client.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        const alertDiv = document.getElementById('add-client-modal-alert');
        if (data.success) {
            alertDiv.innerHTML = '<div class="alert alert-success">' + data.success + '</div>';
            // Add new row to table
            const table = document.getElementById('clientsTable').querySelector('tbody');
            const newRow = document.createElement('tr');
            newRow.innerHTML = `<td>${data.client.id}</td>
                <td class='client-name'>${data.client.name}</td>
                <td class='client-phone'>${data.client.phone}</td>
                <td class='client-email'>${data.client.email}</td>
                <td class='client-address'>${data.client.address}</td>
                <td>${data.actions}</td>`;
            table.prepend(newRow);
            setTimeout(() => { addClientModal.hide(); }, 1000);
        } else {
            alertDiv.innerHTML = '<div class="alert alert-danger">' + data.error + '</div>';
        }
    })
    .catch(() => {
        document.getElementById('add-client-modal-alert').innerHTML = '<div class="alert alert-danger">An error occurred.</div>';
    });
});
</script>
</body>
</html> 
<?php
session_start();
include 'db.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}
// Check admin
$user_id = $_SESSION['user_id'];
$role = 'cashier';
$res = $conn->query("SELECT role FROM users WHERE id = $user_id");
if ($row = $res->fetch_assoc()) {
    $role = $row['role'];
}
if ($role !== 'admin') {
    echo '<div class="alert alert-danger text-center mt-5">Access denied. Only admins can edit products.<br><a href="products.php" class="btn btn-primary mt-3">Back to Products</a></div>';
    exit();
}
// Get product
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$product = $conn->query("SELECT * FROM products WHERE id = $id")->fetch_assoc();
if (!$product) {
    echo '<div class="alert alert-danger text-center mt-5">Product not found.<br><a href="products.php" class="btn btn-primary mt-3">Back to Products</a></div>';
    exit();
}
$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $desc = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    if (strlen($name) < 2 || $price <= 0) {
        $error = 'Product name and valid price are required.';
    } else {
        $stmt = $conn->prepare('UPDATE products SET name=?, description=?, price=?, stock=? WHERE id=?');
        $stmt->bind_param('ssdii', $name, $desc, $price, $stock, $id);
        if ($stmt->execute()) {
            $success = 'Product updated successfully!';
            // Refresh product data
            $product = $conn->query("SELECT * FROM products WHERE id = $id")->fetch_assoc();
        } else {
            $error = 'Error: Could not update product.';
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
    <title>Edit Product - Mindgigs POS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background: linear-gradient(135deg, #0d6efd 0%, #6610f2 100%); min-height: 100vh; }
        .form-container { max-width: 500px; margin: 6vh auto; padding: 2.5rem 2rem; background: #fff; border-radius: 16px; box-shadow: 0 0 24px rgba(13,110,253,0.10); }
        .logo { display: flex; flex-direction: column; align-items: center; margin-bottom: 1.5rem; }
        .logo img { width: 48px; height: 48px; margin-bottom: 0.5rem; border-radius: 50%; background: #f4f6fb; }
        .logo span { font-size: 1.3rem; font-weight: bold; color: #0d6efd; letter-spacing: 1px; }
        @media (max-width: 500px) { .form-container { padding: 1rem; margin: 2vh auto; } }
    </style>
</head>
<body>
<div class="form-container">
    <div class="logo">
        <img src="https://placehold.co/48x48?text=M" alt="Mindgigs Logo">
        <span>Mindgigs POS</span>
    </div>
    <h3 class="mb-4 text-center">Edit Product</h3>
    <?php if ($success): ?>
        <div class="alert alert-success text-center"><?php echo $success; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger text-center"><?php echo $error; ?></div>
    <?php endif; ?>
    <form method="POST" autocomplete="off">
        <div class="mb-3">
            <label for="name" class="form-label">Name</label>
            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
        </div>
        <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <input type="text" class="form-control" id="description" name="description" value="<?php echo htmlspecialchars($product['description']); ?>">
        </div>
        <div class="mb-3">
            <label for="price" class="form-label">Price</label>
            <input type="number" step="0.01" class="form-control" id="price" name="price" value="<?php echo $product['price']; ?>" required>
        </div>
        <div class="mb-3">
            <label for="stock" class="form-label">Stock</label>
            <input type="number" class="form-control" id="stock" name="stock" value="<?php echo $product['stock']; ?>" required min="0">
        </div>
        <button type="submit" class="btn btn-primary w-100">Update Product</button>
        <a href="products.php" class="btn btn-outline-secondary w-100 mt-2">Back to Products</a>
    </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 
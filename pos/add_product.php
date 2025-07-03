<?php
session_start();
header('Content-Type: application/json');
include 'db.php';
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit();
}
$user_id = $_SESSION['user_id'];
$role = 'cashier';
$res = $conn->query("SELECT role FROM users WHERE id = $user_id");
if ($row = $res->fetch_assoc()) {
    $role = $row['role'];
}
if ($role !== 'admin') {
    echo json_encode(['error' => 'Access denied']);
    exit();
}
$name = trim($_POST['name'] ?? '');
$desc = trim($_POST['description'] ?? '');
$price = floatval($_POST['price'] ?? 0);
$stock = intval($_POST['stock'] ?? 0);
if (strlen($name) < 2 || $price <= 0) {
    echo json_encode(['error' => 'Product name and valid price are required.']);
    exit();
}
$stmt = $conn->prepare('INSERT INTO products (name, description, price, stock) VALUES (?, ?, ?, ?)');
$stmt->bind_param('ssdi', $name, $desc, $price, $stock);
if ($stmt->execute()) {
    $id = $conn->insert_id;
    $actions = '<button class="btn btn-sm btn-warning edit-btn" data-id="' . $id . '" data-name="' . htmlspecialchars($name, ENT_QUOTES) . '" data-desc="' . htmlspecialchars($desc, ENT_QUOTES) . '" data-price="' . $price . '" data-stock="' . $stock . '"><i class="bi bi-pencil"></i> Edit</button> <a href="delete_product.php?id=' . $id . '" class="btn btn-sm btn-danger" onclick="return confirm(\'Are you sure you want to delete this product?\');"><i class="bi bi-trash"></i> Delete</a>';
    echo json_encode(['success' => 'Product added successfully!', 'product' => ['id' => $id, 'name' => $name, 'description' => $desc, 'price' => $price, 'stock' => $stock], 'actions' => $actions]);
} else {
    echo json_encode(['error' => 'Error: Could not add product.']);
}
$stmt->close(); 
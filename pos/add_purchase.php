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
$product_id = intval($_POST['product_id'] ?? 0);
$quantity = intval($_POST['quantity'] ?? 0);
$supplier = trim($_POST['supplier'] ?? '');
if ($product_id < 1 || $quantity < 1) {
    echo json_encode(['error' => 'Please select a product and enter a valid quantity.']);
    exit();
}
$stmt = $conn->prepare('INSERT INTO purchases (product_id, quantity, supplier) VALUES (?, ?, ?)');
$stmt->bind_param('iis', $product_id, $quantity, $supplier);
if ($stmt->execute()) {
    $conn->query('UPDATE products SET stock = stock + ' . $quantity . ' WHERE id = ' . $product_id);
    $id = $conn->insert_id;
    $purchase_date = date('Y-m-d H:i:s');
    $product_name = $conn->query('SELECT name FROM products WHERE id = ' . $product_id)->fetch_row()[0];
    $actions = '';
    echo json_encode(['success' => 'Purchase added and stock updated!', 'purchase' => ['id' => $id, 'product' => $product_name, 'quantity' => $quantity, 'purchase_date' => $purchase_date, 'supplier' => $supplier], 'actions' => $actions]);
} else {
    echo json_encode(['error' => 'Error: Could not add purchase.']);
}
$stmt->close(); 
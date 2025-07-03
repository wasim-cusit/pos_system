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
if ($role !== 'admin' && $role !== 'cashier') {
    echo json_encode(['error' => 'Access denied']);
    exit();
}
$product_id = intval($_POST['product_id'] ?? 0);
$client_id = intval($_POST['client_id'] ?? 0);
$quantity = intval($_POST['quantity'] ?? 0);
$total_price = floatval($_POST['total_price'] ?? 0);
if ($product_id < 1 || $client_id < 1 || $quantity < 1 || $total_price <= 0) {
    echo json_encode(['error' => 'Please fill all fields with valid values.']);
    exit();
}
// Check product stock
$res = $conn->query('SELECT name, stock FROM products WHERE id = ' . $product_id);
if (!$res || $res->num_rows == 0) {
    echo json_encode(['error' => 'Product not found.']);
    exit();
}
list($product_name, $stock) = $res->fetch_row();
if ($stock < $quantity) {
    echo json_encode(['error' => 'Not enough stock.']);
    exit();
}
// Get client name
$res2 = $conn->query('SELECT name FROM clients WHERE id = ' . $client_id);
if (!$res2 || $res2->num_rows == 0) {
    echo json_encode(['error' => 'Client not found.']);
    exit();
}
$client_name = $res2->fetch_row()[0];
// Insert sale
$stmt = $conn->prepare('INSERT INTO sales (product_id, client_id, quantity, total_price) VALUES (?, ?, ?, ?)');
$stmt->bind_param('iiid', $product_id, $client_id, $quantity, $total_price);
if ($stmt->execute()) {
    $conn->query('UPDATE products SET stock = stock - ' . $quantity . ' WHERE id = ' . $product_id);
    $id = $conn->insert_id;
    $sale_date = date('Y-m-d H:i:s');
    $actions = '';
    echo json_encode(['success' => 'Sale recorded and stock updated!', 'sale' => ['id' => $id, 'product' => $product_name, 'client' => $client_name, 'quantity' => $quantity, 'sale_date' => $sale_date, 'total_price' => number_format($total_price, 2)], 'actions' => $actions]);
} else {
    echo json_encode(['error' => 'Error: Could not record sale.']);
}
$stmt->close(); 
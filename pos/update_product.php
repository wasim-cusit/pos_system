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
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$name = trim($_POST['name'] ?? '');
$desc = trim($_POST['description'] ?? '');
$price = floatval($_POST['price'] ?? 0);
$stock = intval($_POST['stock'] ?? 0);
if ($id < 1 || strlen($name) < 2 || $price <= 0) {
    echo json_encode(['error' => 'Invalid input']);
    exit();
}
$stmt = $conn->prepare('UPDATE products SET name=?, description=?, price=?, stock=? WHERE id=?');
$stmt->bind_param('ssdii', $name, $desc, $price, $stock, $id);
if ($stmt->execute()) {
    echo json_encode(['success' => 'Product updated successfully!']);
} else {
    echo json_encode(['error' => 'Could not update product.']);
}
$stmt->close(); 
<?php
session_start();
header('Content-Type: application/json');
include 'db.php';
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['sale_id'])) {
    echo json_encode(['error' => 'Invalid request']);
    exit();
}
$sale_id = intval($_POST['sale_id']);
if ($sale_id < 1) {
    echo json_encode(['error' => 'Invalid sale ID']);
    exit();
}
// Get sale items to restore stock
$res = $conn->query('SELECT product_id, quantity FROM sale_items WHERE sale_id = ' . $sale_id);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $conn->query('UPDATE products SET stock = stock + ' . intval($row['quantity']) . ' WHERE id = ' . intval($row['product_id']));
    }
}
// Delete sale items
$conn->query('DELETE FROM sale_items WHERE sale_id = ' . $sale_id);
// Delete client transactions for this sale
$conn->query('DELETE FROM client_transactions WHERE sale_id = ' . $sale_id);
// Delete the sale
if ($conn->query('DELETE FROM sales WHERE id = ' . $sale_id)) {
    echo json_encode(['success' => 'Sale deleted and stock restored.']);
} else {
    echo json_encode(['error' => 'Failed to delete sale.']);
} 
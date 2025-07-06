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
$client_id = intval($_POST['client_id'] ?? 0);
$overall_discount = floatval($_POST['overall_discount'] ?? 0);
$amount_paid = floatval($_POST['amount_paid'] ?? 0);
$payment_type = $_POST['payment_type'] ?? 'cash';
$products = $_POST['products'] ?? [];
if (is_string($products)) {
    $products = json_decode($products, true);
}
if ($sale_id < 1 || $client_id < 1 || !is_array($products) || count($products) == 0) {
    echo json_encode(['error' => 'Please fill all fields with valid values.']);
    exit();
}
// Restore old product stock
$res = $conn->query('SELECT product_id, quantity FROM sale_items WHERE sale_id = ' . $sale_id);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $conn->query('UPDATE products SET stock = stock + ' . intval($row['quantity']) . ' WHERE id = ' . intval($row['product_id']));
    }
}
// Remove old sale_items
$conn->query('DELETE FROM sale_items WHERE sale_id = ' . $sale_id);
// Check all product stocks before updating
foreach ($products as $item) {
    $pid = intval($item['product_id'] ?? 0);
    $qty = intval($item['quantity'] ?? 0);
    $price = floatval($item['price'] ?? 0);
    $discount = floatval($item['discount'] ?? 0);
    if ($pid < 1 || $qty < 1 || $price <= 0) {
        echo json_encode(['error' => 'Invalid product data.']);
        exit();
    }
    $res = $conn->query('SELECT name, stock FROM products WHERE id = ' . $pid);
    if (!$res || $res->num_rows == 0) {
        echo json_encode(['error' => 'Product not found.']);
        exit();
    }
    list($product_name, $stock) = $res->fetch_row();
    if ($stock < $qty) {
        echo json_encode(['error' => 'Not enough stock for ' . htmlspecialchars($product_name)]);
        exit();
    }
}
// Insert new sale_items and update stock
$total_amount = 0;
foreach ($products as $item) {
    $pid = intval($item['product_id']);
    $qty = intval($item['quantity']);
    $price = floatval($item['price']);
    $discount = floatval($item['discount']);
    $stmt2 = $conn->prepare('INSERT INTO sale_items (sale_id, product_id, quantity, price, discount) VALUES (?, ?, ?, ?, ?)');
    $stmt2->bind_param('iiidd', $sale_id, $pid, $qty, $price, $discount);
    $stmt2->execute();
    $stmt2->close();
    $conn->query('UPDATE products SET stock = stock - ' . $qty . ' WHERE id = ' . $pid);
    $total_amount += ($price * $qty) - $discount;
}
$total_amount -= $overall_discount;
if ($total_amount < 0) $total_amount = 0;
$remaining = $total_amount - $amount_paid;
if ($remaining < 0) $remaining = 0;
// Update sales table
$stmt = $conn->prepare('UPDATE sales SET client_id=?, total_amount=?, overall_discount=?, amount_paid=?, payment_type=?, remaining=? WHERE id=?');
$stmt->bind_param('idddssd', $client_id, $total_amount, $overall_discount, $amount_paid, $payment_type, $remaining, $sale_id);
if (!$stmt->execute()) {
    echo json_encode(['error' => 'Failed to update sale.']);
    exit();
}
// Remove old client transactions
$conn->query('DELETE FROM client_transactions WHERE sale_id = ' . $sale_id);
// Insert new client transactions
$balance = 0;
$res3 = $conn->query('SELECT SUM(CASE WHEN type="credit" THEN amount ELSE -amount END) FROM client_transactions WHERE client_id = ' . $client_id);
if ($res3 && $row3 = $res3->fetch_row()) {
    $balance = floatval($row3[0]);
}
// Debit: total_amount
$balance -= $total_amount;
$stmt3 = $conn->prepare('INSERT INTO client_transactions (client_id, sale_id, type, amount, date, details, balance_after) VALUES (?, ?, "debit", ?, NOW(), ?, ?)');
$details = 'Sale #' . $sale_id . ' (edited)';
$stmt3->bind_param('iidss', $client_id, $sale_id, $total_amount, $details, $balance);
$stmt3->execute();
$stmt3->close();
// Credit: amount_paid (if any)
if ($amount_paid > 0) {
    $balance += $amount_paid;
    $stmt4 = $conn->prepare('INSERT INTO client_transactions (client_id, sale_id, type, amount, date, details, balance_after) VALUES (?, ?, "credit", ?, NOW(), ?, ?)');
    $details2 = 'Payment for Sale #' . $sale_id . ' (edited)';
    $stmt4->bind_param('iidss', $client_id, $sale_id, $amount_paid, $details2, $balance);
    $stmt4->execute();
    $stmt4->close();
}
echo json_encode(['success' => 'Sale updated successfully.']); 
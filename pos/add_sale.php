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
// Accept multi-product sale
$products = $_POST['products'] ?? [];
if (is_string($products)) {
    $products = json_decode($products, true);
}
$client_id = intval($_POST['client_id'] ?? 0);
$overall_discount = floatval($_POST['overall_discount'] ?? 0);
$amount_paid = floatval($_POST['amount_paid'] ?? 0);
$payment_type = $_POST['payment_type'] ?? 'cash';
if (!is_array($products) || count($products) == 0 || $client_id < 1) {
    echo json_encode(['error' => 'Please fill all fields with valid values.']);
    exit();
}
// Check all product stocks before making any changes 
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
$total_amount = 0;
foreach ($products as $item) {
    $pid = intval($item['product_id'] ?? 0);
    $qty = intval($item['quantity'] ?? 0);
    $price = floatval($item['price'] ?? 0);
    $discount = floatval($item['discount'] ?? 0);
    if ($pid < 1 || $qty < 1 || $price <= 0) {
        echo json_encode(['error' => 'Invalid product data.']);
        exit();
    }
    // Check product stock
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
    $total_amount += ($price * $qty) - $discount;
}
$total_amount -= $overall_discount;
if ($total_amount < 0) $total_amount = 0;
$remaining = $total_amount - $amount_paid;
if ($remaining < 0) $remaining = 0;
// Calculate total items and total discount percentage
$total_items = 0;
$total_discount = 0;
$total_price_before_discount = 0;
foreach ($products as $item) {
    $qty = intval($item['quantity']);
    $price = floatval($item['price']);
    $discount = floatval($item['discount']);
    $total_items += $qty;
    $total_discount += $discount;
    $total_price_before_discount += $qty * $price;
}
$total_discount += $overall_discount;
$discount_percentage = $total_price_before_discount > 0 ? round(($total_discount / $total_price_before_discount) * 100, 2) : 0;
// Insert sale
$stmt = $conn->prepare('INSERT INTO sales (client_id, sale_date, total_amount, overall_discount, amount_paid, payment_type, remaining) VALUES (?, NOW(), ?, ?, ?, ?, ?)');
$stmt->bind_param('idddsd', $client_id, $total_amount, $overall_discount, $amount_paid, $payment_type, $remaining);
if ($stmt->execute()) {
    $sale_id = $conn->insert_id;
    // Insert sale items and update stock
    $products_with_names = [];
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
        // Check if stock went negative (concurrent safety)
        $res_stock = $conn->query('SELECT stock FROM products WHERE id = ' . $pid);
        if ($res_stock && $row_stock = $res_stock->fetch_assoc()) {
            if ($row_stock['stock'] < 0) {
                // Rollback: restore stock and delete sale/sale_items
                $conn->query('UPDATE products SET stock = stock + ' . $qty . ' WHERE id = ' . $pid);
                $conn->query('DELETE FROM sale_items WHERE sale_id = ' . $sale_id);
                $conn->query('DELETE FROM sales WHERE id = ' . $sale_id);
                echo json_encode(['error' => 'Stock for a product went negative. Sale cancelled.']);
                exit();
            }
        }
        // Get product name for response
        $pname = '';
        $res_p = $conn->query('SELECT name FROM products WHERE id = ' . $pid);
        if ($res_p && $row_p = $res_p->fetch_assoc()) {
            $pname = $row_p['name'];
        } else {
            $pname = 'Product #' . $pid;
        }
        $products_with_names[] = [
            'product_id' => $pid,
            'product_name' => $pname,
            'quantity' => $qty,
            'price' => $price,
            'discount' => $discount
        ];
    }
    // Always return products as array
    if (!is_array($products_with_names)) {
        $products_with_names = [];
    }
    // Get client name
    $res2 = $conn->query('SELECT name FROM clients WHERE id = ' . $client_id);
    $client_name = $res2 && $res2->num_rows > 0 ? $res2->fetch_row()[0] : '';
    // Insert client transaction (debit for sale, credit for payment)
    $balance = 0;
    $res3 = $conn->query('SELECT SUM(CASE WHEN type="credit" THEN amount ELSE -amount END) FROM client_transactions WHERE client_id = ' . $client_id);
    if ($res3 && $row3 = $res3->fetch_row()) {
        $balance = floatval($row3[0]);
    }
    // Debit: total_amount
    $balance -= $total_amount;
    $stmt3 = $conn->prepare('INSERT INTO client_transactions (client_id, sale_id, type, amount, date, details, balance_after) VALUES (?, ?, "debit", ?, NOW(), ?, ?)');
    $details = 'Sale #' . $sale_id;
    $stmt3->bind_param('iidss', $client_id, $sale_id, $total_amount, $details, $balance);
    $stmt3->execute();
    $stmt3->close();
    // Credit: amount_paid (if any)
    if ($amount_paid > 0) {
        $balance += $amount_paid;
        $stmt4 = $conn->prepare('INSERT INTO client_transactions (client_id, sale_id, type, amount, date, details, balance_after) VALUES (?, ?, "credit", ?, NOW(), ?, ?)');
        $details2 = 'Payment for Sale #' . $sale_id;
        $stmt4->bind_param('iidss', $client_id, $sale_id, $amount_paid, $details2, $balance);
        $stmt4->execute();
        $stmt4->close();
    }
    $sale_date = date('Y-m-d H:i:s');
    $actions = '';
    echo json_encode([
        'success' => 'Sale recorded and stock updated!',
        'sale' => [
            'id' => $sale_id,
            'client' => $client_name,
            'sale_date' => $sale_date,
            'total_amount' => number_format($total_amount, 2),
            'overall_discount' => number_format($overall_discount, 2),
            'amount_paid' => number_format($amount_paid, 2),
            'remaining' => number_format($remaining, 2),
            'products' => $products_with_names,
            'total_items' => $total_items,
            'discount_percentage' => $discount_percentage
        ],
        'actions' => $actions,
        'balance' => number_format($balance, 2)
    ]);
} else {
    echo json_encode(['error' => 'Error: Could not record sale.']);
}
$stmt->close(); 
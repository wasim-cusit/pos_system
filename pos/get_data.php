<?php
include 'db.php';
header('Content-Type: application/json');
if (isset($_GET['sale_id'])) {
    $sale_id = intval($_GET['sale_id']);
    $res = $conn->query("SELECT * FROM sales WHERE id = $sale_id");
    if ($res && $sale = $res->fetch_assoc()) {
        $products = [];
        $res2 = $conn->query("SELECT product_id, quantity, price, discount FROM sale_items WHERE sale_id = $sale_id");
        while ($row2 = $res2->fetch_assoc()) {
            $products[] = $row2;
        }
        $sale['products'] = $products;
        echo json_encode(['success' => true, 'sale' => $sale]);
    } else {
        echo json_encode(['error' => 'Sale not found.']);
    }
    exit();
}
// ... existing code ... 
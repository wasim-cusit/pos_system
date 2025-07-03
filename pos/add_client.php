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
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$address = trim($_POST['address'] ?? '');
if (strlen($name) < 2) {
    echo json_encode(['error' => 'Client name is required.']);
    exit();
}
$stmt = $conn->prepare('INSERT INTO clients (name, phone, email, address) VALUES (?, ?, ?, ?)');
$stmt->bind_param('ssss', $name, $phone, $email, $address);
if ($stmt->execute()) {
    $id = $conn->insert_id;
    $actions = '<button class="btn btn-sm btn-warning edit-client-btn" data-id="' . $id . '" data-name="' . htmlspecialchars($name, ENT_QUOTES) . '" data-phone="' . htmlspecialchars($phone, ENT_QUOTES) . '" data-email="' . htmlspecialchars($email, ENT_QUOTES) . '" data-address="' . htmlspecialchars($address, ENT_QUOTES) . '"><i class="bi bi-pencil"></i> Edit</button>';
    echo json_encode(['success' => 'Client added successfully!', 'client' => ['id' => $id, 'name' => $name, 'phone' => $phone, 'email' => $email, 'address' => $address], 'actions' => $actions]);
} else {
    echo json_encode(['error' => 'Error: Could not add client.']);
}
$stmt->close(); 
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
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$address = trim($_POST['address'] ?? '');
if ($id < 1 || strlen($name) < 2) {
    echo json_encode(['error' => 'Invalid input']);
    exit();
}
$stmt = $conn->prepare('UPDATE clients SET name=?, phone=?, email=?, address=? WHERE id=?');
$stmt->bind_param('ssssi', $name, $phone, $email, $address, $id);
if ($stmt->execute()) {
    echo json_encode(['success' => 'Client updated successfully!']);
} else {
    echo json_encode(['error' => 'Could not update client.']);
}
$stmt->close(); 
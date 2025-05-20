<?php
include 'connect.php';

$order_id = isset($_GET["order_id"]) ? (int)$_GET["order_id"] : 0;

$sql = "SELECT Status FROM orders WHERE OrderID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

header('Content-Type: application/json');
echo json_encode(['status' => $result['Status'] ?? 'Unknown']);

$stmt->close();
$conn->close();
?>
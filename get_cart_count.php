<?php
session_start();
include 'connect.php';

if (!isset($_SESSION['CustomerID'])) {
    header('Content-Type: application/json');
    echo json_encode(['count' => 0]);
    exit();
}

$customerID = (int)$_SESSION['CustomerID'];

try {
    $query = "SELECT SUM(Quantity) as total FROM cart WHERE CustomerID = ?";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Failed to prepare statement: ' . $conn->error);
    }
    $stmt->bind_param('i', $customerID);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $count = (int)($row['total'] ?? 0);

    header('Content-Type: application/json');
    echo json_encode(['count' => $count]);

    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['count' => 0, 'error' => $e->getMessage()]);
    exit();
}
?>
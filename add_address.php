<?php
session_start();
include 'connect.php';

if (!isset($_SESSION['CustomerID'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$customerID = $_SESSION['CustomerID'];
$addressType = isset($_POST['addressType']) ? trim($_POST['addressType']) : '';
$addressLine1 = isset($_POST['addressLine1']) ? trim($_POST['addressLine1']) : '';
$addressLine2 = isset($_POST['addressLine2']) ? trim($_POST['addressLine2']) : '';

if (empty($addressLine1)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Address Line 1 is required']);
    exit;
}

try {
    // Removed the 'City' column from the query
    $stmt = $conn->prepare("INSERT INTO address (CustomerID, AddressType, AddressLine1, AddressLine2) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $customerID, $addressType, $addressLine1, $addressLine2);
    $stmt->execute();
    $addressID = $conn->insert_id;
    $stmt->close();
    $conn->close();

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'addressID' => $addressID]);
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error adding address: ' . $e->getMessage()]);
}
?>
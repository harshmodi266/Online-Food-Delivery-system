<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'connect.php';

// Ensure the user is logged in
if (!isset($_SESSION['CustomerID'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

$customerID = (int)$_SESSION['CustomerID'];

// Handle the AJAX request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$cartID = isset($_POST['cart_id']) ? (int)$_POST['cart_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;

// Validate inputs
if ($cartID <= 0 || $quantity < 1) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid cart ID or quantity']);
    exit();
}

try {
    // Update the quantity in the database
    $updateQuery = "UPDATE cart SET Quantity = ? WHERE CartID = ? AND CustomerID = ?";
    $stmt = $conn->prepare($updateQuery);
    if (!$stmt) {
        throw new Exception('Failed to prepare statement: ' . $conn->error);
    }
    $stmt->bind_param('iii', $quantity, $cartID, $customerID);
    if ($stmt->execute()) {
        // Get the updated cart count
        $countQuery = "SELECT SUM(Quantity) as total FROM cart WHERE CustomerID = ?";
        $countStmt = $conn->prepare($countQuery);
        $countStmt->bind_param('i', $customerID);
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $countRow = $countResult->fetch_assoc();
        $cartCount = (int)($countRow['total'] ?? 0);
        $countStmt->close();

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'cart_count' => $cartCount]);
    } else {
        throw new Exception('Failed to update quantity: ' . $stmt->error);
    }
    $stmt->close();
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit();
}

$conn->close();
?>
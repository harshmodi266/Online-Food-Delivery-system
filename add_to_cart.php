<?php
session_start();
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

// Get and validate the menu ID
$menuID = isset($_POST['menu_id']) ? (int)$_POST['menu_id'] : 0;
if ($menuID <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid menu ID']);
    exit();
}

try {
    // Validate that the menu item exists
    $menuCheckQuery = "SELECT MenuID FROM menu WHERE MenuID = ?";
    $menuStmt = $conn->prepare($menuCheckQuery);
    $menuStmt->bind_param('i', $menuID);
    $menuStmt->execute();
    $menuResult = $menuStmt->get_result();
    if ($menuResult->num_rows === 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Menu item does not exist']);
        $menuStmt->close();
        exit();
    }
    $menuStmt->close();

    // Check if item already exists in the cart
    $checkQuery = "SELECT * FROM cart WHERE CustomerID = ? AND MenuID = ?";
    $stmt = $conn->prepare($checkQuery);
    if (!$stmt) {
        throw new Exception('Failed to prepare statement: ' . $conn->error);
    }
    $stmt->bind_param('ii', $customerID, $menuID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Update quantity if item exists
        $updateQuery = "UPDATE cart SET Quantity = Quantity + 1 WHERE CustomerID = ? AND MenuID = ?";
        $updateStmt = $conn->prepare($updateQuery);
        if (!$updateStmt) {
            throw new Exception('Failed to prepare update statement: ' . $conn->error);
        }
        $updateStmt->bind_param('ii', $customerID, $menuID);
        if ($updateStmt->execute()) {
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
            echo json_encode(['success' => true, 'message' => 'Item quantity updated', 'cart_count' => $cartCount]);
        } else {
            throw new Exception('Failed to update quantity: ' . $updateStmt->error);
        }
        $updateStmt->close();
    } else {
        // Insert new cart item
        $insertQuery = "INSERT INTO cart (CustomerID, MenuID, Quantity, DateAdded) VALUES (?, ?, 1, NOW())";
        $insertStmt = $conn->prepare($insertQuery);
        if (!$insertStmt) {
            throw new Exception('Failed to prepare insert statement: ' . $conn->error);
        }
        $insertStmt->bind_param('ii', $customerID, $menuID);
        if ($insertStmt->execute()) {
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
            echo json_encode(['success' => true, 'message' => 'Item added to cart', 'cart_count' => $cartCount]);
        } else {
            throw new Exception('Failed to add item to cart: ' . $insertStmt->error);
        }
        $insertStmt->close();
    }
    $stmt->close();
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit();
}

// Close the database connection
$conn->close();
?>
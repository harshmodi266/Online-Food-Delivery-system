<?php
session_start();

// Check if customer is logged in
if (!isset($_SESSION['CustomerID'])) {
    header("Location: login.php"); // Redirect to login page if not logged in
    exit();
}

$customerId = $_SESSION['CustomerID'];
$successMessage = '';
$errorMessage = '';

// Database connection
$conn = new mysqli("127.0.0.1", "root", "", "lazeez");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle order cancellation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cancel_order'])) {
    $orderId = intval($_POST['order_id']);

    // Check if the order belongs to the customer and is cancellable
    $stmt = $conn->prepare("SELECT Status FROM orders WHERE OrderID = ? AND CustomerID = ?");
    $stmt->bind_param("ii", $orderId, $customerId);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    $stmt->close();

    if ($order) {
        if ($order['Status'] === 'Pending') {
            // Update the order status to Canceled
            $stmt = $conn->prepare("UPDATE orders SET Status = 'Canceled' WHERE OrderID = ?");
            $stmt->bind_param("i", $orderId);
            if ($stmt->execute()) {
                $successMessage = "Order #$orderId has been canceled successfully!";
            } else {
                $errorMessage = "Error canceling order: " . $conn->error;
            }
            $stmt->close();
        } else {
            $errorMessage = "Order #$orderId cannot be canceled. It is already " . $order['Status'] . ".";
        }
    } else {
        $errorMessage = "Order not found or you do not have permission to cancel it.";
    }
}

// Fetch customer's orders
$stmt = $conn->prepare("SELECT OrderID, OrderDate, TotalAmount, Status FROM orders WHERE CustomerID = ? ORDER BY OrderDate DESC");
$stmt->bind_param("i", $customerId);
$stmt->execute();
$ordersResult = $stmt->get_result();
$orders = $ordersResult->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancel Order - Lazeez Restaurant</title>
    <link href="css/style.css" rel="stylesheet" type="text/css">
    <style>
        /* Unique class names for cancel order page */
        .profile-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 30px;
            background: #fff;
            border: 2px solid #0A1F44;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            min-height: 400px;
        }

        .profile-heading {
            text-align: center;
            font-family: 'Georgia', serif;
            color: #0A1F44;
            font-size: 2rem;
            margin-bottom: 30px;
        }

        .profile-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .profile-table th,
        .profile-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #D4AF37;
        }

        .profile-table th {
            background: #FAF3E0;
            color: #0A1F44;
            font-weight: bold;
        }

        .profile-table tr.non-editable {
            background: #e0e0e0; /* Darkened background for non-editable rows */
            filter: grayscale(50%);
            opacity: 0.7;
        }

        .profile-table tr.non-editable td {
            color: #666; /* Darkened text color */
        }

        .profile-table td {
            background: #fff;
            color: #2E2E2E;
        }

        .profile-btn {
            padding: 8px 16px;
            background: #D4AF37;
            color: #0A1F44;
            font-size: 14px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s, transform 0.2s;
        }

        .profile-btn:disabled {
            background: #e0e0e0;
            color: #666;
            cursor: not-allowed;
        }

        .profile-btn:hover:not(:disabled) {
            background: #B9972E;
            transform: scale(1.05);
        }

        .profile-success-message {
            color: #D4AF37;
            font-size: 16px;
            margin-top: 20px;
            text-align: center;
            display: <?php echo $successMessage ? 'block' : 'none'; ?>;
        }

        .profile-error-message {
            color: red;
            font-size: 16px;
            margin-top: 20px;
            text-align: center;
            display: <?php echo $errorMessage ? 'block' : 'none'; ?>;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .profile-container {
                max-width: 350px;
                margin: 20px auto;
                padding: 20px;
            }

            .profile-table th,
            .profile-table td {
                padding: 8px;
                font-size: 14px;
            }

            .profile-btn {
                padding: 6px 12px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="profile-container">
        <div class="profile-heading">Cancel Your Orders</div>

        <?php if (empty($orders)): ?>
            <p style="text-align: center; color: #2E2E2E;">You have no orders to cancel.</p>
        <?php else: ?>
            <table class="profile-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Date</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr class="<?php echo $order['Status'] !== 'Pending' ? 'non-editable' : ''; ?>">
                            <td>#<?php echo htmlspecialchars($order['OrderID']); ?></td>
                            <td><?php echo htmlspecialchars(date('Y-m-d H:i:s', strtotime($order['OrderDate']))); ?></td>
                            <td>₹<?php echo number_format($order['TotalAmount'], 2); ?></td>
                            <td><?php echo htmlspecialchars($order['Status']); ?></td>
                            <td>
                                <form method="POST" action="" style="display: inline;">
                                    <input type="hidden" name="order_id" value="<?php echo $order['OrderID']; ?>">
                                    <button type="submit" name="cancel_order" class="profile-btn" 
                                            <?php echo $order['Status'] !== 'Pending' ? 'disabled' : ''; ?>>
                                        Cancel
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <p id="successMessage" class="profile-success-message"><?php echo htmlspecialchars($successMessage); ?></p>
        <p id="errorMessage" class="profile-error-message"><?php echo htmlspecialchars($errorMessage); ?></p>
    </div>
</body>
</html>
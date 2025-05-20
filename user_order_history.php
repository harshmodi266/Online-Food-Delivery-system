<?php
include 'connect.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION["CustomerID"])) {
    header("Location: login.php");
    exit;
}

$CustomerID = $_SESSION["CustomerID"];
$successMessage = '';
$errorMessage = '';

// Helper function to format prices with the rupee symbol
function formatPrice($price) {
    return "₹" . number_format($price, 2, '.', '');
}

$page_title = "Order History";
include 'header.php';

// Handle order cancellation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cancel_order'])) {
    $orderId = intval($_POST['order_id']);

    // Check if the order belongs to the customer and is cancellable
    $stmt = $conn->prepare("SELECT Status FROM orders WHERE OrderID = ? AND CustomerID = ?");
    $stmt->bind_param("ii", $orderId, $CustomerID);
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

// Fetch Orders
$sql = "SELECT o.OrderID, o.OrderDate, o.TotalAmount, o.Status, 
               p.PaymentMethod, p.PaymentStatus 
        FROM orders o 
        LEFT JOIN payment p ON o.PaymentID = p.PaymentID 
        WHERE o.CustomerID = ? 
        ORDER BY o.OrderDate DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $CustomerID);
$stmt->execute();
$orders = $stmt->get_result();
?>

<div>
    <h2>Order History</h2>
    
    <!-- Display success or error message -->
    <?php if ($successMessage) { ?>
        <p style="color: green;"><?php echo htmlspecialchars($successMessage); ?></p>
    <?php } elseif ($errorMessage) { ?>
        <p style="color: red;"><?php echo htmlspecialchars($errorMessage); ?></p>
    <?php } elseif (isset($_GET["success"])) { ?>
        <p style="color: green;"><?php echo htmlspecialchars($_GET["success"]); ?></p>
    <?php } ?>

    <?php if ($orders->num_rows > 0) { ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Order Date</th>
                    <th>Total Amount</th>
                    <th>Payment Method</th>
                    <th>Payment Status</th>
                    <th>Order Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($order = $orders->fetch_assoc()) { ?>
                <tr class="<?php echo $order['Status'] === 'Canceled' ? 'canceled-order' : ''; ?>">
                    <td><?php echo htmlspecialchars($order["OrderID"]); ?></td>
                    <td><?php echo htmlspecialchars($order["OrderDate"]); ?></td>
                    <td><?php echo formatPrice($order["TotalAmount"]); ?></td>
                    <td><?php echo htmlspecialchars($order["PaymentMethod"] ?: 'COD'); ?></td>
                    <td><?php echo htmlspecialchars($order["PaymentStatus"] ?: ($order["PaymentMethod"] ? 'Completed' : 'Pending')); ?></td>
                    <td><?php echo htmlspecialchars($order["Status"]); ?></td>
                    <td>
                        <a href="user_order_details.php?order_id=<?php echo htmlspecialchars($order['OrderID']); ?>" class="btn">View Bill</a>
                        <?php if ($order['Status'] === 'Pending') { ?>
                            <form method="POST" action="" style="display: inline; margin-left: 10px;">
                                <input type="hidden" name="order_id" value="<?php echo $order['OrderID']; ?>">
                                <button type="submit" name="cancel_order" class="btn cancel-btn">Cancel Order</button>
                            </form>
                        <?php } ?>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    <?php } else { ?>
        <p>You have no past orders.</p>
    <?php } ?>
</div>

<?php 
$stmt->close();
$conn->close();
include 'footer.php';
?>
<!-- 
<style>
    .table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    .table th,
    .table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #D4AF37;
    }

    .table th {
        background: #FAF3E0;
        color: #0A1F44;
        font-weight: bold;
    }

    .table td {
        background: #fff;
        color: #2E2E2E;
    }

    .canceled-order {
        background: #e0e0e0;
        filter: grayscale(50%);
        opacity: 0.7;
    }

    .canceled-order td {
        color: #666;
    }

    .btn {
        padding: 8px 16px;
        background: #D4AF37;
        color: #0A1F44;
        font-size: 14px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        text-decoration: none;
        transition: background 0.3s, transform 0.2s;
    }

    .btn:hover {
        background: #B9972E;
        transform: scale(1.05);
    }

    .cancel-btn:disabled {
        background: #e0e0e0;
        color: #666;
        cursor: not-allowed;
    }

    @media (max-width: 768px) {
        .table th,
        .table td {
            padding: 8px;
            font-size: 14px;
        }

        .btn {
            padding: 6px 12px;
            font-size: 12px;
        }
    }
</style> -->
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
$OrderID = isset($_GET["order_id"]) ? (int)$_GET["order_id"] : 0;

// Helper function to format prices with the rupee symbol
function formatPrice($price) {
    return "₹" . number_format($price, 2, '.', '');
}

$page_title = "Order Details - Invoice";
include 'header.php';

// Fetch Order Details, including Delivery Boy info
$sql = "SELECT o.OrderID, o.OrderDate, o.TotalAmount, o.Status, 
               p.PaymentMethod, p.PaymentStatus, p.TransactionID,
               a.AddressLine1, a.AddressLine2, 
               c.Name AS CustomerName, c.PhoneNumber AS CustomerPhone,
               d.Name AS DeliveryBoyName, d.PhoneNumber AS DeliveryBoyPhone
        FROM orders o 
        JOIN address a ON o.AddressID = a.AddressID 
        JOIN customer c ON o.CustomerID = c.CustomerID
        LEFT JOIN payment p ON o.PaymentID = p.PaymentID 
        LEFT JOIN deliveryboy d ON o.DeliveryBoyID = d.DeliveryBoyID
        WHERE o.OrderID = ? AND o.CustomerID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $OrderID, $CustomerID);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

// Fetch Order Items
$items_sql = "SELECT oi.OrderItemID, oi.Quantity, oi.ItemPrice, m.DishName 
              FROM orderitems oi 
              JOIN menu m ON oi.MenuID = m.MenuID 
              WHERE oi.OrderID = ?";
$items_stmt = $conn->prepare($items_sql);
$items_stmt->bind_param("i", $OrderID);
$items_stmt->execute();
$items = $items_stmt->get_result();

// Calculate Subtotal, GST, Delivery Charges, and Total
$subtotal = 0;
$items_array = [];
while ($item = $items->fetch_assoc()) {
    $items_array[] = $item;
    $subtotal += $item["Quantity"] * $item["ItemPrice"];
}

// Define tax rates and delivery charges
$cgst_rate = 2.5; // 2.5%
$sgst_rate = 2.5; // 2.5%
$delivery_charge = 10.00; // Fixed delivery charge (adjust as needed)

$cgst = ($subtotal * $cgst_rate) / 100;
$sgst = ($subtotal * $sgst_rate) / 100;
$total_tax = $cgst + $sgst;
$grand_total = $subtotal + $total_tax + $delivery_charge;

// Determine if QR code should be shown (for COD with pending payment)
$show_qr = false;
if ($order && $order["PaymentMethod"] === 'Cash' && $order["PaymentStatus"] === 'Pending') {
    $show_qr = true;
}
?>

<style>
.invoice-container {
    max-width: 800px;
    margin: 20px auto;
    padding: 20px;
    border: 1px solid #ddd;
    font-family: Arial, sans-serif;
    background-color: #fff;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}

.invoice-header {
    text-align: center;
    border-bottom: 2px dashed #000;
    padding-bottom: 10px;
    margin-bottom: 20px;
}

.invoice-header img {
    max-width: 100px;
}

.invoice-details {
    display: flex;
    justify-content: space-between;
    margin-bottom: 20px;
}

.invoice-details div {
    width: 48%;
}

.invoice-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

.invoice-table th, .invoice-table td {
    border: 1px solid #ddd;
    padding: 8px;
    text-align: left;
}

.invoice-table th {
    background-color: rgb(43, 42, 42);
    color: #fff; /* Added for better readability */
}

.invoice-summary {
    text-align: right;
    margin-bottom: 20px;
}

.qr-code {
    text-align: center;
    margin-top: 20px;
}

.qr-code img {
    max-width: 150px;
}

.footer {
    text-align: center;
    margin-top: 20px;
    font-size: 14px;
    color: #555;
}
</style>

<div class="invoice-container">
    <!-- Invoice Header -->
    <div class="invoice-header">
        <h1>Lazeez Restaurant</h1>
        <p>11/2 Sector 37, Vadodara-390007</p>
        <p>Ph. No.: 9054996724</p>
        <p>GSTIN: 24AAACL1234A1Z5</p>
        <h3>Invoice</h3>
        <p>Invoice Number: INV<?php echo str_pad($OrderID, 6, '0', STR_PAD_LEFT); ?></p>
        <p>Invoice Date: <?php echo date('d-M-y H:i', strtotime($order["OrderDate"])); ?></p>
    </div>

    <!-- Customer and Delivery Info -->
    <?php if ($order) { ?>
        <div class="invoice-details">
            <div>
                <h4>Customer Information</h4>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($order["CustomerName"]); ?></p>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($order["CustomerPhone"]); ?></p>
                <p><strong>Address:</strong> 
                    <?php echo htmlspecialchars($order["AddressLine1"] . ($order["AddressLine2"] ? ', ' . $order["AddressLine2"] : '')); ?>
                </p>
            </div>
            <div>
                <h4>Delivery Information</h4>
                <p><strong>Delivery Boy:</strong> <?php echo htmlspecialchars($order["DeliveryBoyName"] ?: 'Not Assigned'); ?></p>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($order["DeliveryBoyPhone"] ?: 'N/A'); ?></p>
                <p><strong>Order Status:</strong> <?php echo htmlspecialchars($order["Status"]); ?></p>
                <p><strong>Payment Status:</strong> <?php echo htmlspecialchars($order["PaymentStatus"] ?: ($order["PaymentMethod"] ? 'Completed' : 'Pending')); ?></p>
                <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($order["PaymentMethod"] ?: 'COD'); ?></p>
                <?php if ($order["TransactionID"]) { ?>
                    <p><strong>Transaction ID:</strong> <?php echo htmlspecialchars($order["TransactionID"]); ?></p>
                <?php } ?>
            </div>
        </div>

        <!-- Order Items Table -->
        <h4>Order Summary</h4>
        <table class="invoice-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Qty.</th>
                    <th>Rate</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($items_array) > 0) { ?>
                    <?php foreach ($items_array as $item) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item["DishName"]); ?></td>
                        <td><?php echo htmlspecialchars($item["Quantity"]); ?></td>
                        <td><?php echo formatPrice($item["ItemPrice"]); ?></td>
                        <td><?php echo formatPrice($item["Quantity"] * $item["ItemPrice"]); ?></td>
                    </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="4">No items found for this order.</td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>

        <!-- Order Summary with Taxes -->
        <div class="invoice-summary">
            <p><strong>Total Qty:</strong> <?php echo count($items_array); ?></p>
            <p><strong>Sub Total:</strong> <?php echo formatPrice($subtotal); ?></p>
            <p><strong>CGST @<?php echo $cgst_rate; ?>%:</strong> <?php echo formatPrice($cgst); ?></p>
            <p><strong>SGST @<?php echo $sgst_rate; ?>%:</strong> <?php echo formatPrice($sgst); ?></p>
            <p><strong>Delivery Charges:</strong> <?php echo formatPrice($delivery_charge); ?></p>
            <p><strong>Total Tax:</strong> <?php echo formatPrice($total_tax); ?></p>
            <p><strong>Grand Total:</strong> <?php echo formatPrice($grand_total); ?></p>
        </div>

        <!-- QR Code for COD Payment (Static Image) -->
        <?php if ($show_qr) { ?>
            <div class="qr-code">
                <h4>Scan to Pay Online (COD)</h4>
                <img src="images/qr_code.jpg" alt="QR Code for Payment">
                <p>Amount: <?php echo formatPrice($grand_total); ?></p>
                <p>Please scan the QR code to pay via UPI or other online methods.</p>
            </div>
        <?php } ?>

    <?php } else { ?>
        <p>Order not found or does not belong to you.</p>
    <?php } ?>

    <!-- Footer -->
    <div class="footer">
        <p>Thank You for Your Order!</p>
        <p>Visit Again at Lazeez Restaurant</p>
    </div>
</div>

<a href="user_order_history.php" class="btn" style="display: block; text-align: center; margin: 20px auto;">Back to Order History</a>

<?php 
$stmt->close();
$items_stmt->close();
$conn->close();
include 'footer.php'; 
?>
<script>
// Update cart count on page load
document.addEventListener('DOMContentLoaded', () => {
    fetch('get_cart_count.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to fetch cart count');
            }
            return response.json();
        })
        .then(data => {
            const cartCount = document.getElementById('cart-count');
            if (cartCount) {
                cartCount.textContent = data.count || 0;
            }
        })
        .catch(error => {
            console.error("Error fetching cart count:", error);
        });
});
</script>
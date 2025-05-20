<?php
session_start();
include 'connect.php';

// Redirect to login if not logged in
if (!isset($_SESSION['CustomerID'])) {
    header("Location: login.php");
    exit();
}

$customerID = $_SESSION['CustomerID'];

// Fetch total amount from cart
$query = "SELECT SUM(m.Price * c.Quantity) AS TotalAmount 
          FROM cart c 
          JOIN menu m ON c.MenuID = m.MenuID 
          WHERE c.CustomerID = '$customerID'";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$totalAmount = $row['TotalAmount'];

// Handle payment selection
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_payment'])) {
    $paymentMethod = mysqli_real_escape_string($conn, $_POST['payment_method']);
    $_SESSION['payment_method'] = $paymentMethod;
    
    if ($paymentMethod == 'Online') {
        // Redirect to a payment gateway page (Simulating for now)
        header("Location: online_payment.php");
        exit();
    } else {
        // Redirect to order confirmation for COD
        header("Location: confirm_order.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - Lazeez Restaurant</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php //include 'header.php'; ?>
    <div class="payment-container">
        <h2>Select Payment Method</h2>
        <form method="POST" action="payment.php">
            <label for="payment_method">Choose Payment Method:</label>
            <select name="payment_method" id="payment_method" required>
                <option value="COD">Cash on Delivery</option>
                <option value="Online">Online Payment</option>
            </select>
            
            <h3>Total Amount: ₹<?php echo number_format($totalAmount, 2); ?></h3>
            <button type="submit" name="confirm_payment">Proceed to Payment</button>
        </form>
    </div>
</body>
</html>

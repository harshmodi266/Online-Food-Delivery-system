<?php
session_start();
include 'connect.php';

// Redirect if payment method is not online
if (!isset($_SESSION['payment_method']) || $_SESSION['payment_method'] != 'Online') {
    header("Location: payment.php");
    exit();
}

$customerID = $_SESSION['CustomerID'];

// Fetch total amount
$query = "SELECT SUM(m.Price * c.Quantity) AS TotalAmount 
          FROM cart c 
          JOIN menu m ON c.MenuID = m.MenuID 
          WHERE c.CustomerID = '$customerID'";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$totalAmount = $row['TotalAmount'];

// Simulate payment processing
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['pay_now'])) {
    $_SESSION['payment_status'] = 'Success';
    header("Location: confirm_order.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Payment - Lazeez Restaurant</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php //include 'header.php'; ?>
    <div class="payment-container">
        <h2>Online Payment Simulation</h2>
        <p>Order Amount: ₹<?php echo number_format($totalAmount, 2); ?></p>
        <p>Click the button below to simulate a successful payment.</p>
        
        <form method="POST" action="online_payment.php">
            <button type="submit" name="pay_now">Simulate Payment</button>
        </form>
    </div>
</body>
</html>
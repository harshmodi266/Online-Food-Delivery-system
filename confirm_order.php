<?php
session_start();
include 'connect.php';


// Redirect if payment is not completed
if (!isset($_SESSION['payment_method'])) {
    header("Location: payment.php");
    exit();
}

$customerID = $_SESSION['CustomerID'];
$paymentMethod = $_SESSION['payment_method'];
$paymentStatus = isset($_SESSION['payment_status']) ? $_SESSION['payment_status'] : 'Pending';

// Fetch latest address
$addressQuery = "SELECT * FROM address WHERE CustomerID = '$customerID' ORDER BY AddressID DESC LIMIT 1";
$addressResult = mysqli_query($conn, $addressQuery);
$address = mysqli_fetch_assoc($addressResult);

// Fetch cart items
$cartQuery = "SELECT c.MenuID, m.DishName, m.Price, c.Quantity FROM cart c 
              JOIN menu m ON c.MenuID = m.MenuID 
              WHERE c.CustomerID = '$customerID'";
$cartResult = mysqli_query($conn, $cartQuery);
$cartItems = mysqli_fetch_all($cartResult, MYSQLI_ASSOC);

// Calculate total
$totalAmount = 0;
foreach ($cartItems as $item) {
    $totalAmount += $item['Price'] * $item['Quantity'];
}

// Assign delivery boy
$deliveryBoyQuery = "SELECT * FROM deliveryboy ORDER BY RAND() LIMIT 1";
$deliveryBoyResult = mysqli_query($conn, $deliveryBoyQuery);
$deliveryBoy = mysqli_fetch_assoc($deliveryBoyResult);

// Store order in database
$orderQuery = "INSERT INTO orders (CustomerID, AddressID, TotalAmount, PaymentMethod, PaymentStatus, OrderDate, DeliveryBoyID) 
               VALUES ('$customerID', '{$address['AddressID']}', '$totalAmount', '$paymentMethod', '$paymentStatus', NOW(), '{$deliveryBoy['DeliveryBoyID']}')";
mysqli_query($conn, $orderQuery);
$orderID = mysqli_insert_id($conn);

// Store order items
foreach ($cartItems as $item) {
    mysqli_query($conn, "INSERT INTO orderitems (OrderID, MenuID, Quantity, ItemPrice) 
                     VALUES ('$orderID', '{$item['MenuID']}', '{$item['Quantity']}', '{$item['Price']}')");
}

// Clear cart
mysqli_query($conn, "DELETE FROM cart WHERE CustomerID = '$customerID'");

// Unset session variables after order placement
unset($_SESSION['payment_method']);
unset($_SESSION['payment_status']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - Lazeez Restaurant</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include 'header.php'; ?>
    <div class="confirmation-container">
        <h2>Order Confirmed!</h2>
        <p>Order ID: <strong>#<?php echo $orderID; ?></strong></p>
        <h3>Order Summary</h3>
        <ul>
            <?php foreach ($cartItems as $item): ?>
                <li><?php echo $item['DishName']; ?> - ₹<?php echo number_format($item['Price'], 2); ?> x <?php echo $item['Quantity']; ?></li>
            <?php endforeach; ?>
        </ul>
        <h3>Total Amount: ₹<?php echo number_format($totalAmount, 2); ?></h3>
        <h3>Payment Method: <?php echo $paymentMethod; ?></h3>
        <h3>Delivery Address:</h3>
        <p><?php echo htmlspecialchars($address['AddressLine1']) . ', ' . htmlspecialchars($address['AddressLine2']); ?></p>
        <h3>Delivery Boy Details:</h3>
        <p>Name: <?php echo $deliveryBoy['Name']; ?> | Phone: <?php echo $deliveryBoy['PhoneNumber']; ?></p>
    </div>
</body>
</html>

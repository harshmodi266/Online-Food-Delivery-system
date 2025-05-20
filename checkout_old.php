<?php
session_start();
include 'connect.php';

// Redirect to login if not logged in
if (!isset($_SESSION['CustomerID'])) {
    header("Location: login.php");
    exit();
}

$customerID = $_SESSION['CustomerID'];

// Fetch cart items from database
$query = "SELECT c.CartID, m.DishName, m.Price, c.Quantity, m.Image 
          FROM cart c 
          JOIN menu m ON c.MenuID = m.MenuID 
          WHERE c.CustomerID = '$customerID'";
$result = mysqli_query($conn, $query);
$cartItems = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Calculate total amount
$total = 0;
foreach ($cartItems as $item) {
    $total += $item['Price'] * $item['Quantity'];
}

// Fetch existing addresses
$addressQuery = "SELECT * FROM address WHERE CustomerID = '$customerID'";
$addressResult = mysqli_query($conn, $addressQuery);
$addresses = mysqli_fetch_all($addressResult, MYSQLI_ASSOC);

// Fetch available delivery boys
$deliveryBoyQuery = "SELECT * FROM deliveryboy ORDER BY RAND() LIMIT 1";
$deliveryBoyResult = mysqli_query($conn, $deliveryBoyQuery);
$deliveryBoy = mysqli_fetch_assoc($deliveryBoyResult);

// Handle order submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_order'])) {
    $selectedAddressID = $_POST['address_id'];
    $paymentMethod = mysqli_real_escape_string($conn, $_POST['payment_method']);

    // Insert new address if "new_address" is selected
    if ($selectedAddressID == 'new') {
        $fullAddress = mysqli_real_escape_string($conn, $_POST['full_address']);
        $city = mysqli_real_escape_string($conn, $_POST['city']);
        $state = mysqli_real_escape_string($conn, $_POST['state']);
        $zipCode = mysqli_real_escape_string($conn, $_POST['zip_code']);
        
        $insertAddress = "INSERT INTO address (CustomerID, FullAddress, City, State, ZipCode) 
                         VALUES ('$customerID', '$fullAddress', '$city', '$state', '$zipCode')";
        mysqli_query($conn, $insertAddress);
        $selectedAddressID = mysqli_insert_id($conn);
    }

    // Insert order details
    $orderQuery = "INSERT INTO orders (CustomerID, AddressID, TotalAmount, PaymentMethod, OrderDate, DeliveryBoyID) 
                   VALUES ('$customerID', '$selectedAddressID', '$total', '$paymentMethod', NOW(), '{$deliveryBoy['DeliveryBoyID']}')";
    mysqli_query($conn, $orderQuery);
    $orderID = mysqli_insert_id($conn);

    // Insert order items
    foreach ($cartItems as $item) {
        $menuID = $item['CartID'];
        $quantity = $item['Quantity'];
        $price = $item['Price'];
        mysqli_query($conn, "INSERT INTO order_items (OrderID, MenuID, Quantity, Price) VALUES ('$orderID', '$menuID', '$quantity', '$price')");
    }

    // Clear cart after order placement
    mysqli_query($conn, "DELETE FROM cart WHERE CustomerID = '$customerID'");
    $_SESSION['order_id'] = $orderID;
    header("Location: invoice.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Lazeez Restaurant</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include 'header.php'; ?>
    <div class="checkout-container">
        <h2>Checkout</h2>
        
        <div id="step-1">
            <h3>Step 1: Enter Delivery Address</h3>
            <form id="address-form">
                <label for="address_id">Select Address:</label>
                <select name="address_id" id="address_id" required>
                    <?php foreach ($addresses as $addr): ?>
                        <option value="<?php echo $addr['AddressID']; ?>">
                            <?php echo htmlspecialchars($addr['FullAddress']) . ', ' . htmlspecialchars($addr['City']) . ', ' . htmlspecialchars($addr['State']) . ' - ' . htmlspecialchars($addr['ZipCode']); ?>
                        </option>
                    <?php endforeach; ?>
                    <option value="new">Add New Address</option>
                </select>
                <button type="button" onclick="nextStep(2)">Next</button>
            </form>
        </div>

        <div id="step-2" style="display: none;">
            <h3>Step 2: Choose Payment Method</h3>
            <form id="payment-form">
                <label for="payment_method">Payment Method:</label>
                <select name="payment_method" id="payment_method" required>
                    <option value="COD">Cash on Delivery</option>
                    <option value="Online">Online Payment</option>
                </select>
                <button type="button" onclick="nextStep(3)">Next</button>
            </form>
        </div>

        <div id="step-3" style="display: none;">
            <h3>Step 3: Confirm Order</h3>
            <h4>Order Summary</h4>
            <p>Total Amount: ₹<?php echo number_format($total, 2); ?></p>
            <p>Delivery by: <?php echo htmlspecialchars($deliveryBoy['Name']) . ' (' . $deliveryBoy['PhoneNumber'] . ')'; ?></p>
            <form method="POST" action="checkout.php">
                <input type="hidden" name="place_order" value="1">
                <button type="submit">Confirm Order</button>
            </form>
        </div>
    </div>

    <script>
    function nextStep(step) {
        document.getElementById("step-" + (step - 1)).style.display = "none";
        document.getElementById("step-" + step).style.display = "block";
    }
    </script>
</body>
</html>

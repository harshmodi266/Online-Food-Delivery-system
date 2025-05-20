<?php
session_start();
include 'connect.php';

// Redirect to login if not logged in
if (!isset($_SESSION['CustomerID'])) {
    header("Location: login.php");
    exit();
}

$customerID = $_SESSION['CustomerID'];

// Handle add to cart request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    $menuID = $_POST['menu_id'];

    // Check if item already exists in the cart
    $checkQuery = "SELECT * FROM cart WHERE CustomerID = ? AND MenuID = ?";
    $stmt = mysqli_prepare($conn, $checkQuery);
    mysqli_stmt_bind_param($stmt, "ii", $customerID, $menuID);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        // Update quantity if item exists
        $updateQuery = "UPDATE cart SET Quantity = Quantity + 1 WHERE CustomerID = ? AND MenuID = ?";
        $stmt = mysqli_prepare($conn, $updateQuery);
        mysqli_stmt_bind_param($stmt, "ii", $customerID, $menuID);
        mysqli_stmt_execute($stmt);
    } else {
        // Insert new cart item
        $insertQuery = "INSERT INTO cart (CustomerID, MenuID, Quantity, DateAdded) VALUES (?, ?, 1, NOW())";
        $stmt = mysqli_prepare($conn, $insertQuery);
        mysqli_stmt_bind_param($stmt, "ii", $customerID, $menuID);
        mysqli_stmt_execute($stmt);
    }
    header("Location: cart.php");
    exit();
}

// Handle remove item
if (isset($_GET['remove'])) {
    $cartID = $_GET['remove'];
    $deleteQuery = "DELETE FROM cart WHERE CustomerID = ? AND CartID = ?";
    $stmt = mysqli_prepare($conn, $deleteQuery);
    mysqli_stmt_bind_param($stmt, "ii", $customerID, $cartID);
    mysqli_stmt_execute($stmt);
    header("Location: cart.php");
    exit();
}

// Fetch cart items from database with images
$query = "SELECT c.CartID, m.DishName, m.Price, c.Quantity, m.Image FROM cart c 
          JOIN menu m ON c.MenuID = m.MenuID WHERE c.CustomerID = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $customerID);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$cartItems = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Calculate totals
$subtotal = 0;
$totalQty = 0;
foreach ($cartItems as $item) {
    $subtotal += $item['Price'] * $item['Quantity'];
    $totalQty += $item['Quantity'];
}

$cgstRate = 2.5; // 2.5%
$sgstRate = 2.5; // 2.5%
$deliveryCharge = 10.00; // Fixed delivery charge
$cgst = ($subtotal * $cgstRate) / 100;
$sgst = ($subtotal * $sgstRate) / 100;
$totalTax = $cgst + $sgst;
$grandTotal = $subtotal + $totalTax + $deliveryCharge;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Lazeez Restaurant</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Dialog Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            width: 300px;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .modal-content p {
            margin: 10px 0;
            font-size: 16px;
            color: #333;
        }
        .modal-content .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .modal-content .close:hover,
        .modal-content .close:focus {
            color: #000;
            text-decoration: none;
            cursor: pointer;
        }
        .details-table {
            width: 100%;
            margin-top: 10px;
            border-collapse: collapse;
        }
        .details-table td {
            padding: 8px;
            border: 1px solid #ddd;
        }
        .details-table td:first-child {
            font-weight: bold;
            background-color: #f5f5f5;
        }
        /* Custom style for View Details link */
        .view-details-btn {
            color: black;
            text-decoration: none;
            margin-left: 10px;
            font-size: 16px;
        }
        .view-details-btn:hover {
            text-decoration: underline;
        }
    </style>
    <script>
        // Function to update the total on the client side
        function updateTotal() {
            let total = 0;
            let totalQty = 0;

            // Calculate subtotal and total quantity
            document.querySelectorAll(".cart-item").forEach(item => {
                let price = parseFloat(item.querySelector(".price").textContent.replace("₹", ""));
                let quantity = parseInt(item.querySelector(".quantity").value);
                let subtotal = price * quantity;
                item.querySelector(".subtotal").textContent = "₹" + subtotal.toFixed(2);
                total += subtotal;
                totalQty += quantity;
            });

            // Recalculate with taxes and delivery
            let cgst = (total * 2.5) / 100;
            let sgst = (total * 2.5) / 100;
            let delivery = 10.00;
            let totalTax = cgst + sgst;
            let grandTotal = total + totalTax + delivery;

            // Update the Grand Total text
            const totalElement = document.querySelector(".total");
            totalElement.childNodes[0].textContent = "Grand Total: ₹" + grandTotal.toFixed(2) + " ";

            // Ensure the "View Details" button remains visible
            let viewDetailsBtn = totalElement.querySelector(".view-details-btn");
            if (!viewDetailsBtn) {
                viewDetailsBtn = document.createElement("a");
                viewDetailsBtn.href = "javascript:void(0)";
                viewDetailsBtn.className = "view-details-btn";
                viewDetailsBtn.textContent = "View Details";
                viewDetailsBtn.onclick = showDetails;
                totalElement.appendChild(viewDetailsBtn);
            }

            // Update the modal table values
            document.getElementById("modal-total-qty").textContent = totalQty;
            document.getElementById("modal-sub-total").textContent = "₹" + total.toFixed(2);
            document.getElementById("modal-cgst").textContent = "₹" + cgst.toFixed(2);
            document.getElementById("modal-sgst").textContent = "₹" + sgst.toFixed(2);
            document.getElementById("modal-delivery").textContent = "₹" + delivery.toFixed(2);
            document.getElementById("modal-total-tax").textContent = "₹" + totalTax.toFixed(2);
            document.getElementById("modal-grand-total").textContent = "₹" + grandTotal.toFixed(2);
        }

        // Function to update quantity via AJAX
        function updateQuantity(cartID, quantity) {
            fetch('update_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `cart_id=${cartID}&quantity=${quantity}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log("Quantity updated successfully");
                    updateTotal(); // Update the total after successful update
                    // Update cart count
                    const cartCount = document.getElementById('cart-count');
                    if (cartCount && data.cart_count !== undefined) {
                        cartCount.textContent = data.cart_count;
                    }
                } else {
                    console.error("Failed to update quantity:", data.message);
                }
            })
            .catch(error => {
                console.error("Error updating quantity:", error);
            });
        }

        // Function to confirm removal
        function confirmRemove(event, url) {
            event.preventDefault();
            if (confirm("Are you sure you want to remove this item?")) {
                window.location.href = url;
            }
        }

        // Function to show the modal
        function showDetails() {
            updateTotal(); // Ensure the modal has the latest values before showing
            const modal = document.getElementById("detailsModal");
            modal.style.display = "flex";
        }

        // Function to close the modal
        function closeModal() {
            const modal = document.getElementById("detailsModal");
            modal.style.display = "none";
        }

        // Call updateCartCount after page load
        document.addEventListener('DOMContentLoaded', () => {
            updateCartCount();
            updateTotal();
        });

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById("detailsModal");
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</head>
<body>
    <?php include 'header.php'; ?>
    <div class="cart-container">
        <h2>Your Cart</h2>
        
        <?php if (!empty($cartItems)): ?>
        <table>
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Dish</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Total</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php $itemTotal = 0; ?>
                <?php foreach ($cartItems as $item): ?>
                    <?php $subtotal = $item['Price'] * $item['Quantity']; ?>
                    <?php $itemTotal += $subtotal; ?>
                    <tr class="cart-item">
                        <td><img src="<?php echo $item['Image']; ?>" class="cart-item-img" alt="<?php echo $item['DishName']; ?>"></td>
                        <td><?php echo htmlspecialchars($item['DishName']); ?></td>
                        <td class="price">₹<?php echo number_format($item['Price'], 2); ?></td>
                        <td>
                            <input type="number" class="quantity" name="quantities[<?php echo $item['CartID']; ?>]" value="<?php echo $item['Quantity']; ?>" min="1" onchange="updateQuantity(<?php echo $item['CartID']; ?>, this.value)">
                        </td>
                        <td class="subtotal">₹<?php echo number_format($subtotal, 2); ?></td>
                        <td>
                            <a href="cart.php?remove=<?php echo $item['CartID']; ?>" class="remove-btn" onclick="confirmRemove(event, this.href)">Remove</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p class="total">Grand Total: ₹<?php echo number_format($grandTotal, 2); ?>
            <a href="javascript:void(0)" class="view-details-btn" onclick="showDetails()">View Details</a>
        </p>
        <a href="menu.php" class="continue-btn">Back to menu...</a>
        <a href="user_order_history.php" class="view-order-btn">View Order History</a>
        <a href="checkout.php" class="checkout-btn">Proceed to Checkout</a>
        
        <!-- Modal for Details -->
        <div id="detailsModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal()">×</span>
                <h3>Order Details</h3>
                <table class="details-table">
                    <tr>
                        <td>Total Qty:</td>
                        <td id="modal-total-qty"><?php echo $totalQty; ?></td>
                    </tr>
                    <tr>
                        <td>Sub Total:</td>
                        <td id="modal-sub-total">₹<?php echo number_format($itemTotal, 2); ?></td>
                    </tr>
                    <tr>
                        <td>CGST @2.5%:</td>
                        <td id="modal-cgst">₹<?php echo number_format($cgst, 2); ?></td>
                    </tr>
                    <tr>
                        <td>SGST @2.5%:</td>
                        <td id="modal-sgst">₹<?php echo number_format($sgst, 2); ?></td>
                    </tr>
                    <tr>
                        <td>Delivery Charges:</td>
                        <td id="modal-delivery">₹<?php echo number_format($deliveryCharge, 2); ?></td>
                    </tr>
                    <tr>
                        <td>Total Tax:</td>
                        <td id="modal-total-tax">₹<?php echo number_format($totalTax, 2); ?></td>
                    </tr>
                    <tr>
                        <td>Grand Total:</td>
                        <td id="modal-grand-total">₹<?php echo number_format($grandTotal, 2); ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <?php else: ?>
            <p>Your cart is empty.</p>
            <a href="menu.php" class="continue-btn">Back to menu...</a><br/><br/> 
            <a href="user_order_history.php" class="view-order-btn">View Order History</a>
        <?php endif; ?>
    </div>
</body>
</html>
<?php
mysqli_close($conn);
?>
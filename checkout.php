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

// Helper function to format prices with the rupee symbol
function formatPrice($price) {
    return "₹" . number_format($price, 2, '.', '');
}

// Fetch Cart Items and Calculate Total
$cart_items = [];
$subtotal = 0;
$totalQty = 0;
$sql = "SELECT c.CartID, c.MenuID, c.Quantity, m.DishName, m.Price 
        FROM cart c 
        JOIN menu m ON c.MenuID = m.MenuID 
        WHERE c.CustomerID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $CustomerID);
$stmt->execute();
$result = $stmt->get_result();
while ($item = $result->fetch_assoc()) {
    $item["Total"] = $item["Price"] * $item["Quantity"];
    $subtotal += $item["Total"];
    $totalQty += $item["Quantity"];
    $cart_items[] = $item;
}
$stmt->close();

$cgstRate = 2.5; // 2.5%
$sgstRate = 2.5; // 2.5%
$deliveryCharge = 10.00; // Fixed delivery charge
$cgst = ($subtotal * $cgstRate) / 100;
$sgst = ($subtotal * $sgstRate) / 100;
$totalTax = $cgst + $sgst;
$grand_total = $subtotal + $totalTax + $deliveryCharge;

// Fetch Addresses
$addresses_sql = "SELECT AddressID, AddressLine1, AddressLine2, AddressType FROM address WHERE CustomerID = ?";
$addresses_stmt = $conn->prepare($addresses_sql);
$addresses_stmt->bind_param("i", $CustomerID);
$addresses_stmt->execute();
$addresses = $addresses_stmt->get_result();

// Retain payment method after form submission or page refresh
$selected_payment_method = isset($_POST["payment_method"]) ? $_POST["payment_method"] : "COD";

// Handle Checkout
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    error_log("POST request received");
    error_log("place_order isset: " . (isset($_POST["place_order"]) ? "Yes" : "No"));
    error_log("POST data: " . print_r($_POST, true));

    if (isset($_POST["place_order"])) {
        error_log("Form submitted with place_order");

        $AddressID = isset($_POST["AddressID"]) ? $_POST["AddressID"] : null;
        $payment_method = $_POST["payment_method"];
        $payment_confirmed = isset($_POST["payment_confirmed"]) ? $_POST["payment_confirmed"] : false;
        $transaction_id = isset($_POST["transaction_id"]) ? trim($_POST["transaction_id"]) : '';

        error_log("AddressID: " . $AddressID);
        error_log("Payment Method: " . $payment_method);
        error_log("Payment Confirmed: " . $payment_confirmed);
        error_log("Transaction ID: " . $transaction_id);

        if (empty($AddressID) || !is_numeric($AddressID) || (int)$AddressID <= 0) {
            $error = "Please select a valid delivery address.";
            error_log("Validation failed: Invalid AddressID");
        } elseif (empty($cart_items)) {
            $error = "Your cart is empty. Please add items to your cart before checking out.";
            error_log("Validation failed: Cart is empty");
        } elseif ($payment_method == "Online" && !$payment_confirmed) {
            $error = "Please confirm the online payment to proceed.";
            error_log("Validation failed: Online payment not confirmed");
        } elseif ($payment_method == "Online" && empty($transaction_id)) {
            $error = "Please enter a transaction ID to confirm the payment.";
            error_log("Validation failed: Transaction ID missing");
        } else {
            $AddressID = (int)$AddressID;
            $status = "Pending";

            $conn->begin_transaction();
            try {
                $order_sql = "INSERT INTO orders (CustomerID, AddressID, OrderDate, TotalAmount, Status, PaymentID) 
                              VALUES (?, ?, NOW(), ?, ?, NULL)";
                $order_stmt = $conn->prepare($order_sql);
                if (!$order_stmt) {
                    throw new Exception("Failed to prepare order insert statement: " . $conn->error);
                }
                $order_stmt->bind_param("iids", $CustomerID, $AddressID, $grand_total, $status);
                if (!$order_stmt->execute()) {
                    throw new Exception("Failed to insert order: " . $order_stmt->error);
                }
                $order_id = $conn->insert_id;
                $order_stmt->close();

                error_log("Order inserted with OrderID: " . $order_id);

                $payment_id = null;
                if ($payment_method == "Online") {
                    $payment_sql = "INSERT INTO payment (OrderID, Amount, PaymentMethod, PaymentStatus, PaymentDate, TransactionID) 
                                    VALUES (?, ?, 'Online Payment', 'Completed', NOW(), ?)";
                    $payment_stmt = $conn->prepare($payment_sql);
                    if (!$payment_stmt) {
                        throw new Exception("Failed to prepare payment insert statement: " . $conn->error);
                    }
                    $payment_stmt->bind_param("ids", $order_id, $grand_total, $transaction_id);
                    if (!$payment_stmt->execute()) {
                        throw new Exception("Failed to insert payment: " . $payment_stmt->error);
                    }
                    $payment_id = $conn->insert_id;
                    $payment_stmt->close();

                    $update_order_sql = "UPDATE orders SET PaymentID = ? WHERE OrderID = ?";
                    $update_order_stmt = $conn->prepare($update_order_sql);
                    if (!$update_order_stmt) {
                        throw new Exception("Failed to prepare order update statement: " . $conn->error);
                    }
                    $update_order_stmt->bind_param("ii", $payment_id, $order_id);
                    if (!$update_order_stmt->execute()) {
                        throw new Exception("Failed to update order with PaymentID: " . $update_order_stmt->error);
                    }
                    $update_order_stmt->close();

                    error_log("Payment inserted with PaymentID: " . $payment_id);
                }

                foreach ($cart_items as $item) {
                    $menu_id = $item["MenuID"];
                    $quantity = $item["Quantity"];
                    $item_price = $item["Price"];
                    $order_item_sql = "INSERT INTO orderitems (OrderID, MenuID, Quantity, ItemPrice) VALUES (?, ?, ?, ?)";
                    $order_item_stmt = $conn->prepare($order_item_sql);
                    if (!$order_item_stmt) {
                        throw new Exception("Failed to prepare order items insert statement: " . $conn->error);
                    }
                    $order_item_stmt->bind_param("iiid", $order_id, $menu_id, $quantity, $item_price);
                    if (!$order_item_stmt->execute()) {
                        throw new Exception("Failed to insert order item: " . $order_item_stmt->error);
                    }
                    $order_item_stmt->close();
                }

                error_log("Order items inserted for OrderID: " . $order_id);

                $cart_clear_sql = "DELETE FROM cart WHERE CustomerID = ?";
                $cart_clear_stmt = $conn->prepare($cart_clear_sql);
                if (!$cart_clear_stmt) {
                    throw new Exception("Failed to prepare cart clear statement: " . $conn->error);
                }
                $cart_clear_stmt->bind_param("i", $CustomerID);
                if (!$cart_clear_stmt->execute()) {
                    throw new Exception("Failed to clear cart: " . $cart_clear_stmt->error);
                }
                $cart_clear_stmt->close();

                error_log("Cart cleared for CustomerID: " . $CustomerID);

                $conn->commit();

                error_log("Order placed successfully, redirecting to user_order_history.php");

                header("Location: user_order_history.php?success=Order+placed+successfully");
                exit;
            } catch (Exception $e) {
                $conn->rollback();
                $error = "An error occurred while placing your order: " . $e->getMessage();
                error_log("Checkout Error: " . $e->getMessage());
            }
        }
    } else {
        error_log("place_order not set in POST");
    }
}

$page_title = "Checkout";
include 'header.php';
?>

<div class="checkout-container">
    <h2 class="checkout-heading">Checkout</h2>
    
    <?php if (isset($error)) { ?>
        <p class="checkout-error"><?php echo htmlspecialchars($error); ?></p>
    <?php } ?>

    <!-- Cart Summary -->
    <div class="checkout-card">
        <h3 class="checkout-subheading">Cart Summary</h3>
        <?php if (!empty($cart_items)) { ?>
            <table class="checkout-table">
                <thead>
                    <tr>
                        <th>Dish Name</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart_items as $item) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item["DishName"]); ?></td>
                        <td><?php echo formatPrice($item["Price"]); ?></td>
                        <td><?php echo htmlspecialchars($item["Quantity"]); ?></td>
                        <td><?php echo formatPrice($item["Total"]); ?></td>
                    </tr>
                    <?php } ?>
                    <tr>
                        <td colspan="3"><strong>Grand Total:</strong></td>
                        <td><strong><?php echo formatPrice($grand_total); ?>
                            <a href="javascript:void(0)" class="view-details-btn" onclick="showDetails()">View Details</a>
                        </strong></td>
                    </tr>
                </tbody>
            </table>
        <?php } else { ?>
            <p class="checkout-empty-cart">Your cart is empty.</p>
        <?php } ?>
    </div>

    <!-- Checkout Form -->
    <?php if (!empty($cart_items)) { ?>
        <div class="checkout-card checkout-form-container">
            <h3 class="checkout-subheading">Delivery and Payment Details</h3>
            <form method="POST" class="checkout-form" id="checkoutForm">
                <div class="checkout-form-group">
                    <label class="checkout-label" for="AddressID">Select Delivery Address:</label>
                    <select class="checkout-select" name="AddressID" id="AddressID" required>
                        <option value="">Select an address</option>
                        <?php while ($address = $addresses->fetch_assoc()) { ?>
                            <option value="<?php echo htmlspecialchars($address['AddressID']); ?>" <?php if (isset($_POST["AddressID"]) && $_POST["AddressID"] == $address['AddressID']) echo "selected"; ?>>
                                <?php echo htmlspecialchars($address["AddressType"] . ": " . $address["AddressLine1"] . ($address["AddressLine2"] ? ', ' . $address["AddressLine2"] : '')); ?>
                            </option>
                        <?php } ?>
                    </select>
                    <button type="button" class="checkout-btn checkout-btn-small" onclick="showAddAddressForm()">Add New Address</button>
                </div>

                <div class="checkout-form-group">
                    <label class="checkout-label" for="paymentMethod">Payment Method:</label>
                    <select class="checkout-select" name="payment_method" id="paymentMethod" required onchange="handlePaymentMethodChange()">
                        <option value="COD" <?php if ($selected_payment_method == "COD") echo "selected"; ?>>Cash on Delivery</option>
                        <option value="Online" <?php if ($selected_payment_method == "Online") echo "selected"; ?>>Online Payment</option>
                    </select>
                </div>

                <input type="hidden" name="payment_confirmed" id="paymentConfirmed" value="0">

                <button type="submit" name="place_order" id="placeOrderButton" class="checkout-btn">Place Order</button>
            </form>
        </div>
    <?php } ?>

    <!-- Add Address Form (Moved Outside the Main Form) -->
    <div id="addAddressForm" class="checkout-popup">
        <h4 class="checkout-subheading">Add New Address</h4>
        <div class="checkout-form-group">
            <label class="checkout-label" for="addressType">Address Type:</label>
            <select class="checkout-select" id="addressType" name="addressType" required>
                <option value="Home">Home</option>
                <option value="Work">Work</option>
                <option value="Other">Other</option>
            </select>
        </div>
        <div class="checkout-form-group">
            <label class="checkout-label" for="addressLine1">Address Line 1:</label>
            <input type="text" class="checkout-input" id="addressLine1" name="addressLine1" placeholder="e.g., 123 Main Street" required>
        </div>
        <div class="checkout-form-group">
            <label class="checkout-label" for="addressLine2">Address Line 2 (Optional):</label>
            <input type="text" class="checkout-input" id="addressLine2" name="addressLine2" placeholder="e.g., Vadodara">
        </div>
        <div class="checkout-form-group">
            <button type="button" class="checkout-btn checkout-btn-small" onclick="addNewAddress()">Save Address</button>
            <button type="button" class="checkout-btn checkout-btn-small checkout-btn-cancel" onclick="hideAddAddressForm()">Cancel</button>
        </div>
        <p id="addressError" class="checkout-error" style="display: none;"></p>
    </div>

    <!-- Pop-up for Online Payment -->
    <div id="paymentPopup" class="checkout-popup">
        <h3 class="checkout-subheading">Complete Your Payment</h3>
        <div class="checkout-popup-content">
            <p class="checkout-popup-text">Scan the QR code or use the UPI details below to pay:</p>
            <img src="images/qr_code.jpg" alt="QR Code for Payment" class="checkout-qr-code" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
            <p class="checkout-qr-error">QR Code not available. Please use the UPI ID below.</p>
            <p class="checkout-popup-text"><strong>UPI ID:</strong> 9054996724@ybi</p>
            <p class="checkout-popup-text"><strong>Amount:</strong> <?php echo formatPrice($grand_total); ?></p>
            <p class="checkout-popup-text"><strong>Note:</strong> Order Payment for Lazeez Restaurant</p>
            <form method="POST" id="confirmPaymentForm" class="checkout-form">
                <input type="hidden" name="AddressID" id="popupAddressID">
                <input type="hidden" name="payment_method" value="Online">
                <input type="hidden" name="payment_confirmed" value="1">
                <input type="hidden" name="place_order" value="1">
                <div class="checkout-form-group">
                    <label class="checkout-label" for="transaction_id">Enter Transaction ID:</label>
                    <input type="text" name="transaction_id" id="transaction_id" class="checkout-input" placeholder="e.g., TXN123456" required>
                    <p id="transactionError" class="checkout-transaction-error"></p>
                </div>
                <div class="checkout-popup-buttons">
                    <button type="submit" class="checkout-btn checkout-btn-confirm">Confirm Payment</button>
                    <button type="button" onclick="closePopup()" class="checkout-btn checkout-btn-cancel">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Overlay for Pop-up -->
    <div id="overlay" class="checkout-overlay"></div>

    <!-- Modal for View Details -->
    <div id="detailsModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">×</span>
            <h3>Order Details</h3>
            <table class="details-table">
                <tr>
                    <td>Total Qty:</td>
                    <td><?php echo $totalQty; ?></td>
                </tr>
                <tr>
                    <td>Sub Total:</td>
                    <td><?php echo formatPrice($subtotal); ?></td>
                </tr>
                <tr>
                    <td>CGST @2.5%:</td>
                    <td><?php echo formatPrice($cgst); ?></td>
                </tr>
                <tr>
                    <td>SGST @2.5%:</td>
                    <td><?php echo formatPrice($sgst); ?></td>
                </tr>
                <tr>
                    <td>Delivery Charges:</td>
                    <td><?php echo formatPrice($deliveryCharge); ?></td>
                </tr>
                <tr>
                    <td>Total Tax:</td>
                    <td><?php echo formatPrice($totalTax); ?></td>
                </tr>
                <tr>
                    <td>Grand Total:</td>
                    <td><?php echo formatPrice($grand_total); ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>

<style>
.checkout-container {
    max-width: 800px;
    margin: 50px auto;
    padding: 30px;
    background: #fff;
    border-radius: 15px;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(211, 84, 0, 0.1);
}

.checkout-heading {
    color: #d35400;
    font-size: 28px;
    margin-bottom: 25px;
    text-align: center;
    font-weight: 600;
    letter-spacing: 1px;
    text-transform: uppercase;
}

.checkout-subheading {
    color: #d35400;
    font-size: 20px;
    margin-bottom: 15px;
    font-weight: 500;
}

.checkout-error {
    color: red;
    font-weight: bold;
    margin-bottom: 20px;
    background-color: #ffe6e6;
    padding: 12px;
    border-radius: 8px;
    text-align: center;
}

.checkout-card {
    background: #fff;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    border-left: 5px solid #d35400;
}

.checkout-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

.checkout-table th, .checkout-table td {
    border: 1px solid #ddd;
    padding: 10px;
    text-align: left;
}

.checkout-table th {
    background-color: #f4f4f4;
    color: #333;
    font-weight: bold;
}

.checkout-table td {
    color: #555;
}

.checkout-empty-cart {
    text-align: center;
    color: #777;
    font-size: 16px;
}

.checkout-form-container {
    margin-top: 20px;
}

.checkout-form {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.checkout-form-group {
    margin-bottom: 15px;
}

.checkout-label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #444;
    font-size: 15px;
}

.checkout-select, .checkout-input {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 15px;
    background-color: #fafafa;
    color: #333;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
}

.checkout-select:focus, .checkout-input:focus {
    border-color: #d35400;
    outline: none;
    box-shadow: 0 0 8px rgba(211, 84, 0, 0.2);
}

.checkout-btn {
    display: inline-flex;
    justify-content: center;
    align-items: center;
    padding: 10px 18px;
    background: linear-gradient(90deg, #d35400, #e67e22);
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 15px;
    font-weight: bold;
    cursor: pointer;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    text-decoration: none;
}

.checkout-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(211, 84, 0, 0.3);
}

.checkout-btn-small {
    padding: 6px 12px;
    font-size: 13px;
    background: #fff;
    border: 2px solid #d35400;
    color: #d35400;
}

.checkout-btn-small:hover {
    background: #d35400;
    color: #fff;
}

.checkout-btn-confirm {
    background: green;
}

.checkout-btn-cancel {
    background: red;
}

.checkout-popup {
    display: none;
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    border-radius: 15px;
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.3);
    z-index: 1000;
    max-width: 350px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
    padding: 20px;
}

.checkout-popup-content {
    padding: 20px;
}

.checkout-popup-text {
    font-size: 14px;
    margin: 8px 0;
    color: #333;
}

.checkout-qr-code {
    width: 150px;
    height: 150px;
    display: block;
    margin: 0 auto 10px;
}

.checkout-qr-error {
    display: none;
    color: red;
    text-align: center;
    font-size: 13px;
    margin-bottom: 10px;
}

.checkout-transaction-error {
    color: red;
    font-size: 13px;
    margin-top: 5px;
    display: none;
}

.checkout-popup-buttons {
    display: flex;
    gap: 10px;
    justify-content: center;
    margin-top: 15px;
}

.checkout-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 999;
    cursor: pointer;
}

/* Modal Styles */
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

@media (max-width: 768px) {
    .checkout-container {
        margin: 20px;
        padding: 20px;
    }

    .checkout-heading {
        font-size: 24px;
    }

    .checkout-subheading {
        font-size: 18px;
    }

    .checkout-table th, .checkout-table td {
        padding: 8px;
        font-size: 14px;
    }

    .checkout-select, .checkout-input, .checkout-btn {
        font-size: 14px;
        padding: 8px;
    }

    .checkout-btn-small {
        font-size: 12px;
        padding: 5px 10px;
    }

    .checkout-popup {
        max-width: 300px;
        max-height: 70vh;
    }

    .checkout-popup-content {
        padding: 15px;
    }

    .checkout-qr-code {
        width: 120px;
        height: 120px;
    }

    .checkout-popup-text {
        font-size: 13px;
    }

    .checkout-label {
        font-size: 14px;
    }
}
</style>

<script>
let activePopup = null;

function showAddAddressForm() {
    const addAddressForm = document.getElementById('addAddressForm');
    const overlay = document.getElementById('overlay');
    addAddressForm.style.display = 'block';
    overlay.style.display = 'block';
    activePopup = 'addAddress';
}

function hideAddAddressForm() {
    const addAddressForm = document.getElementById('addAddressForm');
    const overlay = document.getElementById('overlay');
    addAddressForm.style.display = 'none';
    if (activePopup === 'addAddress') {
        overlay.style.display = 'none';
        activePopup = null;
    }
    document.getElementById('addressError').style.display = 'none';
    document.getElementById('addressType').value = 'Home';
    document.getElementById('addressLine1').value = '';
    document.getElementById('addressLine2').value = '';
}

function addNewAddress() {
    const addressType = document.getElementById('addressType').value;
    const addressLine1 = document.getElementById('addressLine1').value.trim();
    const addressLine2 = document.getElementById('addressLine2').value.trim();
    const addressError = document.getElementById('addressError');

    if (!addressLine1) {
        addressError.textContent = 'Address Line 1 is required.';
        addressError.style.display = 'block';
        return;
    }

    fetch('add_address.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `addressType=${encodeURIComponent(addressType)}&addressLine1=${encodeURIComponent(addressLine1)}&addressLine2=${encodeURIComponent(addressLine2)}`
    })
    .then(response => {
        if (!response.ok) throw new Error(`Network response was not ok: ${response.statusText}`);
        return response.json();
    })
    .then(data => {
        if (data.success) {
            const addressSelect = document.getElementById('AddressID');
            const newOption = document.createElement('option');
            newOption.value = data.addressID;
            let displayText = `${addressType}: ${addressLine1}`;
            if (addressLine2) displayText += `, ${addressLine2}`;
            newOption.textContent = displayText;
            addressSelect.appendChild(newOption);
            addressSelect.value = data.addressID;
            hideAddAddressForm();
        } else {
            addressError.textContent = data.message || 'Failed to add address.';
            addressError.style.display = 'block';
        }
    })
    .catch(error => {
        console.error("Error adding address:", error.message);
        addressError.textContent = 'Error adding address. Please try again.';
        addressError.style.display = 'block';
    });
}

function handlePaymentMethodChange() {
    const paymentMethod = document.getElementById('paymentMethod').value;
    const popup = document.getElementById('paymentPopup');
    const overlay = document.getElementById('overlay');

    if (paymentMethod === 'Online') {
        popup.style.display = 'block';
        overlay.style.display = 'block';
        const addressID = document.getElementById('AddressID').value;
        document.getElementById('popupAddressID').value = addressID;
        activePopup = 'payment';
    } else {
        popup.style.display = 'none';
        if (activePopup === 'payment') {
            overlay.style.display = 'none';
            activePopup = null;
        }
    }
}

function closePopup() {
    const paymentPopup = document.getElementById('paymentPopup');
    const overlay = document.getElementById('overlay');
    paymentPopup.style.display = 'none';
    if (activePopup === 'payment') {
        overlay.style.display = 'none';
        activePopup = null;
    }
    document.getElementById('paymentMethod').value = 'COD';
    document.getElementById('transactionError').style.display = 'none';
    document.getElementById('transaction_id').value = '';
}

function showDetails() {
    const modal = document.getElementById("detailsModal");
    modal.style.display = "flex";
}

function closeModal() {
    const modal = document.getElementById("detailsModal");
    modal.style.display = "none";
}

window.onclick = function(event) {
    const modal = document.getElementById("detailsModal");
    if (event.target == modal) {
        modal.style.display = "none";
    }
};

document.getElementById('confirmPaymentForm').addEventListener('submit', function(event) {
    const transactionId = document.getElementById('transaction_id').value.trim();
    const transactionError = document.getElementById('transactionError');
    const transactionPattern = /^[A-Za-z0-9]{12,22}$/;

    if (!transactionPattern.test(transactionId)) {
        event.preventDefault();
        transactionError.textContent = 'Please Enter A Valid UPI Transaction ID';
        transactionError.style.display = 'block';
    } else {
        transactionError.style.display = 'none';
    }
});

document.getElementById('overlay').addEventListener('click', function() {
    if (activePopup === 'addAddress') hideAddAddressForm();
    else if (activePopup === 'payment') closePopup();
});

document.getElementById('checkoutForm').addEventListener('submit', function(event) {
    const paymentMethod = document.getElementById('paymentMethod').value;
    const paymentConfirmed = document.getElementById('paymentConfirmed').value;
    if (paymentMethod === 'Online' && paymentConfirmed !== '1') {
        event.preventDefault();
        alert('Please confirm your online payment by entering the transaction ID.');
    }
});

document.addEventListener('DOMContentLoaded', handlePaymentMethodChange);
</script>

<?php 
$addresses_stmt->close();
$conn->close();
include 'footer.php';
?>
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

$customer_id = $_SESSION["CustomerID"];
$page_title = "Manage Addresses";
include 'header.php';

// Handle Add/Edit Address
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["save_address"])) {
    $address_id = isset($_POST["AddressID"]) ? (int)$_POST["AddressID"] : 0;
    $address_line1 = $_POST["address_line1"];
    $address_line2 = $_POST["address_line2"];
    $address_type = $_POST["address_type"]; // New field: AddressType

    if ($address_id > 0) {
        // Update existing address
        $sql = "UPDATE address SET AddressLine1 = ?, AddressLine2 = ?, AddressType = ? WHERE AddressID = ? AND CustomerID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssii", $address_line1, $address_line2, $address_type, $address_id, $customer_id);
    } else {
        // Add new address
        $sql = "INSERT INTO address (CustomerID, AddressLine1, AddressLine2, AddressType) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isss", $customer_id, $address_line1, $address_line2, $address_type);
    }
    $stmt->execute();
    header("Location: user_addresses.php");
    exit;
}

// Handle Delete Address
if (isset($_GET["delete_id"])) {
    $delete_id = (int)$_GET["delete_id"];
    $sql = "DELETE FROM address WHERE AddressID = ? AND CustomerID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $delete_id, $customer_id);
    $stmt->execute();
    header("Location: user_addresses.php");
    exit;
}

// Fetch Addresses
$addresses = $conn->query("SELECT AddressID, AddressLine1, AddressLine2, AddressType 
                           FROM address 
                           WHERE CustomerID = $customer_id");
?>

<div>
    <h2>Manage Addresses</h2>

    <!-- Add New Address Form -->
    <div class="card form-container">
        <h3>Add New Address</h3>
        <form method="POST" class="form">
            <input type="hidden" name="address_id" value="0">
            <label>Address Type:</label>
            <select name="address_type" required>
                <option value="Home">Home</option>
                <option value="Work">Work</option>
                <option value="Other">Other</option>
            </select>
            <label>Address Line 1:</label>
            <input type="text" name="address_line1" required>
            <label>Address Line 2:</label>
            <input type="text" name="address_line2">
            <button type="submit" name="save_address" class="btn">Add Address</button>
        </form>
    </div>

    <!-- List of Addresses -->
    <?php if ($addresses->num_rows > 0) { ?>
        <h3>Your Addresses</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Address Type</th>
                    <th>Address</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($address = $addresses->fetch_assoc()) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($address["AddressType"]); ?></td>
                    <td>
                        <?php echo htmlspecialchars($address["AddressLine1"]); ?>
                        <?php if ($address["AddressLine2"]) { ?>
                            , <?php echo htmlspecialchars($address["AddressLine2"]); ?>
                        <?php } ?>
                    </td>
                    <td>
                        <!-- Edit Address Form -->
                        <button onclick="editAddress(<?php echo $address['AddressID']; ?>, '<?php echo htmlspecialchars($address['AddressType']); ?>', '<?php echo htmlspecialchars($address['AddressLine1']); ?>', '<?php echo htmlspecialchars($address['AddressLine2']); ?>')" class="btn">Edit</button>
                        <a href="user_addresses.php?delete_id=<?php echo $address['AddressID']; ?>" class="btn">Delete</a>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    <?php } else { ?>
        <p>No addresses found. Add a new address above.</p>
    <?php } ?>
</div>

<!-- JavaScript for Edit Address -->
<script>
function editAddress(addressId, addressType, addressLine1, addressLine2) {
    document.querySelector('input[name="address_id"]').value = addressId;
    document.querySelector('select[name="address_type"]').value = addressType; // Set AddressType
    document.querySelector('input[name="address_line1"]').value = addressLine1;
    document.querySelector('input[name="address_line2"]').value = addressLine2;
    document.querySelector('form h3').textContent = "Edit Address";
    document.querySelector('form button').textContent = "Update Address";
    window.scrollTo(0, 0);
}
</script>

<?php include 'footer.php'; ?>
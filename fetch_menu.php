<?php
include 'connect.php';
include 'header.php'; 

$categoryID = isset($_GET['category']) ? $_GET['category'] : 'all';

$query = ($categoryID == 'all') ? "SELECT * FROM menu" : "SELECT * FROM menu WHERE CategoryID = '$categoryID'";
$result = mysqli_query($conn, $query);

$output = ''; // Store HTML in a variable to avoid accidental output
while ($row = mysqli_fetch_assoc($result)) {
    $output .= '<div class="menu-item" data-category="'. $row['CategoryID'] .'">
                    <img src="'. $row['Image'] .'" alt="'. $row['DishName'] .'">
                    <h3>'. $row['DishName'] .'</h3>
                    <p>'. $row['Description'] .'</p>
                    <span>₹'. $row['Price'] .'</span>
                    <form method="POST" action="cart.php">
                        <input type="hidden" name="menu_id" value="'. $row['MenuID'] .'">
                        <input type="hidden" name="dish_name" value="'. $row['DishName'] .'">
                        <input type="hidden" name="price" value="'. $row['Price'] .'">
                        <button onclick="addToCart('. $row['MenuID'] .')" class="add-to-cart">Add to Cart</button>
                        </form>
                </div>';
}
echo trim($output); // Ensure no extra space is echoed
?>

<?php
include 'connect.php';// Database connection
//include 'header.php';// Header
session_start(); // Start session for login check

if (isset($_GET['q']) && !empty($_GET['q'])) {
    $search = "%" . $_GET['q'] . "%";

    echo "<h2>Search Results for: " . htmlspecialchars($_GET['q']) . "</h2>";

    // Search food items from Menu table
    $stmt = $conn->prepare("SELECT * FROM Menu WHERE DishName LIKE ?");
    $stmt->bind_param("s", $search);
    $stmt->execute();
    $menu_result = $stmt->get_result();

    // Search food categories from FoodCategory table
    $stmt = $conn->prepare("SELECT CategoryID, CategoryName, CategoryImage FROM FoodCategory WHERE CategoryName LIKE ?");
    $stmt->bind_param("s", $search);
    $stmt->execute();
    $category_result = $stmt->get_result();

    // Check if any results were found
    if ($menu_result->num_rows > 0 || $category_result->num_rows > 0) {
        // Display food items
        while ($row = $menu_result->fetch_assoc()) {
            echo "<div class='food-card'>
                    <img src='" . $row['FoodImage'] . "' alt='" . $row['FoodName'] . "'>
                    <h3>" . $row['FoodName'] . "</h3>
                    <p>Price: " . $row['Price'] . " USD</p>";

            // Check if user is logged in before allowing "Add to Cart"
            if (isset($_SESSION['user_id'])) {
                echo "<a href='add_to_cart.php?id=" . $row['MenuID'] . "'><button>Add to Cart</button></a>";
            } else {
                echo "<a href='login.php'><button>Login to Add</button></a>";
            }

            echo "</div>";
        }

        // Display categories
        while ($row = $category_result->fetch_assoc()) {
            echo "<div class='food-card'>
                    <img src='" . $row['CategoryImage'] . "' alt='" . $row['CategoryName'] . "'>
                    <h3>" . $row['CategoryName'] . "</h3>
                  </div>";
        }
    } else {
        echo "<p>No results found.</p>";
    }
} else {
    echo "<p>Please enter a search term.</p>";
}
?>

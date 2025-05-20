<?php 
include 'header.php';
include 'connect.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect to login if not logged in
if (!isset($_SESSION['CustomerID'])) {
    header("Location: login.php");
    exit();
}

$customerID = $_SESSION['CustomerID'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu - Lazeez Restaurant</title>
    <link href="css/style.css" rel="stylesheet">
    <style>
        /* Style for the notification */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: #4CAF50;
            color: white;
            padding: 15px 20px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            display: none;
            z-index: 1000;
            opacity: 0;
            transform: translateX(100%);
            transition: opacity 0.3s ease-in-out, transform 0.3s ease-in-out;
        }

        .notification.show {
            display: block;
            opacity: 1;
            transform: translateX(0);
        }
    </style>
    <script>
        // Function to add item to cart via AJAX
        function addToCart(menuID) {
            fetch('add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `menu_id=${menuID}`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    showNotification(data.message || "Item added to cart!");
                    // Update cart count in the header
                    const cartCount = document.getElementById('cart-count');
                    if (cartCount && data.cart_count !== undefined) {
                        cartCount.textContent = data.cart_count;
                    } else {
                        console.error("Cart count element not found or cart_count missing in response!");
                    }
                } else {
                    showNotification("Failed to add item to cart: " + data.message, true);
                    if (data.message === 'User not logged in') {
                        setTimeout(() => {
                            window.location.href = 'login.php';
                        }, 2000);
                    }
                }
            })
            .catch(error => {
                console.error("Error adding to cart:", error);
                showNotification("Error adding item to cart", true);
            });
        }

        // Function to show the notification
        function showNotification(message, isError = false) {
            const notification = document.getElementById('notification');
            if (!notification) {
                console.error("Notification element not found!");
                return;
            }
            notification.textContent = message;
            notification.style.backgroundColor = isError ? '#f44336' : '#4CAF50';
            notification.classList.add('show');

            // Hide the notification after 3 seconds
            setTimeout(() => {
                notification.classList.remove('show');
            }, 3000);
        }

        // Initialize cart count on page load
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
                    } else {
                        console.error("Cart count element not found on page load!");
                    }
                })
                .catch(error => {
                    console.error("Error fetching cart count on page load:", error);
                });
        });
    </script>
</head>
<body>

<!-- Category Filters -->
<section class="category-slider">
    <button id="prevSlide">‹</button>
    <div class="category-container">
        <div class="category-item" data-category="all">
            <img src="food_slide_img/All.png" alt="All">
            <p>All</p>
        </div>
        <?php
            $query = "SELECT * FROM foodcategory";
            $result = mysqli_query($conn, $query);
            while ($row = mysqli_fetch_assoc($result)) {
                echo '<div class="category-item" data-category="'. $row['CategoryID'] .'">
                        <img src="food_slide_img/'. $row['CategoryImage'] .'" loading="lazy" alt="'. $row['CategoryName'] .'">
                        <p>'. $row['CategoryName'] .'</p>
                      </div>';
            }
        ?>
    </div>
    <button id="nextSlide">›</button>
</section>

<!-- Search Results Heading -->
<?php if (isset($_GET['q']) && !empty(trim($_GET['q']))): ?>
    <div class="search-results-container">
        <h2 class="search-heading">Search Results for: <?php echo htmlspecialchars($_GET['q']); ?></h2>
    </div>
<?php endif; ?>

<!-- Menu Items (Search or Full Menu) -->
<section class="menu" id="menu-items">
    <?php
    if (isset($_GET['q']) && !empty(trim($_GET['q']))) {
        $searchQuery = trim($_GET['q']);
        // Split search terms into an array for multi-word searches
        $searchTerms = preg_split('/\s+/', $searchQuery);
        $conditions = [];
        $params = [];
        $types = '';

        // Build dynamic WHERE clause for each search term
        foreach ($searchTerms as $term) {
            $term = '%' . $term . '%'; // Wildcards for partial matching
            $conditions[] = "(DishName LIKE ? OR Description LIKE ?)";
            $params[] = $term; // For DishName
            $params[] = $term; // For Description
            $types .= 'ss';    // Two string parameters per term
        }

        // Combine conditions with OR to match any term
        $whereClause = implode(' OR ', $conditions);
        $stmt = $conn->prepare("SELECT * FROM menu WHERE $whereClause");
        
        // Bind parameters dynamically
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $menu_result = $stmt->get_result();

        if ($menu_result->num_rows > 0) {
            while ($row = $menu_result->fetch_assoc()) {
                echo '<div class="menu-item">
                        <img src="'. $row['Image'] .'" alt="'. $row['DishName'] .'">
                        <h3>'. $row['DishName'] .'</h3>
                        <p>'. $row['Description'] .'</p>
                        <span class="price">₹'. $row['Price'] .'</span>';

                // Button to add item to cart via AJAX
                echo '<button onclick="addToCart('. $row['MenuID'] .')" class="add-to-cart">Add to Cart</button>';
                echo '</div>';
            }
        } else {
            echo '<p class="no-results">No results found for "' . htmlspecialchars($searchQuery) . '". Try a different keyword like "Paneer", "Soup", or "Pizza".</p>';
        }

        $stmt->close();
    } else {
        // Display full menu when no search query is provided
        $query = "SELECT * FROM menu";
        $result = mysqli_query($conn, $query);

        while ($row = mysqli_fetch_assoc($result)) {
            echo '<div class="menu-item">
                    <img src="'. $row['Image'] .'" loading="lazy" alt="'. $row['DishName'] .'">
                    <h3>'. $row['DishName'] .'</h3>
                    <p>'. $row['Description'] .'</p>
                    <span class="price">₹'. $row['Price'] .'</span>';

            // Button to add item to cart via AJAX
            echo '<button onclick="addToCart('. $row['MenuID'] .')" class="add-to-cart">Add to Cart</button>';
            echo '</div>';
        }
        mysqli_free_result($result);
    }
    ?>
</section>

<!-- Notification element -->
<div id="notification" class="notification"></div>

<!-- Footer -->
<footer>
    <?php 
    include 'footer.php';
    ?>
</footer>

</body>
</html>
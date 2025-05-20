<?php include 'header.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lazeez Restaurant - Home</title>
    <link rel="stylesheet" href="css/style.css">
    
</head>

<body>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>Welcome to <span>Lazeez Restaurant</span></h1>
            <p>Experience the best flavors crafted with love.</p>
            <a href="menu.php" class="btn">Explore Menu</a>
        </div>
    </section>

    <!-- Popular Dishes -->
    <section class="popular-dishes">
        <h2>🔥 Popular Dishes</h2>
        <div class="dish-container">
            <?php
            include 'connect.php';
            $query = "SELECT * FROM menu ORDER BY RAND() LIMIT 4"; // Fetch 4 random dishes
            $result = mysqli_query($conn, $query);
            while ($row = mysqli_fetch_assoc($result)) {
                echo '<div class="dish-card">
                    <img loading="lazy" src="' . $row['Image'] . '" alt="' . $row['DishName'] . '">
                    <h3>' . $row['DishName'] . '</h3>
                    <p>₹' . number_format($row['Price'], 2) . '</p>
                    <a href="menu.php" class="btn">Order Now</a>
                  </div>';
            }
            ?>
        </div>
    </section>

    <!-- About Us -->
    <section id="about" class="about">
        <h2>🍽 About Us</h2>
        <p>Lazeez Restaurant brings you a variety of mouth-watering dishes made with fresh ingredients. Enjoy the best
            culinary experience with us.</p>
    </section>

    <!-- Customer Reviews -->
    <section id="reviews" class="reviews">
        <h2>💬 What Our Customers Say</h2>
        <div class="review-container">
            <?php
            try {
                // Improved query with JOINs to get customer name and dish name
                $reviewQuery = "
                SELECT 
                    r.ReviewText,
                    r.Rating,
                    r.ReviewDate,
                    c.Name AS CustomerName,
                    m.DishName
                FROM reviews r
                INNER JOIN customer c ON r.CustomerID = c.CustomerID
                INNER JOIN menu m ON r.MenuID = m.MenuID
                ORDER BY RAND()
                LIMIT 3
            ";

                $reviewResult = mysqli_query($conn, $reviewQuery);

                // Check if query executed successfully
                if (!$reviewResult) {
                    throw new Exception("Query failed: " . mysqli_error($conn));
                }

                // Check if there are any reviews
                if (mysqli_num_rows($reviewResult) > 0) {
                    while ($review = mysqli_fetch_assoc($reviewResult)) {
                        // Create star rating display
                        $stars = str_repeat('★', $review['Rating']) . str_repeat('☆', 5 - $review['Rating']);

                        echo '<div class="review-card">
                            <div class="rating">' . $stars . '</div>
                            <p>"' . htmlspecialchars($review['ReviewText']) . '"</p>
                            <h4>- ' . htmlspecialchars($review['CustomerName']) . '</h4>
                            <small>Reviewed: ' . htmlspecialchars($review['DishName']) . '</small>
                            <small>' . date('M d, Y', strtotime($review['ReviewDate'])) . '</small>
                          </div>';
                    }
                } else {
                    echo '<p>No reviews yet. Be the first to share your experience!</p>';
                }

                mysqli_free_result($reviewResult);
            } catch (Exception $e) {
                echo '<p>Error loading reviews: ' . htmlspecialchars($e->getMessage()) . '</p>';
            }
            ?>
        </div>
    </section>
    <!-- Contact Us -->
    <section id="contact" class="contact">
        <h2>📍 Contact Us</h2>
        <p>📍 34 Vishal Chambers, Behind National Plaza, Alkapuri, Vadodara, Gujarat, India</p>
        <p>📞 +9190549 96724</p>
        <p>📧 contact@lazeezrestaurant.com</p>
    </section>

    <?php include 'footer.php'; ?>
</body>

</html>
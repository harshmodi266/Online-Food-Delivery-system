<?php
// review_page.php

// Database connection
include 'connect.php';
include 'header.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['CustomerID'])) {
    header("Location: login.php");
    exit();
}

$CustomerID = $_SESSION['CustomerID'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_review'])) {
    $menu_id = $_POST['menu_id'];
    $rating = $_POST['rating'];
    $review_text = $_POST['review_text'];
    $review_date = date('Y-m-d H:i:s');

    $stmt = $conn->prepare("INSERT INTO reviews (CustomerID, MenuID, Rating, ReviewText, ReviewDate) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iiiss", $CustomerID, $menu_id, $rating, $review_text, $review_date);
    
    if ($stmt->execute()) {
        $success_message = "Review submitted successfully!";
    } else {
        $error_message = "Error submitting review: " . $conn->error;
    }
}

// Fetch menu items
$query = "SELECT DISTINCT m.MenuID, m.DishName 
          FROM orderitems oi 
          JOIN orders o ON oi.OrderID = o.OrderID 
          JOIN menu m ON oi.MenuID = m.MenuID 
          LEFT JOIN reviews r ON m.MenuID = r.MenuID AND r.CustomerID = ?
          WHERE o.CustomerID = ? AND r.ReviewID IS NULL";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $CustomerID, $CustomerID);
$stmt->execute();
$menu_items = $stmt->get_result();

// Fetch user's previous reviews
if (isset($_GET['show_my_reviews'])) {
    $my_reviews_query = "SELECT r.*, m.DishName FROM reviews r JOIN menu m ON r.MenuID = m.MenuID WHERE r.CustomerID = ? ORDER BY r.ReviewDate DESC";
    $stmt = $conn->prepare($my_reviews_query);
    $stmt->bind_param("i", $CustomerID);
    $stmt->execute();
    $my_reviews = $stmt->get_result();
}

// Fetch all reviews
if (isset($_GET['show_all_reviews'])) {
    $all_reviews_query = "SELECT r.*, m.DishName, c.Name FROM reviews r 
                          JOIN menu m ON r.MenuID = m.MenuID 
                          JOIN customer c ON r.CustomerID = c.CustomerID 
                          ORDER BY r.ReviewDate DESC";
    $stmt = $conn->prepare($all_reviews_query);
    $stmt->execute();
    $all_reviews = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Lazeez Restaurants - Submit Review</title>
    <style>
        /* General Reset and Base Styles */
        .review-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(211, 84, 0, 0.1);
        }

        .review-heading {
            color: #d35400;
            font-size: 28px;
            margin-bottom: 25px;
            text-align: center;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .review-success-message {
            background-color: #e6ffe6;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 500;
            color: green;
        }

        .review-error-message {
            background-color: #ffe6e6;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 500;
            color: red;
        }

        .review-form-group {
            margin-bottom: 25px;
        }

        .review-label {
            display: block;
            margin-bottom: 10px;
            font-weight: 500;
            color: #444;
            font-size: 16px;
        }

        .review-select {
            width: 100%;
            padding: 12px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            background-color: #fafafa;
            color: #333;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .review-select:focus {
            border-color: #d35400;
            outline: none;
            box-shadow: 0 0 8px rgba(211, 84, 0, 0.2);
        }

        .review-textarea {
            width: 100%;
            padding: 12px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            background-color: #fafafa;
            color: #333;
            resize: vertical;
            min-height: 120px;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .review-textarea:focus {
            border-color: #d35400;
            outline: none;
            box-shadow: 0 0 8px rgba(211, 84, 0, 0.2);
        }

        .review-textarea::placeholder {
            color: #999;
        }

        .review-star-rating {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
            margin: 0 auto;
        }

        .review-stars {
            display: flex;
            justify-content: center;
            gap: 15px;
            padding: 10px 0;
        }

        .review-star-rating input[type="radio"] {
            display: none;
        }

        .review-star-rating .review-label {
            font-size: 35px;
            color: #ddd;
            cursor: pointer;
            transition: transform 0.2s ease, color 0.3s ease, text-shadow 0.3s ease;
            text-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
        }

        .review-star-rating .review-label:before {
            content: '★';
        }

        @keyframes review-starGlow {
            0% { text-shadow: 0 0 5px rgba(255, 215, 0, 0.5); }
            50% { text-shadow: 0 0 15px rgba(255, 215, 0, 0.8), 0 0 25px rgba(255, 215, 0, 0.5); }
            100% { text-shadow: 0 0 5px rgba(255, 215, 0, 0.5); }
        }

        .review-rating-text {
            font-size: 16px;
            font-weight: 600;
            color: #d35400;
            text-transform: uppercase;
            margin-top: 10px;
            opacity: 0;
            transition: all 0.3s ease;
            transform-origin: center;
        }

        @keyframes review-textPop {
            0% { transform: scale(1); opacity: 0; }
            50% { transform: scale(1.2); opacity: 1; }
            100% { transform: scale(1); opacity: 1; }
        }

        .review-rating-text.active {
            opacity: 1;
            animation: review-textPop 0.5s ease forwards;
        }

        .review-rating-text span {
            display: none;
        }

        .review-rating-text span.active {
            display: inline-block;
        }

        .review-submit-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(90deg, #d35400, #e67e22);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .review-submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(211, 84, 0, 0.3);
        }

        .review-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
            flex-wrap: wrap; /* Ensure buttons wrap on smaller screens */
        }

        .review-action-btn {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            min-width: 120px; /* Ensure buttons have a minimum width */
            height: 40px; /* Fixed height for consistency */
            padding: 10px 20px;
            background: #fff;
            border: 2px solid #d35400;
            border-radius: 8px;
            color: #d35400;
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            box-sizing: border-box; /* Ensure padding doesn't affect width */
        }

        .review-action-btn:hover {
            background: #d35400;
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(211, 84, 0, 0.3);
        }

        .review-reviews-container {
            margin-top: 30px;
        }

        .review-card {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border-left: 5px solid #d35400;
            transition: transform 0.3s ease;
        }

        .review-card:hover {
            transform: translateY(-5px);
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            color: #666;
        }

        .review-dish {
            font-weight: bold;
            color: #d35400;
            font-size: 18px;
        }

        .review-rating {
            color: #ffd700;
            font-size: 20px;
        }

        .review-text-content {
            color: #333;
            line-height: 1.5;
        }

        .review-name {
            font-style: italic;
            color: #777;
            margin-top: 10px;
        }

        .review-date {
            font-size: 14px;
            color: #999;
            margin-top: 10px;
        }

        @media (max-width: 768px) {
            .review-container {
                margin: 20px;
                padding: 20px;
            }

            .review-heading {
                font-size: 24px;
            }

            .review-select,
            .review-textarea,
            .review-submit-btn {
                font-size: 14px;
                padding: 10px;
            }

            .review-star-rating .review-label {
                font-size: 28px;
            }

            .review-rating-text {
                font-size: 14px;
            }

            .review-action-btn {
                min-width: 100px; /* Slightly smaller width on mobile */
                height: 35px; /* Slightly smaller height on mobile */
                font-size: 14px;
                padding: 8px 15px;
            }
        }
    </style>
</head>
<body>
    <div class="review-container">
        <h2 class="review-heading">Submit a Review - Lazeez Restaurants</h2>
        
        <?php if (isset($success_message)): ?>
            <p class="review-success-message"><?php echo $success_message; ?></p>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <p class="review-error-message"><?php echo $error_message; ?></p>
        <?php endif; ?>

        <?php if ($menu_items->num_rows > 0): ?>
            <form method="POST">
                <div class="review-form-group">
                    <label class="review-label" for="menu_id">Select Menu Item:</label>
                    <select class="review-select" name="menu_id" id="menu_id" required>
                        <option value="">-- Select an item to review --</option>
                        <?php while ($item = $menu_items->fetch_assoc()): ?>
                            <option value="<?php echo $item['MenuID']; ?>">
                                <?php echo htmlspecialchars($item['DishName']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="review-form-group">
                    <label class="review-label">Rating (1-5):</label>
                    <div class="review-star-rating">
                        <div class="review-stars">
                            <input type="radio" id="star1" name="rating" value="1" required>
                            <label class="review-label" for="star1"></label>
                            <input type="radio" id="star2" name="rating" value="2">
                            <label class="review-label" for="star2"></label>
                            <input type="radio" id="star3" name="rating" value="3">
                            <label class="review-label" for="star3"></label>
                            <input type="radio" id="star4" name="rating" value="4">
                            <label class="review-label" for="star4"></label>
                            <input type="radio" id="star5" name="rating" value="5">
                            <label class="review-label" for="star5"></label>
                        </div>
                        <div class="review-rating-text" id="rating-text">
                            <span data-rating="1">Poor</span>
                            <span data-rating="2">Fair</span>
                            <span data-rating="3">Good</span>
                            <span data-rating="4">Very Good</span>
                            <span data-rating="5">Excellent</span>
                        </div>
                    </div>
                </div>

                <div class="review-form-group">
                    <label class="review-label" for="review_text">Your Review:</label>
                    <textarea class="review-textarea" name="review_text" id="review_text" rows="5" 
                            placeholder="Tell us about your experience..." required></textarea>
                </div>

                <input type="submit" name="submit_review" value="Submit Review" class="review-submit-btn">
            </form>

            <div class="review-buttons">
                <button class="review-action-btn" onclick="window.location.href='?show_my_reviews=1'">View My Reviews</button>
                <button class="review-action-btn" onclick="window.location.href='?show_all_reviews=1'">All Reviews</button>
            </div>

            <?php if (isset($my_reviews)): ?>
                <div class="review-reviews-container">
                    <h3 class="review-heading">Your Previous Reviews</h3>
                    <?php while ($review = $my_reviews->fetch_assoc()): ?>
                        <div class="review-card">
                            <div class="review-header">
                                <span class="review-dish"><?php echo htmlspecialchars($review['DishName']); ?></span>
                                <span class="review-rating"><?php echo str_repeat('★', $review['Rating']); ?></span>
                            </div>
                            <div class="review-text-content"><?php echo htmlspecialchars($review['ReviewText']); ?></div>
                            <div class="review-date"><?php echo $review['ReviewDate']; ?></div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($all_reviews)): ?>
                <div class="review-reviews-container">
                    <h3 class="review-heading">All Reviews</h3>
                    <?php while ($review = $all_reviews->fetch_assoc()): ?>
                        <div class="review-card">
                            <div class="review-header">
                                <span class="review-dish"><?php echo htmlspecialchars($review['DishName']); ?></span>
                                <span class="review-rating"><?php echo str_repeat('★', $review['Rating']); ?></span>
                            </div>
                            <div class="review-text-content"><?php echo htmlspecialchars($review['ReviewText']); ?></div>
                            <div class="review-name">By: <?php echo htmlspecialchars($review['Name']); ?></div>
                            <div class="review-date"><?php echo $review['ReviewDate']; ?></div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <p>You have no recent items to review or you've already reviewed all your ordered items.</p>
            <a href="menu.php">Order something new!</a>
        <?php endif; ?>
    </div>

    <script>
        const stars = document.querySelectorAll('.review-star-rating input');
        const labels = document.querySelectorAll('.review-star-rating .review-label');
        const ratingText = document.getElementById('rating-text');

        // Function to reset all stars
        function resetStars() {
            labels.forEach(label => {
                label.style.color = '#ddd';
                label.style.textShadow = '0 0 5px rgba(0, 0, 0, 0.1)';
                label.style.transform = 'scale(1)';
                label.style.animation = 'none';
            });
        }

        // Function to highlight stars up to a certain index
        function highlightStars(upToIndex) {
            for (let i = 0; i <= upToIndex; i++) {
                labels[i].style.color = '#ffd700';
                labels[i].style.textShadow = '0 0 10px rgba(255, 215, 0, 0.8), 0 0 20px rgba(255, 215, 0, 0.5)';
                labels[i].style.transform = 'scale(1.1)';
                labels[i].style.animation = 'review-starGlow 1.5s infinite';
            }
        }

        // Function to show rating text
        function showRatingText(rating) {
            const textSpans = ratingText.querySelectorAll('span');
            textSpans.forEach(span => {
                span.classList.remove('active');
            });
            const activeSpan = ratingText.querySelector(`span[data-rating="${rating}"]`);
            if (activeSpan) {
                activeSpan.classList.add('active');
                ratingText.classList.add('active');
            }
        }

        // Handle click event
        stars.forEach(star => {
            star.addEventListener('change', function() {
                const rating = this.value;
                const selectedIndex = parseInt(this.id.replace('star', '')) - 1;

                resetStars();
                highlightStars(selectedIndex);
                showRatingText(rating);
            });
        });

        // Handle hover events
        labels.forEach((label, index) => {
            label.addEventListener('mouseover', function() {
                resetStars();
                highlightStars(index);
            });

            label.addEventListener('mouseout', function() {
                resetStars();
                const checkedStar = document.querySelector('.review-star-rating input:checked');
                if (checkedStar) {
                    const selectedIndex = parseInt(checkedStar.id.replace('star', '')) - 1;
                    highlightStars(selectedIndex);
                }
            });
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>
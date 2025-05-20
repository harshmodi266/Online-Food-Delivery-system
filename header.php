<?php
// Lazeez Restaurant - Premium Header Design

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'connect.php';
// Get the current page filename to determine the active link
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lazeez Restaurant</title>
    <link href="css/style.css" rel="stylesheet" type="text/css">
</head>
<body>
    <header>
        <div class="nav-container">
            <a href="index.php">
                <h1 class="nav-logo">Lazeez Restaurant</h1>
            </a>
            <nav>
                <ul class="nav-links">
                    <li><a href="menu.php" class="<?php echo $current_page == 'menu.php' ? 'active' : ''; ?>">Menu</a></li>
                    <li><a href="aboutus.php" class="<?php echo $current_page == 'aboutus.php' ? 'active' : ''; ?>">About</a></li>
                    <li><a href="contactus.php" class="<?php echo $current_page == 'contactus.php' ? 'active' : ''; ?>">Contact</a></li>
                    <li><a href="review.php" class="<?php echo $current_page == 'review.php' ? 'active' : ''; ?>">Reviews</a></li>
                </ul> 
            </nav>

            <!-- Search Bar -->
            <div class="group">
                <svg viewBox="0 0 24 24" aria-hidden="true" class="icon">
                    <g>
                        <path
                            d="M21.53 20.47l-3.66-3.66C19.195 15.24 20 13.214 20 11c0-4.97-4.03-9-9-9s-9 4.03-9 9 4.03 9 9 9c2.215 0 4.24-.804 5.808-2.13l3.66 3.66c.147.146.34.22.53.22s.385-.073.53-.22c.295-.293.295-.767.002-1.06zM3.5 11c0-4.135 3.365-7.5 7.5-7.5s7.5 3.365 7.5 7.5-3.365 7.5-7.5 7.5-7.5-3.365-7.5-7.5z">
                        </path>
                    </g>
                </svg>
                <form method="GET" action="menu.php">
                    <input class="input" type="search" name="q" placeholder="Search food..." />
                </form>
            </div>

            <!-- Authentication Links -->
            <div class="auth-links">
                <?php if (isset($_SESSION['CustomerID'])): ?>
                    <?php
                    $customerID = $_SESSION['CustomerID'];
                    $query = "SELECT Name FROM customer WHERE CustomerID = '$customerID'";
                    $result = mysqli_query($conn, $query);
                    $user = mysqli_fetch_assoc($result);
                    ?>
                    <a href="user_profile.php"><span class="welcome-msg">Hi, <?php echo htmlspecialchars($user['Name']); ?>!</span></a>
                    <a href="logout.php" class="logout-btn">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="<?php echo $current_page == 'login.php' ? 'active' : ''; ?>">Login</a>
                    <a href="registration.php" class="<?php echo $current_page == 'registration.php' ? 'active' : ''; ?>">Sign Up</a>
                <?php endif; ?>
            </div>

            <!-- Cart Icon -->
            <div class="header-cart">
                <a href="cart.php" class="<?php echo $current_page == 'cart.php' ? 'active' : ''; ?>">
                    <img src="cart.png" alt="Cart" class="cart-icon">
                </a>
            </div>
        </div>
    </header>

    <script src="Js/script.js" type="text/javascript"></script>
</body>
</html>
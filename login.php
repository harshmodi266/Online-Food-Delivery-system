<?php
include 'connect.php'; // Database connection file
include 'header.php'; // Header

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['Email']);
    $password = trim($_POST['Password']);
    
    $stmt = $conn->prepare("SELECT CustomerID, Password FROM customer WHERE Email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($customerID, $hashedPassword);
        $stmt->fetch();
        
        if (password_verify($password, $hashedPassword)) {
            $_SESSION['CustomerID'] = $customerID;
            header("Location: index.php"); // Redirect to customer dashboard
            exit();
        } else {
            $loginError = "Invalid email or password.";
        }
    } else {
        $loginError = "Invalid email or password.";
    }
    
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Login - Lazeez Restaurant</title>
    <link rel="stylesheet" href="css/style.css">
    
</head>
<body>
    <div class="container">
        <h2>Customer Login</h2>
        <?php if (!empty($loginError)) echo "<p class='error'>$loginError</p>"; ?>
        <form action="" method="POST">
            <label for="Email">Email:</label>
            <input type="email" id="Email" name="Email" required>
            
            <label for="Password">Password:</label>
            <input type="password" id="Password" name="Password" required>
            
            <button type="submit">Login</button>
            <p>Don't have an account? <a href="registration.php">Create account</a></p>
        </form>
    </div>
</body>
</html>
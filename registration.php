<?php
include 'connect.php'; // Database connection file
include 'header.php'; // Include the header

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = mysqli_real_escape_string($conn, $_POST['Name']);
    $email = mysqli_real_escape_string($conn, $_POST['Email']);
    $phone = mysqli_real_escape_string($conn, $_POST['PhoneNumber']);
    $password = $_POST['Password'];

    // Check if email already exists
    $checkEmailQuery = "SELECT Email FROM customer WHERE Email = '$email'";
    $result = mysqli_query($conn, $checkEmailQuery);

    if (mysqli_num_rows($result) > 0) {
        $message = "<p class='error'>This email is already registered. Please use a different email or login.</p>";
    } else {
        // Hash password for security
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        // Insert query with prepared statement for better security
        $query = "INSERT INTO customer (Name, Email, PhoneNumber, Password) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $phone, $hashedPassword);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "<p class='success'>Registration successful!</p>";
        } else {
            $message = "<p class='error'>Error: " . mysqli_error($conn) . "</p>";
        }
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Registration - Lazeez Restaurant</title>
    <link rel="stylesheet" href="css/style.css">
    <script>
        function validateForm(event) {
            let name = document.getElementById("Name").value;
            let email = document.getElementById("Email").value;
            let phone = document.getElementById("PhoneNumber").value;
            let password = document.getElementById("Password").value;
            let nameError = document.getElementById("nameError");
            let emailError = document.getElementById("emailError");
            let phoneError = document.getElementById("phoneError");
            let passwordError = document.getElementById("passwordError");
            
            nameError.textContent = "";
            emailError.textContent = "";
            phoneError.textContent = "";
            passwordError.textContent = "";

            let namePattern = /^[A-Za-z\s]+$/; // Only letters and spaces
            let emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
            let phonePattern = /^\d{10}$/;
            let passwordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
            
            let valid = true;

            // Name validation
            if (name.trim() === "") {
                nameError.textContent = "Full Name is required.";
                valid = false;
            } else if (!namePattern.test(name)) {
                nameError.textContent = "Invalid name. Use only letters and spaces.";
                valid = false;
            } else if (name.length > 20) {
                nameError.textContent = "The name must be less than 20 characters.";
                valid = false;
            }

            if (!emailPattern.test(email)) {
                emailError.textContent = "Please enter a valid email address.";
                valid = false;
            }
            if (!phonePattern.test(phone)) {
                phoneError.textContent = "Phone number must be exactly 10 digits.";
                valid = false;
            }
            if (!passwordPattern.test(password)) {
                passwordError.textContent = "Password must be at least 8 characters, contain one uppercase, one lowercase, one digit, and one special character.";
                valid = false;
            }

            if (!valid) {
                event.preventDefault();
            }
        }
    </script>
</head>
<body>
    <div class="container">
        <h2>Customer Registration</h2>
        
        <?php if (isset($message)) echo $message; ?>

        <form action="" method="POST" onsubmit="validateForm(event)">
            <label for="Name">Full Name:</label>
            <input type="text" id="Name" name="Name" required>
            <div id="nameError" class="error"></div>

            <label for="Email">Email:</label>
            <input type="email" id="Email" name="Email" required>
            <div id="emailError" class="error"></div>

            <label for="PhoneNumber">Phone Number:</label>
            <input type="text" id="PhoneNumber" name="PhoneNumber" required>
            <div id="phoneError" class="error"></div>

            <label for="Password">Password:</label>
            <input type="password" id="Password" name="Password" required>
            <div id="passwordError" class="error"></div>

            <button type="submit">Register</button>
            <p>Already have an account?<a href="login.php"> Login</a></p>
        </form>
    </div>
</body>
</html>
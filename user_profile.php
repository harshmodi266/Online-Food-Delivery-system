<?php
session_start();

// Check if customer is logged in
if (!isset($_SESSION['CustomerID'])) {
    header("Location: login.php"); // Redirect to login page if not logged in
    exit();
}

$customerId = $_SESSION['CustomerID'];
$successMessage = '';
$errorMessage = '';

// Database connection
$conn = new mysqli("127.0.0.1", "root", "", "lazeez");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch customer details
$stmt = $conn->prepare("SELECT Name, Email, PhoneNumber, Password, RegistrationDate FROM customer WHERE CustomerID = ?");
$stmt->bind_param("i", $customerId);
$stmt->execute();
$result = $stmt->get_result();
$customer = $result->fetch_assoc();
$stmt->close();

if (!$customer) {
    $errorMessage = "Customer not found.";
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phoneNumber = trim($_POST['phoneNumber']);
    $password = trim($_POST['password']);

    // Server-side validation
    $errors = [];
    if (strlen($name) > 20) {
        $errors[] = "Name must be 20 characters or less.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 30) {
        $errors[] = "Please enter a valid email.";
    }
    if (!preg_match("/^[0-9]{10}$/", $phoneNumber)) {
        $errors[] = "Phone number must be 10 digits.";
    }
    if (empty($password)) {
        $errors[] = "Password cannot be empty.";
    }

    // Check if email is already used by another customer
    $stmt = $conn->prepare("SELECT CustomerID FROM customer WHERE Email = ? AND CustomerID != ?");
    $stmt->bind_param("si", $email, $customerId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $errors[] = "Email is already in use.";
    }
    $stmt->close();

    if (empty($errors)) {
        // Hash the password if it has changed
        $hashedPassword = $customer['Password'];
        if ($password !== $customer['Password']) {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        }

        // Update customer details
        $stmt = $conn->prepare("UPDATE customer SET Name = ?, Email = ?, PhoneNumber = ?, Password = ? WHERE CustomerID = ?");
        $stmt->bind_param("ssssi", $name, $email, $phoneNumber, $hashedPassword, $customerId);
        
        if ($stmt->execute()) {
            $successMessage = "Profile updated successfully!";
            // Update local customer data for form pre-fill
            $customer['Name'] = $name;
            $customer['Email'] = $email;
            $customer['PhoneNumber'] = $phoneNumber;
            $customer['Password'] = $password; // Store plain password temporarily for form pre-fill
        } else {
            $errorMessage = "Error updating profile: " . $conn->error;
        }
        $stmt->close();
    } else {
        $errorMessage = implode(" ", $errors);
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Profile - Lazeez Restaurant</title>
    <link href="css/style.css" rel="stylesheet" type="text/css">
    <style>
        /* Unique class names for profile page */
        .profile-container {
            display: flex;
            justify-content: space-between;
            max-width: 800px;
            margin: 40px auto;
            padding: 30px;
            background: #fff;
            border: 2px solid #0A1F44;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            position: relative;
            min-height: 400px; /* Ensure sufficient height for content */
        }

        .profile-heading {
            text-align: center;
            font-family: 'Georgia', serif;
            color:#D4AF37;
            font-size: 2rem;
            margin-bottom: 20px;
            position: absolute;
            top: -10px;
            left: 50%;
            transform: translateX(-50%);
            margin-top: 10px;
            padding: 0 10px;
        }

        /* .profile-divider {
            border-left: 2px solid #D4AF37;
            height: 100%;
            position: absolute;
            left: 50%;
            top: 0;
            transform: translateX(-50%);
        } */

        .profile-column {
            flex: 1;
            padding: 20px;
            position: relative;
        }

        .profile-left-column {
            margin-right: 20px;
        }

        .profile-form-group {
            margin-bottom: 20px; /* Increased spacing between form fields */
        }

        .profile-form-group label {
            font-size: 16px;
            color: #2E2E2E;
            margin-bottom: 5px;
            display: block;
        }

        .profile-input {
            width: 100%;
            padding: 8px;
            height: 36px;
            border: 1px solid #D4AF37;
            border-radius: 5px;
            font-size: 14px;
            background: #FAF3E0;
            color: #2E2E2E;
            margin-top: 5px;
        }

        .profile-input:disabled {
            background-color: #e0e0e0;
            cursor: not-allowed;
        }

        .profile-error {
            color: red;
            font-size: 12px;
            margin-top: 3px;
            display: none;
        }

        .profile-success-message {
            color: #D4AF37;
            font-size: 16px;
            margin-top: 15px;
            text-align: center;
            display: <?php echo $successMessage ? 'block' : 'none'; ?>;
        }

        .profile-error-message {
            color: red;
            font-size: 16px;
            margin-top: 15px;
            text-align: center;
            display: <?php echo $errorMessage ? 'block' : 'none'; ?>;
        }

        .profile-button-group {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 30px; /* Increased spacing above buttons */
        }

        .profile-btn {
            padding: 10px;
            height: 40px;
            background: #D4AF37;
            color: #0A1F44;
            font-size: 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s, transform 0.2s;
        }

        .profile-btn:hover {
            background: #B9972E;
            transform: scale(1.05);
        }

        .profile-cancel-btn {
            background: #e0e0e0;
            color: #2E2E2E;
        }

        .profile-cancel-btn:hover {
            background: #d0d0d0;
            transform: scale(1.05);
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .profile-container {
                flex-direction: column;
                max-width: 350px;
                height: auto;
                margin: 20px auto;
                padding: 20px;
            }

            .profile-left-column {
                margin-right: 0;
            }

            .profile-divider {
                display: none; /* Hide divider on mobile */
            }

            .profile-heading {
                position: static;
                transform: none;
                margin-bottom: 10px;
            }

            .profile-button-group {
                margin-top: 20px;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="profile-container">
        <div class="profile-heading">Profile Information</div>
        <div class="profile-divider"></div>
        <div class="profile-column profile-left-column">
            <form id="profileForm" method="POST" action="">
                <div class="profile-form-group">
                    <label for="name">Name</label>
                    <input type="text" id="name" name="name" required maxlength="20" 
                           value="<?php echo htmlspecialchars($customer['Name']); ?>" 
                           class="profile-input" placeholder="Enter your name">
                    <p id="nameError" class="profile-error">Name must be 20 characters or less</p>
                </div>
                <div class="profile-form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required maxlength="30" 
                           value="<?php echo htmlspecialchars($customer['Email']); ?>" 
                           class="profile-input" placeholder="Enter your email">
                    <p id="emailError" class="profile-error">Please enter a valid email</p>
                </div>
                <div class="profile-form-group">
                    <label for="phoneNumber">Phone Number</label>
                    <input type="tel" id="phoneNumber" name="phoneNumber" required pattern="[0-9]{10}" 
                           value="<?php echo htmlspecialchars($customer['PhoneNumber']); ?>" 
                           class="profile-input" placeholder="Enter your phone number">
                    <p id="phoneError" class="profile-error">Phone number must be 10 digits</p>
                </div>
                <div class="profile-form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required 
                           value="<?php echo htmlspecialchars($customer['Password']); ?>" 
                           class="profile-input" placeholder="Enter new password">
                    <p id="passwordError" class="profile-error">Password cannot be empty</p>
                </div>
            </form>
        </div>
        <div class="profile-column">
            <div class="profile-form-group">
                <label for="registrationDate">Registration Date</label>
                <input type="text" id="registrationDate" name="registrationDate" 
                       value="<?php echo date('Y-m-d H:i:s', strtotime($customer['RegistrationDate'])); ?>" 
                       class="profile-input" disabled>
            </div>
            <div class="profile-button-group">
                <button type="submit" id="updateBtn" name="update" class="profile-btn">Update Profile</button>
                <button type="button" id="cancelBtn" class="profile-cancel-btn">Cancel</button>
                <a href="index.php" class="profile-btn">Back to Home</a>
            </div>
        </div>
    </form>
    <p id="successMessage" class="profile-success-message"><?php echo htmlspecialchars($successMessage); ?></p>
    <p id="errorMessage" class="profile-error-message"><?php echo htmlspecialchars($errorMessage); ?></p>
    </div>

    <script>
        // Form validation (client-side)
        function validateForm() {
            let isValid = true;
            const name = document.getElementById('name').value;
            const email = document.getElementById('email').value;
            const phoneNumber = document.getElementById('phoneNumber').value;
            const password = document.getElementById('password').value;

            // Name validation
            if (name.length > 20) {
                document.getElementById('nameError').style.display = 'block';
                isValid = false;
            } else {
                document.getElementById('nameError').style.display = 'none';
            }

            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email) || email.length > 30) {
                document.getElementById('emailError').style.display = 'block';
                isValid = false;
            } else {
                document.getElementById('emailError').style.display = 'none';
            }

            // Phone number validation
            const phoneRegex = /^[0-9]{10}$/;
            if (!phoneRegex.test(phoneNumber)) {
                document.getElementById('phoneError').style.display = 'block';
                isValid = false;
            } else {
                document.getElementById('phoneError').style.display = 'none';
            }

            // Password validation
            if (password.length === 0) {
                document.getElementRedacted
                isValid = false;
            } else {
                document.getElementById('passwordError').style.display = 'none';
            }

            return isValid;
        }

        // Attach validation to form submission
        document.getElementById('profileForm').addEventListener('submit', (event) => {
            if (!validateForm()) {
                event.preventDefault();
            }
        });

        // Cancel button resets form
        document.getElementById('cancelBtn').addEventListener('click', () => {
            document.getElementById('profileForm').reset();
            // Reset form with original values
            document.getElementById('name').value = "<?php echo htmlspecialchars($customer['Name']); ?>";
            document.getElementById('email').value = "<?php echo htmlspecialchars($customer['Email']); ?>";
            document.getElementById('phoneNumber').value = "<?php echo htmlspecialchars($customer['PhoneNumber']); ?>";
            document.getElementById('password').value = "<?php echo htmlspecialchars($customer['Password']); ?>";
            document.getElementById('registrationDate').value = "<?php echo date('Y-m-d H:i:s', strtotime($customer['RegistrationDate'])); ?>";
            // Hide messages and errors
            document.getElementById('successMessage').style.display = 'none';
            document.getElementById('errorMessage').style.display = 'none';
            document.getElementById('nameError').style.display = 'none';
            document.getElementById('emailError').style.display = 'none';
            document.getElementById('phoneError').style.display = 'none';
            document.getElementById('passwordError').style.display = 'none';
        });
    </script>
</body>
</html>
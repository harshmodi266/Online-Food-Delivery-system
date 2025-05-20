<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link href="css/style.css" rel="stylesheet">
    <!-- Import Roboto font from Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
</head>
<style>
    /* Import Roboto font (already included in the HTML via Google Fonts) */

/* Footer container styling */
.footer {
    background-color: #1a252f; /* Dark background for the footer */
    padding: 40px 0; /* Increased padding for better spacing */
    color: #fff; /* White text for the footer */
    font-family: 'Roboto', sans-serif; /* Apply Roboto font */
}

/* Ensure the footer container uses Flexbox for layout */
.footer-container {
    display: flex;
    justify-content: space-around; /* Evenly space the sections */
    flex-wrap: wrap; /* Allow wrapping on smaller screens */
    gap: 20px; /* Add spacing between sections */
    max-width: 1200px; /* Limit the width for larger screens */
    margin: 0 auto; /* Center the container */
    padding: 0 20px; /* Add padding to the sides */
}

/* Style for all footer sections to ensure consistency */
.footer-section {
    flex: 1; /* Each section takes equal space */
    min-width: 200px; /* Ensure sections don’t collapse on smaller screens */
    padding: 20px; /* Consistent padding for all sections */
    border-radius: 10px; /* Rounded corners for all sections */
    text-align: center; /* Center-align text in all sections */
}

/* Specific styling for the "Lazeez Restaurant" section (green card) */
.footer-section.about {
    background-color: #4CAF50; /* Green background */
    color: #fff; /* White text */
}

/* Ensure the h2 in the about section has proper styling */
.footer-section.about h2 {
    rgb(202, 177, 37); /* Yellow color for "Lazeez Restaurant" */
    font-size: 24px; /* Adjust font size */
    margin-bottom: 10px; /* Space below the heading */
}

/* Ensure the paragraph in the about section has proper styling */
.footer-section.about p {
    color: #fff; /* White color for the description */
    font-size: 14px; /* Adjust font size */
    line-height: 1.5; /* Improve readability */
}

/* Style for the other sections (Quick Links, Contact Us, Follow Us, Developer Information) */
.footer-section.links,
.footer-section.contact,
.footer-section.social,
.footer-section.developer {
    background-color: #333; /* Dark gray background for other sections */
}

/* Style for the headings in other sections */
.footer-section.links h3,
.footer-section.contact h3,
.footer-section.social h3,
.footer-section.developer h3,
.footer-bottom h5{
    color:rgb(202, 177, 37); /* Yellow color for headings */
    font-size: 18px; /* Adjust font size */
    margin-bottom: 15px; /* Space below the heading */
}

/* Style for the Quick Links section */
.footer-section.links ul {
    list-style: none;
    padding: 0;
}

.footer-section.links ul li {
    margin-bottom: 10px; /* Space between links */
}

.footer-section.links ul li a {
    color: #fff; /* White color for links */
    text-decoration: none;
    font-size: 14px; /* Adjust font size */
}

.footer-section.links ul li a:hover {
    text-decoration: underline; /* Underline on hover */
}

/* Style for the Contact Us section */
.footer-section.contact p {
    margin: 10px 0; /* Space between contact details */
    font-size: 14px; /* Adjust font size */
}

.footer-section.contact a {
    color: #fff; /* White color for email link */
    text-decoration: none;
}

.footer-section.contact a:hover {
    text-decoration: underline; /* Underline on hover */
}

/* Style for the Follow Us section */
.footer-section.social .social-icons {
    display: flex;
    justify-content: center; /* Center the social icons */
    gap: 15px; /* Space between icons */
}

.footer-section.social .social-icons a img {
    width: 30px; /* Adjust icon size */
    height: 30px;
}

/* Style for the Developer Information section */
.footer-section.developer p {
    margin: 10px 0; /* Space between developer details */
    font-size: 14px; /* Adjust font size */
}

.footer-section.developer a {
    color: #fff; /* White color for links */
    text-decoration: none;
}

.footer-section.developer a:hover {
    text-decoration: underline; /* Underline on hover */
}

/* Style for the footer bottom */
.footer-bottom {
    text-align: center;
    padding: 15px 0;
    background-color: #1a252f; /* Match the footer background */
    color: #fff;
    font-size: 14px;
    border-top: 1px solid #333; /* Add a subtle separator */
    margin-top: 20px; /* Space above the copyright */
}
</style>
<body>
<footer class="footer">
    <div class="footer-container">
        <div class="footer-section about">
            <h2>Lazeez Restaurant</h2>
            <p>Experience the best flavors in town with our delicious and freshly prepared dishes.</p>
        </div>
        <div class="footer-section links">
            <h3>Quick Links</h3>
            <ul>
                <li><a href="menu.php">Menu</a></li>
                <li><a href="aboutus.php">About</a></li>
                <li><a href="contactus.php">Contact</a></li>
                <li><a href="review copy.php">Reviews</a></li>
            </ul>
        </div>
        <div class="footer-section contact">
            <h3>Contact Us</h3>
            <p>Email: <a href="mailto:support@lazeezrestaurant.com">support@lazeezrestaurant.com</a></p>
            <p>Phone: +91 12345 67890</p>
            <p>Location: 34 Vishal Chambers, Behind National Plaza, Alkapuri, Vadodara, Gujarat, India</p>
        </div>
        <div class="footer-section social">
            <h3>Follow Us</h3>
            <div class="social-icons">
                <a href="#"><img src="social_media/facebook-.png" alt="Facebook"></a>
                <a href="#"><img src="social_media/instagram.png" alt="Instagram"></a>
                <a href="#"><img src="social_media/twitter.png" alt="Twitter"></a>
            </div>
        </div>
        <div class="footer-bottom">
        <h5>Developed by:</h5>    
        <pre> Jainam Mochi   |   Vidhi Mochi   |   Harsh Modi </pre>
        <p>© 2025 Lazeez Restaurant | All Rights Reserved</p>
    </div>
    </div>
</footer>
</body>
</html>
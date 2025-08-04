Online Food Delivery System

Overview

The Online Food Delivery System is a web-based platform designed for Lazeez Restaurant to streamline food ordering and delivery. It allows customers to browse menus, place orders, make secure payments, and track deliveries, while providing restaurant staff with tools to manage orders efficiently. This project was developed as part of a Master of Science in Computer Applications at Hemchandracharya North Gujarat University (HNGU).

Features





User Module: Register, login, browse food menus, place orders, make payments, and view order history.



Admin Module: Manage menu items, track orders, and oversee delivery operations.



Delivery Module: Delivery personnel can check orders, collect items, deliver food, and report issues.



Secure Payments: Supports UPI payments with dynamic QR code generation.



Responsive Design: Works seamlessly on desktops, tablets, and mobile devices.

Technologies Used





Frontend: HTML, CSS, JavaScript



Backend: PHP, MySQL



Tools: Visual Studio Code



Libraries: phpqrcode (for QR code generation)

Prerequisites

Before setting up the project, ensure you have the following:





A web server (e.g., XAMPP, WAMP) with PHP support



MySQL database



A modern web browser (Chrome, Firefox, Edge, Safari)

Installation





Clone or Download the Project:





Clone this repository or download the ZIP file.

git clone <repository-url>



Set Up the Web Server:





Place the project folder in your web server’s root directory (e.g., htdocs for XAMPP).



Configure the Database:





Create a MySQL database named lazeez_food_delivery.



Import the provided database.sql file to set up tables (e.g., customer, order, menu).



Update the database connection settings in config.php with your credentials:

$host = "localhost";
$username = "root";
$password = "";
$dbname = "lazeez_food_delivery";



Run the Project:





Start your web server (e.g., Apache and MySQL via XAMPP).



Open your browser and navigate to http://localhost/<project-folder-name>.

Usage





For Customers: Register an account, browse the menu, add items to your cart, and place an order. Use the UPI QR code to make a payment and track your delivery.



For Admins: Log in to the admin dashboard to manage menu items, view orders, and assign deliveries.



For Delivery Personnel: Log in to view assigned orders, collect food from the restaurant, and update delivery status.

Screenshots





Homepage: Displays the menu and order options.



User Dashboard: Shows order history and profile settings.



Admin Panel: Manages menu and orders.

Future Enhancements





Develop a mobile app for iOS and Android.



Add a live order tracking map.



Implement a loyalty program for regular customers.



Introduce eco-friendly packaging options.

Created by Harsh Modi
Acknowledgments


gread :- 250 into 235 


Thanks to our project guides, Dr. Jignesh Rami and Dr. Kirit Chokhavala, for their guidance.



Gratitude to Lazeez Restaurant staff for their valuable insights.



Special thanks to HNGU faculty and online communities for their support.

License

This project is licensed under the MIT License.

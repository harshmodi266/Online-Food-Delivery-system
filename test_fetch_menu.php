<?php
session_start();
$_SESSION['CustomerID'] = 1; // Replace with a valid CustomerID for testing
$_POST['category_id'] = '1'; // Replace with a valid CategoryID from your foodcategory table
include 'fetch_menu.php';
?>
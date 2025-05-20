<?php
session_start();
session_unset(); 
session_destroy(); // Destroy all session data
header("Location: index.php"); // Redirect to login page
exit();
?>

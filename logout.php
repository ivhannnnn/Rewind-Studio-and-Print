<?php
session_start();

// Destroy all session data
session_unset();
session_destroy();

// Start a new session to store the logout message
session_start();
$_SESSION['logout_success'] = "You have successfully logged out.";

// Redirect to home page
header("Location: index.php");
exit();
?>

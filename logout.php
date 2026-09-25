<?php

session_start();

// Remove all session variables
$_SESSION = [];

// Destroy the session
session_destroy();

// Send the user back to the login page
header("Location: login.php");
exit;

?>
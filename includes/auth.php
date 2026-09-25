<?php

// Start the session if it has not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in
if (!isset($_SESSION["user_id"])) {

    header("Location: ../login.php");
    exit;
}

// Check if the logged-in user is an administrator
if (!isset($_SESSION["user_role"]) || $_SESSION["user_role"] !== "admin") {

    session_unset();
    session_destroy();

    header("Location: ../login.php");
    exit;
}

?>
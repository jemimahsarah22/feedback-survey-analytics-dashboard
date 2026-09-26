<?php

/*
|--------------------------------------------------------------------------
| Secure Admin Authentication
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {

    // Secure session cookie settings
    ini_set("session.cookie_httponly", "1");
    ini_set("session.cookie_samesite", "Lax");

    // Use secure cookies when the site is running over HTTPS
    if (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") {
        ini_set("session.cookie_secure", "1");
    }

    session_start();
}

/*
|--------------------------------------------------------------------------
| Check Login
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["user_id"])) {

    header("Location: ../login.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Check Admin Role
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION["user_role"]) ||
    $_SESSION["user_role"] !== "admin"
) {

    session_unset();
    session_destroy();

    header("Location: ../login.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Session Timeout
|--------------------------------------------------------------------------
|
| Automatically logs the admin out after 30 minutes of inactivity.
|
*/

$session_timeout = 1800; // 30 minutes

if (
    isset($_SESSION["last_activity"]) &&
    (time() - $_SESSION["last_activity"]) > $session_timeout
) {

    session_unset();
    session_destroy();

    header("Location: ../login.php?timeout=1");
    exit;
}

/*
|--------------------------------------------------------------------------
| Update Last Activity
|--------------------------------------------------------------------------
*/

$_SESSION["last_activity"] = time();

?>
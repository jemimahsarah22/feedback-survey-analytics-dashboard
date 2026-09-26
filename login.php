<?php
require_once "config/database.php";
require_once "includes/csrf.php";

$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf_token();
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    if ($email === "" || $password === "") {
        $error = "Please enter your email and password.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($user && password_verify($password, $user["password"]) && $user["role"] === "admin") {
                session_regenerate_id(true);
                $_SESSION["user_id"] = (int)$user["id"];
                $_SESSION["user_name"] = $user["name"];
                $_SESSION["user_email"] = $user["email"];
                $_SESSION["user_role"] = $user["role"];
                $_SESSION["last_activity"] = time();
                header("Location: admin/dashboard.php"); exit;
            }
        }
        $error = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Admin Login</title><link rel="stylesheet" href="assets/css/style.css"></head>
<body><div class="center"><main class="login-card">
<a class="back" href="index.php">← Back to home</a>
<div class="eyebrow">Administrator</div><h1>Welcome back</h1><p class="muted">Sign in to manage surveys and view analytics.</p>
<?php if ($error !== ""): ?><div class="alert error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<?php if (isset($_GET["timeout"])): ?><div class="alert warning">Your session expired. Please log in again.</div><?php endif; ?>
<form method="POST" action="">
<?php echo csrf_field(); ?>
<div class="field"><label for="email">Email</label><input type="email" id="email" name="email" required maxlength="150" autocomplete="username"></div>
<div class="field"><label for="password">Password</label><input type="password" id="password" name="password" required autocomplete="current-password"></div>
<button type="submit">Login</button>
</form>
</main></div></body></html>

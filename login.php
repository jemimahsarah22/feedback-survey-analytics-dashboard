<?php

session_start();

require_once "config/database.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    // Check that both fields are filled
    if (empty($email) || empty($password)) {

        $error = "Please enter both email and password.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } else {

        // Find the user by email
        $stmt = $conn->prepare(
            "SELECT id, name, email, password, role
             FROM users
             WHERE email = ?"
        );

        $stmt->bind_param("s", $email);

        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $user = $result->fetch_assoc();

            // Check password
            if (password_verify($password, $user["password"])) {

                // Check admin role
                if ($user["role"] === "admin") {

                    // Create login session
                    $_SESSION["user_id"] = $user["id"];
                    $_SESSION["user_name"] = $user["name"];
                    $_SESSION["user_email"] = $user["email"];
                    $_SESSION["user_role"] = $user["role"];

                    // Redirect to admin dashboard
                    header("Location: admin/dashboard.php");
                    exit;

                } else {

                    $error = "You do not have administrator access.";

                }

            } else {

                $error = "Incorrect email or password.";

            }

        } else {

            $error = "Incorrect email or password.";

        }

        $stmt->close();
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Admin Login</title>

</head>

<body>

    <h1>Admin Login</h1>

    <?php if (!empty($error)): ?>

        <p style="color: red;">
            <?php echo htmlspecialchars($error); ?>
        </p>

    <?php endif; ?>


    <form method="POST">

        <label for="email">
            Email:
        </label>

        <br>

        <input
            type="email"
            id="email"
            name="email"
            required
        >

        <br><br>


        <label for="password">
            Password:
        </label>

        <br>

        <input
            type="password"
            id="password"
            name="password"
            required
        >

        <br><br>


        <button type="submit">
            Login
        </button>

    </form>

</body>

</html>
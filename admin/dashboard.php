<?php
require_once "../includes/auth.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard</title>
</head>

<body>

    <h1>Admin Dashboard</h1>

    <p>
        Welcome,
        <strong>
            <?php echo htmlspecialchars($_SESSION["user_name"]); ?>
        </strong>
    </p>

    <hr>

    <h2>Dashboard Menu</h2>

    <ul>
        <li>
            <a href="surveys.php">Manage Surveys</a>
        </li>

        <li>
            <a href="questions.php">Manage Questions</a>
        </li>

        <li>
            <a href="../logout.php">Logout</a>
        </li>
    </ul>

</body>
</html>
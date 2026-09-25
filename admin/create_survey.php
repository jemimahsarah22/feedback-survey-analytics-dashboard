<?php
require_once "../includes/auth.php";
require_once "../config/database.php";

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $title = trim($_POST["title"]);
    $description = trim($_POST["description"]);

    // Basic validation
    if (empty($title)) {
        $error = "Survey title is required.";

    } elseif (strlen($title) > 200) {
        $error = "Survey title must not exceed 200 characters.";

    } else {

        $created_by = $_SESSION["user_id"];

        $stmt = $conn->prepare(
            "INSERT INTO surveys (title, description, created_by)
             VALUES (?, ?, ?)"
        );

        $stmt->bind_param(
            "ssi",
            $title,
            $description,
            $created_by
        );

        if ($stmt->execute()) {

            $message = "Survey created successfully!";

        } else {

            $error = "Error creating survey: " . $stmt->error;
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

    <title>Create Survey</title>

</head>

<body>

    <h1>Create New Survey</h1>

    <p>
        <a href="surveys.php">← Back to Surveys</a>
    </p>

    <hr>

    <?php if (!empty($message)): ?>

        <p style="color: green;">
            <?php echo htmlspecialchars($message); ?>
        </p>

    <?php endif; ?>


    <?php if (!empty($error)): ?>

        <p style="color: red;">
            <?php echo htmlspecialchars($error); ?>
        </p>

    <?php endif; ?>


    <form method="POST">

        <div>

            <label for="title">
                Survey Title:
            </label>

            <br>

            <input
                type="text"
                id="title"
                name="title"
                maxlength="200"
                required
            >

        </div>

        <br>


        <div>

            <label for="description">
                Survey Description:
            </label>

            <br>

            <textarea
                id="description"
                name="description"
                rows="6"
                cols="50"
            ></textarea>

        </div>

        <br>


        <button type="submit">
            Create Survey
        </button>

    </form>

</body>

</html>
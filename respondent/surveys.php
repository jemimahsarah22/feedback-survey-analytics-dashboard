<?php
require_once "../config/database.php";


// Get active surveys
$sql = "SELECT
            id,
            title,
            description,
            created_at
        FROM surveys
        WHERE status = 'active'
        ORDER BY created_at DESC";

$result = $conn->query($sql);

if (!$result) {
    die("Error retrieving surveys: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Available Surveys</title>

</head>

<body>

    <h1>Available Surveys</h1>

    <p>
        Select a survey below to provide your feedback.
    </p>

    <hr>


    <?php if ($result->num_rows > 0): ?>

        <?php while ($survey = $result->fetch_assoc()): ?>

            <div>

                <h2>
                    <?php echo htmlspecialchars($survey["title"]); ?>
                </h2>

                <p>
                    <?php
                    echo htmlspecialchars(
                        $survey["description"] ?? ""
                    );
                    ?>
                </p>

                <p>
                    Created:
                    <?php echo htmlspecialchars($survey["created_at"]); ?>
                </p>

                <p>
                    <a href="survey.php?id=<?php echo $survey["id"]; ?>">
                        Take This Survey
                    </a>
                </p>

            </div>

            <hr>

        <?php endwhile; ?>

    <?php else: ?>

        <p>
            There are currently no active surveys available.
        </p>

    <?php endif; ?>

</body>

</html>
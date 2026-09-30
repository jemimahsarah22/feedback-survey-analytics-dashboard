<?php

$survey_id = null;

if (isset($_GET["survey_id"]) && is_numeric($_GET["survey_id"])) {
    $survey_id = (int) $_GET["survey_id"];
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Thank You</title>

    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

    <h1>Response Submitted Successfully!!!</h1>

    <hr>

    <h2>Your response has been saved successfully.</h2>

    <p>
        Thank you for taking the time to complete this survey.
        Your feedback had been recored successfully.
    </p>

    <p>
        You can now return to the survey or view otehr available surveys.
    </p>

    <hr>

    <?php if ($survey_id !== null): ?>

        <p>
            <a href="survey.php?id=<?php echo $survey_id; ?>">
                ← Return to Survey
            </a>
        </p>

    <?php endif; ?>

    <p>
        <a href="surveys.php">
            View Available Surveys
        </a>
    </p>

</body>

</html>
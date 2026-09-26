<?php
require_once "../includes/auth.php";
require_once "../config/database.php";
require_once "../includes/csrf.php";

$error = "";
$success = "";


// --------------------------------------------------
// Get question ID
// --------------------------------------------------

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    die("Invalid question ID.");
}

$question_id = (int) $_GET["id"];


// --------------------------------------------------
// Handle form submission
// --------------------------------------------------

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    verify_csrf_token();

    $question_text = trim($_POST["question_text"]);
    $question_type = $_POST["question_type"];
    $is_required = isset($_POST["is_required"]) ? 1 : 0;

    if (empty($question_text)) {

        $error = "Question text is required.";

    } elseif (strlen($question_text) > 1000) {

        $error = "Question text is too long.";

    } elseif (
        !in_array(
            $question_type,
            ["text", "rating", "multiple_choice", "yes_no"]
        )
    ) {

        $error = "Invalid question type.";

    } else {

        $stmt = $conn->prepare(
            "UPDATE questions
             SET question_text = ?,
                 question_type = ?,
                 is_required = ?
             WHERE id = ?"
        );

        $stmt->bind_param(
            "ssii",
            $question_text,
            $question_type,
            $is_required,
            $question_id
        );

        if ($stmt->execute()) {

            $success = "Question updated successfully.";

        } else {

            $error = "Error updating question: " . $stmt->error;
        }

        $stmt->close();
    }
}


// --------------------------------------------------
// Get question
// --------------------------------------------------

$stmt = $conn->prepare(
    "SELECT
        questions.id,
        questions.survey_id,
        questions.question_text,
        questions.question_type,
        questions.is_required,
        surveys.title AS survey_title
     FROM questions
     INNER JOIN surveys
        ON questions.survey_id = surveys.id
     WHERE questions.id = ?"
);

$stmt->bind_param("i", $question_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {

    $stmt->close();

    die("Question not found.");
}

$question = $result->fetch_assoc();

$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Edit Question</title>

    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

    <h1>Edit Question</h1>

    <p>
        Survey:
        <strong>
            <?php echo htmlspecialchars($question["survey_title"]); ?>
        </strong>
    </p>

    <p>
        <a href="questions.php?survey_id=<?php echo $question["survey_id"]; ?>">
            ← Back to Questions
        </a>
    </p>

    <hr>


    <?php if (!empty($success)): ?>

        <p style="color: green;">
            <?php echo htmlspecialchars($success); ?>
        </p>

    <?php endif; ?>


    <?php if (!empty($error)): ?>

        <p style="color: red;">
            <?php echo htmlspecialchars($error); ?>
        </p>

    <?php endif; ?>


    <form method="POST">

        <?php echo csrf_field(); ?>

        <div>

            <label for="question_text">
                Question:
            </label>

            <br>

            <textarea
                id="question_text"
                name="question_text"
                rows="5"
                cols="60"
                maxlength="1000"
                required
            ><?php echo htmlspecialchars($question["question_text"]); ?></textarea>

        </div>

        <br>


        <div>

            <label for="question_type">
                Question Type:
            </label>

            <br>

            <select
                id="question_type"
                name="question_type"
                required
            >

                <option
                    value="text"
                    <?php echo ($question["question_type"] === "text") ? "selected" : ""; ?>
                >
                    Text
                </option>

                <option
                    value="rating"
                    <?php echo ($question["question_type"] === "rating") ? "selected" : ""; ?>
                >
                    Rating
                </option>

                <option
                    value="multiple_choice"
                    <?php echo ($question["question_type"] === "multiple_choice") ? "selected" : ""; ?>
                >
                    Multiple Choice
                </option>

                <option
                    value="yes_no"
                    <?php echo ($question["question_type"] === "yes_no") ? "selected" : ""; ?>
                >
                    Yes / No
                </option>

            </select>

        </div>

        <br>


        <div>

            <label>

                <input
                    type="checkbox"
                    name="is_required"
                    value="1"
                    <?php echo $question["is_required"] ? "checked" : ""; ?>
                >

                Required question

            </label>

        </div>

        <br>


        <button type="submit">
            Update Question
        </button>

    </form>

</body>

</html>
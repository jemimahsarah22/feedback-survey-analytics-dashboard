<?php
require_once "../includes/auth.php";
require_once "../config/database.php";
require_once "../includes/csrf.php";

$error = "";
$success = "";


// --------------------------------------------------
// Get survey ID
// --------------------------------------------------

if (!isset($_GET["survey_id"]) || !is_numeric($_GET["survey_id"])) {
    die("Invalid survey ID.");
}

$survey_id = (int) $_GET["survey_id"];


// --------------------------------------------------
// Check that survey exists
// --------------------------------------------------

$stmt = $conn->prepare(
    "SELECT id, title
     FROM surveys
     WHERE id = ?"
);

$stmt->bind_param("i", $survey_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $stmt->close();
    die("Survey not found.");
}

$survey = $result->fetch_assoc();

$stmt->close();


// --------------------------------------------------
// Handle form submission
// --------------------------------------------------

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    verify_csrf_token();

    $question_text = trim($_POST["question_text"]);
    $question_type = $_POST["question_type"];
    $is_required = isset($_POST["is_required"]) ? 1 : 0;


    // Validate question
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

        // Get the next question order
        $order_stmt = $conn->prepare(
            "SELECT COALESCE(MAX(question_order), 0) + 1 AS next_order
             FROM questions
             WHERE survey_id = ?"
        );

        $order_stmt->bind_param("i", $survey_id);
        $order_stmt->execute();

        $order_result = $order_stmt->get_result();
        $order_row = $order_result->fetch_assoc();

        $question_order = (int) $order_row["next_order"];

        $order_stmt->close();


        // Insert question
        $stmt = $conn->prepare(
            "INSERT INTO questions
             (survey_id, question_text, question_type, is_required, question_order)
             VALUES (?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "issii",
            $survey_id,
            $question_text,
            $question_type,
            $is_required,
            $question_order
        );

        if ($stmt->execute()) {

            $success = "Question created successfully.";

        } else {

            $error = "Error creating question: " . $stmt->error;
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

    <title>Create Question</title>

    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

    <h1>Create Question</h1>

    <p>
        Survey:
        <strong>
            <?php echo htmlspecialchars($survey["title"]); ?>
        </strong>
    </p>

    <p>
        <a href="questions.php?survey_id=<?php echo $survey_id; ?>">
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

        <!-- Question text -->

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
            ></textarea>

        </div>

        <br>


        <!-- Question type -->

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

                <option value="">
                    -- Select Type --
                </option>

                <option value="text">
                    Text
                </option>

                <option value="rating">
                    Rating
                </option>

                <option value="multiple_choice">
                    Multiple Choice
                </option>

                <option value="yes_no">
                    Yes / No
                </option>

            </select>

        </div>

        <br>


        <!-- Required -->

        <div>

            <label>

                <input
                    type="checkbox"
                    name="is_required"
                    value="1"
                    checked
                >

                Required question

            </label>

        </div>

        <br>


        <button type="submit">
            Create Question
        </button>

    </form>

</body>

</html>
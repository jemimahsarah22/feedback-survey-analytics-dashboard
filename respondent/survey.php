<?php
require_once "../config/database.php";
require_once "../includes/csrf.php";


// --------------------------------------------------
// Get survey ID
// --------------------------------------------------

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    die("Invalid survey ID.");
}

$survey_id = (int) $_GET["id"];


// --------------------------------------------------
// Get active survey
// --------------------------------------------------

$stmt = $conn->prepare(
    "SELECT id, title, description
     FROM surveys
     WHERE id = ?
     AND status = 'active'"
);

$stmt->bind_param("i", $survey_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $stmt->close();
    die("Survey not found or is no longer active.");
}

$survey = $result->fetch_assoc();

$stmt->close();


// --------------------------------------------------
// Get questions
// --------------------------------------------------

$stmt = $conn->prepare(
    "SELECT
        id,
        question_text,
        question_type,
        is_required,
        question_order
     FROM questions
     WHERE survey_id = ?
     ORDER BY question_order ASC, id ASC"
);

$stmt->bind_param("i", $survey_id);
$stmt->execute();

$result = $stmt->get_result();

$questions = [];

while ($row = $result->fetch_assoc()) {
    $questions[] = $row;
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        <?php echo htmlspecialchars($survey["title"]); ?>
    </title>

    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

    <h1>
        <?php echo htmlspecialchars($survey["title"]); ?>
    </h1>

    <p>
        <?php
        echo htmlspecialchars(
            $survey["description"] ?? ""
        );
        ?>
    </p>

    <hr>


    <?php if (count($questions) > 0): ?>

        <form
            method="POST"
            action="submit_response.php"
            id="surveyForm"
        >

            <!-- Keep survey ID when submitting -->

            <?php echo csrf_field(); ?>

            <input
                type="hidden"
                name="survey_id"
                value="<?php echo $survey_id; ?>"
            >


            <?php foreach ($questions as $question): ?>

                <div
                    data-question-id="<?php echo $question["id"]; ?>"
                    data-type="<?php echo htmlspecialchars($question["question_type"]); ?>"
                    data-required="<?php echo $question["is_required"] ? "true" : "false"; ?>"
                >

                    <h3>
                        <?php echo htmlspecialchars($question["question_text"]); ?>

                        <?php if ($question["is_required"]): ?>

                            <span style="color: red;">
                                *
                            </span>

                        <?php endif; ?>

                    </h3>


                    <?php if ($question["question_type"] === "text"): ?>

                        <textarea
                            name="answers[<?php echo $question["id"]; ?>]"
                            rows="5"
                            cols="60"
                            <?php echo $question["is_required"] ? "required" : ""; ?>
                        ></textarea>


                    <?php elseif ($question["question_type"] === "rating"): ?>

                        <div>

                            <label>
                                <input
                                    type="radio"
                                    name="answers[<?php echo $question["id"]; ?>]"
                                    value="1"
                                    <?php echo $question["is_required"] ? "required" : ""; ?>
                                >
                                1
                            </label>

                            <label>
                                <input
                                    type="radio"
                                    name="answers[<?php echo $question["id"]; ?>]"
                                    value="2"
                                >
                                2
                            </label>

                            <label>
                                <input
                                    type="radio"
                                    name="answers[<?php echo $question["id"]; ?>]"
                                    value="3"
                                >
                                3
                            </label>

                            <label>
                                <input
                                    type="radio"
                                    name="answers[<?php echo $question["id"]; ?>]"
                                    value="4"
                                >
                                4
                            </label>

                            <label>
                                <input
                                    type="radio"
                                    name="answers[<?php echo $question["id"]; ?>]"
                                    value="5"
                                >
                                5
                            </label>

                        </div>


                    <?php elseif ($question["question_type"] === "yes_no"): ?>

                        <div>

                            <label>

                                <input
                                    type="radio"
                                    name="answers[<?php echo $question["id"]; ?>]"
                                    value="Yes"
                                    <?php echo $question["is_required"] ? "required" : ""; ?>
                                >

                                Yes

                            </label>


                            <label>

                                <input
                                    type="radio"
                                    name="answers[<?php echo $question["id"]; ?>]"
                                    value="No"
                                >

                                No

                            </label>

                        </div>


                    <?php elseif ($question["question_type"] === "multiple_choice"): ?>

                        <?php
                        $option_stmt = $conn->prepare(
                            "SELECT id, option_text FROM question_options WHERE question_id = ? ORDER BY option_order ASC, id ASC"
                        );
                        $option_stmt->bind_param("i", $question["id"]);
                        $option_stmt->execute();
                        $option_result = $option_stmt->get_result();
                        ?>

                        <div class="choices">
                            <?php if ($option_result->num_rows > 0): ?>
                                <?php while ($option = $option_result->fetch_assoc()): ?>
                                    <label class="choice">
                                        <input
                                            type="radio"
                                            name="answers[<?php echo $question["id"]; ?>]"
                                            value="<?php echo htmlspecialchars($option["id"]); ?>"
                                            <?php echo $question["is_required"] ? "required" : ""; ?>
                                        >
                                        <?php echo htmlspecialchars($option["option_text"]); ?>
                                    </label>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p class="muted">No options have been configured for this question.</p>
                            <?php endif; ?>
                        </div>

                        <?php $option_stmt->close(); ?>

                    <?php endif; ?>

                </div>

                <hr>

            <?php endforeach; ?>


            <button type="submit">
                Submit Survey
            </button>

        </form>


    <?php else: ?>

        <p>
            This survey does not have any questions yet.
        </p>

    <?php endif; ?>


    <p>

        <a href="surveys.php">
            ← Back to Surveys
        </a>

    </p>

<script src="../assets/js/survey_validation.js"></script>

</body>

</html>
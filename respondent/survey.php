<?php
require_once "../config/database.php";


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

                        <p>
                            Multiple-choice options have not been configured yet.
                        </p>

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
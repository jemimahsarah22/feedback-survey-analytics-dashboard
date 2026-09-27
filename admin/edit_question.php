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

    $question_text = trim($_POST["question_text"] ?? "");
    $question_type = $_POST["question_type"] ?? "";
    $is_required = isset($_POST["is_required"]) ? 1 : 0;

    $options = $_POST["options"] ?? [];

    if (!is_array($options)) {
        $options = [];
    }


    // --------------------------------------------------
    // Validate question
    // --------------------------------------------------

    if (empty($question_text)) {

        $error = "Question text is required.";

    } elseif (strlen($question_text) > 1000) {

        $error = "Question text is too long.";

    } elseif (
        !in_array(
            $question_type,
            ["text", "rating", "multiple_choice", "yes_no"],
            true
        )
    ) {

        $error = "Invalid question type.";

    }


    // --------------------------------------------------
    // Validate multiple-choice options
    // --------------------------------------------------

    if (
        empty($error) &&
        $question_type === "multiple_choice"
    ) {

        $clean_options = [];

        foreach ($options as $option) {

            $option = trim((string) $option);

            if ($option !== "") {
                $clean_options[] = $option;
            }
        }

        if (count($clean_options) < 2) {

            $error = "Multiple-choice questions require at least 2 options.";

        } elseif (count($clean_options) > 10) {

            $error = "You can add a maximum of 10 options.";

        } else {

            $options = $clean_options;
        }
    }


    // --------------------------------------------------
    // Update question
    // --------------------------------------------------

    if (empty($error)) {

        $conn->begin_transaction();

        try {

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

            if (!$stmt->execute()) {
                throw new Exception("Error updating question.");
            }

            $stmt->close();


            // --------------------------------------------------
            // Replace multiple-choice options
            // --------------------------------------------------

            if ($question_type === "multiple_choice") {

                // Remove old options
                $delete_stmt = $conn->prepare(
                    "DELETE FROM question_options
                     WHERE question_id = ?"
                );

                $delete_stmt->bind_param(
                    "i",
                    $question_id
                );

                if (!$delete_stmt->execute()) {
                    throw new Exception("Could not remove old options.");
                }

                $delete_stmt->close();


                // Insert new options
                $option_stmt = $conn->prepare(
                    "INSERT INTO question_options
                     (question_id, option_text, option_order)
                     VALUES (?, ?, ?)"
                );

                $option_order = 1;

                foreach ($options as $option_text) {

                    $option_stmt->bind_param(
                        "isi",
                        $question_id,
                        $option_text,
                        $option_order
                    );

                    if (!$option_stmt->execute()) {
                        throw new Exception("Could not save question options.");
                    }

                    $option_order++;
                }

                $option_stmt->close();

            } else {

                // If question type changed away from
                // multiple choice, remove old options.
                $delete_stmt = $conn->prepare(
                    "DELETE FROM question_options
                     WHERE question_id = ?"
                );

                $delete_stmt->bind_param(
                    "i",
                    $question_id
                );

                $delete_stmt->execute();

                $delete_stmt->close();
            }


            $conn->commit();

            $success = "Question updated successfully.";

        } catch (Exception $e) {

            $conn->rollback();

            $error = $e->getMessage();
        }
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


// --------------------------------------------------
// Get existing options
// --------------------------------------------------

$existing_options = [];

$option_stmt = $conn->prepare(
    "SELECT id, option_text, option_order
     FROM question_options
     WHERE question_id = ?
     ORDER BY option_order ASC, id ASC"
);

$option_stmt->bind_param(
    "i",
    $question_id
);

$option_stmt->execute();

$option_result = $option_stmt->get_result();

while ($option = $option_result->fetch_assoc()) {
    $existing_options[] = $option;
}

$option_stmt->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Edit Question</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

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
            ><?php echo htmlspecialchars($question["question_text"]); ?></textarea>

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
                onchange="toggleOptions()"
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


        <!-- Multiple-choice options -->

        <div
            id="optionsSection"
            style="<?php echo $question["question_type"] === "multiple_choice" ? "display:block;" : "display:none;"; ?>"
        >

            <label>
                Answer Options:
            </label>

            <p>
                Add at least 2 options.
            </p>


            <div id="optionsContainer">

                <?php if (count($existing_options) > 0): ?>

                    <?php foreach ($existing_options as $option): ?>

                        <div class="option-row">

                            <input
                                type="text"
                                name="options[]"
                                value="<?php echo htmlspecialchars($option["option_text"]); ?>"
                                maxlength="255"
                                placeholder="Enter an option"
                            >

                        </div>

                    <?php endforeach; ?>

                <?php else: ?>

                    <div class="option-row">

                        <input
                            type="text"
                            name="options[]"
                            maxlength="255"
                            placeholder="Enter an option"
                        >

                    </div>

                    <div class="option-row">

                        <input
                            type="text"
                            name="options[]"
                            maxlength="255"
                            placeholder="Enter an option"
                        >

                    </div>

                <?php endif; ?>

            </div>


            <br>

            <button
                type="button"
                onclick="addOption()"
            >
                + Add Option
            </button>

        </div>

        <br>


        <!-- Required -->

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


<script>

function toggleOptions() {

    const type =
        document.getElementById("question_type").value;

    const section =
        document.getElementById("optionsSection");

    if (type === "multiple_choice") {

        section.style.display = "block";

    } else {

        section.style.display = "none";

    }
}


function addOption() {

    const container =
        document.getElementById("optionsContainer");

    const rows =
        container.querySelectorAll(".option-row");

    if (rows.length >= 10) {

        alert("Maximum 10 options allowed.");

        return;
    }

    const row =
        document.createElement("div");

    row.className = "option-row";

    row.innerHTML = `
        <input
            type="text"
            name="options[]"
            maxlength="255"
            placeholder="Enter an option"
        >
    `;

    container.appendChild(row);
}

</script>

</body>

</html>
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
    // Create question
    // --------------------------------------------------

    if (empty($error)) {

        // Get next question order
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


        // Start transaction
        $conn->begin_transaction();

        try {

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

            if (!$stmt->execute()) {
                throw new Exception("Error creating question.");
            }

            $question_id = $conn->insert_id;

            $stmt->close();


            // --------------------------------------------------
            // Insert multiple-choice options
            // --------------------------------------------------

            if ($question_type === "multiple_choice") {

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
                        throw new Exception("Error saving question options.");
                    }

                    $option_order++;
                }

                $option_stmt->close();
            }


            // Commit everything
            $conn->commit();

            $success = "Question created successfully.";

        } catch (Exception $e) {

            $conn->rollback();

            $error = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Create Question</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

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
            ><?php echo htmlspecialchars($_POST["question_text"] ?? ""); ?></textarea>

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

                <option value="">
                    -- Select Type --
                </option>

                <option
                    value="text"
                    <?php echo (($_POST["question_type"] ?? "") === "text") ? "selected" : ""; ?>
                >
                    Text
                </option>

                <option
                    value="rating"
                    <?php echo (($_POST["question_type"] ?? "") === "rating") ? "selected" : ""; ?>
                >
                    Rating
                </option>

                <option
                    value="multiple_choice"
                    <?php echo (($_POST["question_type"] ?? "") === "multiple_choice") ? "selected" : ""; ?>
                >
                    Multiple Choice
                </option>

                <option
                    value="yes_no"
                    <?php echo (($_POST["question_type"] ?? "") === "yes_no") ? "selected" : ""; ?>
                >
                    Yes / No
                </option>

            </select>

        </div>

        <br>


        <!-- Multiple-choice options -->

        <div
            id="optionsSection"
            style="display: none;"
        >

            <label>
                Answer Options:
            </label>

            <p>
                Add at least 2 options.
            </p>


            <div id="optionsContainer">

                <?php
                $submitted_options = $_POST["options"] ?? [];

                if (
                    is_array($submitted_options) &&
                    count($submitted_options) > 0
                ):

                    foreach ($submitted_options as $option):
                ?>

                    <div class="option-row">

                        <input
                            type="text"
                            name="options[]"
                            value="<?php echo htmlspecialchars($option); ?>"
                            maxlength="255"
                            placeholder="Enter an option"
                        >

                    </div>

                <?php
                    endforeach;

                else:
                ?>

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
                    <?php echo isset($_POST["is_required"]) || $_SERVER["REQUEST_METHOD"] !== "POST" ? "checked" : ""; ?>
                >

                Required question

            </label>

        </div>

        <br>


        <button type="submit">
            Create Question
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


document.addEventListener(
    "DOMContentLoaded",
    function () {
        toggleOptions();
    }
);

</script>

</body>

</html>
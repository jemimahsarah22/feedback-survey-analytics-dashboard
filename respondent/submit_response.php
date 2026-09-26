<?php
require_once "../config/database.php";
require_once "../includes/csrf.php";

$error = "";


// --------------------------------------------------
// Check request method
// --------------------------------------------------

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    die("Method Not Allowed.");
}

verify_csrf_token();


// --------------------------------------------------
// Get survey ID
// --------------------------------------------------

if (!isset($_POST["survey_id"]) || !is_numeric($_POST["survey_id"])) {
    die("Invalid survey ID.");
}

$survey_id = (int) $_POST["survey_id"];


// --------------------------------------------------
// Check survey
// --------------------------------------------------

$stmt = $conn->prepare(
    "SELECT id, title
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
        is_required
     FROM questions
     WHERE survey_id = ?
     ORDER BY question_order ASC, id ASC"
);

$stmt->bind_param("i", $survey_id);
$stmt->execute();

$result = $stmt->get_result();

$questions = [];

while ($row = $result->fetch_assoc()) {
    $questions[$row["id"]] = $row;
}

$stmt->close();


// --------------------------------------------------
// Get submitted answers
// --------------------------------------------------

$answers = $_POST["answers"] ?? [];

if (!is_array($answers)) {
    die("Invalid answer data.");
}


// --------------------------------------------------
// Validate answers
// --------------------------------------------------

foreach ($questions as $question_id => $question) {

    $answer = $answers[$question_id] ?? "";

    if (is_array($answer)) {
        $answer = "";
    }

    $answer = trim((string) $answer);


    // Required question
    if ($question["is_required"] && $answer === "") {

        $error = "Please answer all required questions.";

        break;
    }


    // Optional unanswered question
    if ($answer === "") {
        continue;
    }


    // Rating validation
    if ($question["question_type"] === "rating") {

        if (
            !in_array(
                $answer,
                ["1", "2", "3", "4", "5"],
                true
            )
        ) {

            $error = "Invalid rating answer.";

            break;
        }
    }


    // Yes/No validation
    elseif ($question["question_type"] === "yes_no") {

        if (
            $answer !== "Yes" &&
            $answer !== "No"
        ) {

            $error = "Invalid Yes/No answer.";

            break;
        }
    }


    // Text validation
    elseif ($question["question_type"] === "text") {

        if (strlen($answer) > 5000) {

            $error = "A text answer is too long.";

            break;
        }
    }


    // Multiple choice
    elseif ($question["question_type"] === "multiple_choice") {

        if (!ctype_digit($answer)) {
            $error = "Invalid multiple-choice answer.";
            break;
        }

        $option_stmt = $conn->prepare(
            "SELECT id FROM question_options WHERE id = ? AND question_id = ?"
        );
        $option_id = (int)$answer;
        $option_stmt->bind_param("ii", $option_id, $question_id);
        $option_stmt->execute();
        $option_result = $option_stmt->get_result();
        $valid_option = $option_result->num_rows === 1;
        $option_stmt->close();

        if (!$valid_option) {
            $error = "Invalid multiple-choice answer.";
            break;
        }

    }


    // Invalid type
    else {

        $error = "Invalid question type.";

        break;
    }
}


// --------------------------------------------------
// Stop if validation failed
// --------------------------------------------------

if (!empty($error)) {

    echo "<h1>Response Error</h1>";

    echo "<p style='color: red;'>";

    echo htmlspecialchars($error);

    echo "</p>";

    echo "<p>";

    echo "<a href='survey.php?id=" .
         $survey_id .
         "'>Return to Survey</a>";

    echo "</p>";

    exit;
}


// --------------------------------------------------
// Start database transaction
// --------------------------------------------------

$conn->begin_transaction();

try {

    // --------------------------------------------------
    // Insert response
    // --------------------------------------------------

    $stmt = $conn->prepare(
        "INSERT INTO responses (survey_id)
         VALUES (?)"
    );

    $stmt->bind_param(
        "i",
        $survey_id
    );

    if (!$stmt->execute()) {
        throw new Exception(
            "Could not create response."
        );
    }

    $response_id = $conn->insert_id;

    $stmt->close();


    // --------------------------------------------------
    // Insert answers
    // --------------------------------------------------

    $answer_stmt = $conn->prepare(
        "INSERT INTO answers
         (response_id, question_id, answer_text)
         VALUES (?, ?, ?)"
    );


    foreach ($questions as $question_id => $question) {

        $answer = $answers[$question_id] ?? "";

        if (is_array($answer)) {
            $answer = "";
        }

        $answer = trim((string) $answer);


        // Skip optional unanswered questions
        if ($answer === "") {
            continue;
        }


        $answer_stmt->bind_param(
            "iis",
            $response_id,
            $question_id,
            $answer
        );

        if (!$answer_stmt->execute()) {

            throw new Exception(
                "Could not save an answer."
            );
        }
    }


    $answer_stmt->close();


    // --------------------------------------------------
    // Commit transaction
    // --------------------------------------------------

    $conn->commit();


    // --------------------------------------------------
    // Redirect to thank-you page
    // --------------------------------------------------

    header(
        "Location: thank_you.php?survey_id=" . $survey_id
    );

    exit;


} catch (Exception $e) {

    // --------------------------------------------------
    // Roll back if anything fails
    // --------------------------------------------------

    $conn->rollback();

    die(
        "An error occurred while saving your response. "
        . "No data was saved."
    );
}
?>
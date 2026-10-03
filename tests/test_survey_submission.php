<?php

require_once "../config/database.php";

echo "<h1>Survey Submission Test</h1>";


// --------------------------------------------------
// Find an active survey
// --------------------------------------------------

$stmt = $conn->prepare(
    "SELECT s.id
    FROM surveys s
    INNER JOIN questions q
        ON q.survey_id = s.id
    WHERE s.status = 'active'
    LIMIT 1"
);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("FAIL: No active survey was found.");
}

$survey = $result->fetch_assoc();

$survey_id = (int)$survey["id"];

$stmt->close();


// --------------------------------------------------
// Find a question
// --------------------------------------------------

$stmt = $conn->prepare(
    "SELECT id
     FROM questions
     WHERE survey_id = ?
     ORDER BY question_order ASC, id ASC
     LIMIT 1"
);

$stmt->bind_param("i", $survey_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("FAIL: No question was found for the survey.");
}

$question = $result->fetch_assoc();

$question_id = (int)$question["id"];

$stmt->close();


// --------------------------------------------------
// Start test transaction
// --------------------------------------------------

$conn->begin_transaction();

try {

    // Create test response
    $stmt = $conn->prepare(
        "INSERT INTO responses (survey_id)
         VALUES (?)"
    );

    $stmt->bind_param("i", $survey_id);

    if (!$stmt->execute()) {
        throw new Exception("Could not create test response.");
    }

    $response_id = $conn->insert_id;

    $stmt->close();


    // Create test answer
    $test_answer = "TEST ANSWER";

    $stmt = $conn->prepare(
        "INSERT INTO answers
         (response_id, question_id, answer_text)
         VALUES (?, ?, ?)"
    );

    $stmt->bind_param(
        "iis",
        $response_id,
        $question_id,
        $test_answer
    );

    if (!$stmt->execute()) {
        throw new Exception("Could not create test answer.");
    }

    $stmt->close();


    // Check that the response exists
    $stmt = $conn->prepare(
        "SELECT id
         FROM responses
         WHERE id = ?"
    );

    $stmt->bind_param("i", $response_id);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows !== 1) {
        throw new Exception(
            "Response was not found after insertion."
        );
    }

    $stmt->close();


    // Check that the answer exists
    $stmt = $conn->prepare(
        "SELECT id
         FROM answers
         WHERE response_id = ?
         AND question_id = ?"
    );

    $stmt->bind_param(
        "ii",
        $response_id,
        $question_id
    );

    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows !== 1) {
        throw new Exception(
            "Answer was not found after insertion."
        );
    }

    $stmt->close();


    // Everything worked
    $conn->rollback();

    echo "<p style='color: green;'>";
    echo "<strong>PASS:</strong> A survey response and answer ";
    echo "were successfully created and verified.";
    echo "</p>";

} catch (Exception $e) {

    $conn->rollback();

    echo "<p style='color: red;'>";
    echo "<strong>FAIL:</strong> ";
    echo htmlspecialchars($e->getMessage());
    echo "</p>";
}

$conn->close();

?>
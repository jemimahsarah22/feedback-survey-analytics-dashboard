<?php
require_once "../includes/auth.php";
require_once "../config/database.php";
require_once "../includes/csrf.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    die("Method Not Allowed.");
}

verify_csrf_token();

if (!isset($_POST["id"]) || !is_numeric($_POST["id"])) {
    die("Invalid question ID.");
}

$question_id = (int) $_POST["id"];

$stmt = $conn->prepare("SELECT survey_id FROM questions WHERE id = ?");
$stmt->bind_param("i", $question_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $stmt->close();
    die("Question not found.");
}

$survey_id = (int)$result->fetch_assoc()["survey_id"];
$stmt->close();

$stmt = $conn->prepare("DELETE FROM questions WHERE id = ?");
$stmt->bind_param("i", $question_id);

if ($stmt->execute()) {
    $stmt->close();
    header("Location: questions.php?survey_id=" . $survey_id);
    exit;
}

$error = $stmt->error;
$stmt->close();
die("Error deleting question: " . htmlspecialchars($error));
?>
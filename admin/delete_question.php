<?php
require_once "../includes/auth.php";
require_once "../config/database.php";


// Check question ID
if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    die("Invalid question ID.");
}

$question_id = (int) $_GET["id"];


// Delete question
$stmt = $conn->prepare(
    "DELETE FROM questions
     WHERE id = ?"
);

$stmt->bind_param("i", $question_id);

if ($stmt->execute()) {

    $stmt->close();

    header("Location: questions.php");
    exit;

} else {

    $error = $stmt->error;

    $stmt->close();

    die("Error deleting question: " . $error);
}
?>
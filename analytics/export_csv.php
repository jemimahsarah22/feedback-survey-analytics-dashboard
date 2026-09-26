<?php
require_once "../config/database.php";

$survey_id = isset($_GET["survey_id"]) ? (int)$_GET["survey_id"] : 0;

if ($survey_id <= 0) {
    die("Invalid survey.");
}

$stmt = $conn->prepare("
    SELECT title
    FROM surveys
    WHERE id = ?
");

$stmt->bind_param("i", $survey_id);
$stmt->execute();

$result = $stmt->get_result();
$survey = $result->fetch_assoc();

$stmt->close();

if (!$survey) {
    die("Survey not found.");
}

header("Content-Type: text/csv");
header(
    "Content-Disposition: attachment; filename=\"survey_"
    . $survey_id
    . "_analytics.csv\""
);

$output = fopen("php://output", "w");

fputcsv($output, [
    "Survey",
    "Question",
    "Question Type",
    "Answer",
    "Submitted At"
]);

$stmt = $conn->prepare("
    SELECT
        s.title,
        q.question_text,
        q.question_type,
        a.answer_text,
        r.submitted_at
    FROM answers a
    JOIN responses r
        ON a.response_id = r.id
    JOIN surveys s
        ON r.survey_id = s.id
    JOIN questions q
        ON a.question_id = q.id
    WHERE r.survey_id = ?
    ORDER BY r.submitted_at DESC
");

$stmt->bind_param("i", $survey_id);
$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {

    fputcsv($output, [
        $row["title"],
        $row["question_text"],
        $row["question_type"],
        $row["answer_text"],
        $row["submitted_at"]
    ]);
}

$stmt->close();
fclose($output);
exit;
?>
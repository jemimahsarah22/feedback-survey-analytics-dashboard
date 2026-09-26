<?php
require_once "../includes/auth.php";
require_once "../config/database.php";

$survey_id = isset($_GET["survey_id"]) ? (int)$_GET["survey_id"] : 0;
$start_date = $_GET["start_date"] ?? "";
$end_date = $_GET["end_date"] ?? "";

if ($start_date !== "" && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date)) {
    $start_date = "";
}
if ($end_date !== "" && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
    $end_date = "";
}
if ($start_date !== "" && $end_date !== "" && $start_date > $end_date) {
    [$start_date, $end_date] = [$end_date, $start_date];
}

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

function csv_safe($value) {
    $value = (string)$value;
    if ($value !== "" && in_array($value[0], ["=", "+", "-", "@"], true)) {
        return "'" . $value;
    }
    return $value;
}

fputcsv($output, [
    "Survey",
    "Question",
    "Question Type",
    "Answer",
    "Submitted At"
]);

$sql = "
    SELECT
        s.title,
        q.question_text,
        q.question_type,
        a.answer_text,
        r.submitted_at
    FROM answers a
    JOIN responses r ON a.response_id = r.id
    JOIN surveys s ON r.survey_id = s.id
    JOIN questions q ON a.question_id = q.id
    WHERE r.survey_id = ?
";

$params = [$survey_id];
$types = "i";

if ($start_date !== "" && $end_date !== "") {
    $sql .= " AND DATE(r.submitted_at) BETWEEN ? AND ?";
    $params[] = $start_date;
    $params[] = $end_date;
    $types .= "ss";
}

$sql .= " ORDER BY r.submitted_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {

    fputcsv($output, [
        csv_safe($row["title"]),
        csv_safe($row["question_text"]),
        csv_safe($row["question_type"]),
        csv_safe($row["answer_text"]),
        csv_safe($row["submitted_at"])
    ]);
}

$stmt->close();
fclose($output);
exit;
?>
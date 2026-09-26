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
    die("Invalid survey ID.");
}

$survey_id = (int) $_POST["id"];

$stmt = $conn->prepare("SELECT status FROM surveys WHERE id = ?");
$stmt->bind_param("i", $survey_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $stmt->close();
    die("Survey not found.");
}

$survey = $result->fetch_assoc();
$stmt->close();
$new_status = ($survey["status"] === "active") ? "inactive" : "active";

$stmt = $conn->prepare("UPDATE surveys SET status = ? WHERE id = ?");
$stmt->bind_param("si", $new_status, $survey_id);

if ($stmt->execute()) {
    $stmt->close();
    header("Location: surveys.php");
    exit;
}

$error = $stmt->error;
$stmt->close();
die("Error updating survey status: " . htmlspecialchars($error));
?>
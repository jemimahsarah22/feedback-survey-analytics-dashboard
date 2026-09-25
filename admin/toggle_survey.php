<?php
require_once "../includes/auth.php";
require_once "../config/database.php";


// Check survey ID
if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    die("Invalid survey ID.");
}

$survey_id = (int) $_GET["id"];


// Get current status
$stmt = $conn->prepare(
    "SELECT status
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


// Determine new status
if ($survey["status"] === "active") {
    $new_status = "inactive";
} else {
    $new_status = "active";
}


// Update status
$stmt = $conn->prepare(
    "UPDATE surveys
     SET status = ?
     WHERE id = ?"
);

$stmt->bind_param(
    "si",
    $new_status,
    $survey_id
);

if ($stmt->execute()) {

    $stmt->close();

    header("Location: surveys.php");
    exit;

} else {

    $error = $stmt->error;

    $stmt->close();

    die("Error updating survey status: " . $error);
}
?>
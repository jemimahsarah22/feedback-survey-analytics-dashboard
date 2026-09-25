<?php
require_once "../includes/auth.php";
require_once "../config/database.php";


// Get survey ID
if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    die("Invalid survey ID.");
}

$survey_id = (int) $_GET["id"];


// Delete the survey
$stmt = $conn->prepare(
    "DELETE FROM surveys
     WHERE id = ?"
);

$stmt->bind_param("i", $survey_id);

if ($stmt->execute()) {

    $stmt->close();

    header("Location: surveys.php");
    exit;

} else {

    $error = "Error deleting survey: " . $stmt->error;

    $stmt->close();

    die($error);
}
?>
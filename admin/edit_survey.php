<?php
require_once "../includes/auth.php";
require_once "../config/database.php";

$error = "";
$success = "";


// --------------------------------------------------
// Get survey ID from URL
// --------------------------------------------------

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    die("Invalid survey ID.");
}

$survey_id = (int) $_GET["id"];


// --------------------------------------------------
// Handle form submission
// --------------------------------------------------

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $title = trim($_POST["title"]);
    $description = trim($_POST["description"]);
    $status = $_POST["status"];

    // Validate title
    if (empty($title)) {

        $error = "Survey title is required.";

    } elseif (strlen($title) > 200) {

        $error = "Survey title must not exceed 200 characters.";

    } elseif (!in_array($status, ["active", "inactive"])) {

        $error = "Invalid survey status.";

    } else {

        $stmt = $conn->prepare(
            "UPDATE surveys
             SET title = ?, description = ?, status = ?
             WHERE id = ?"
        );

        $stmt->bind_param(
            "sssi",
            $title,
            $description,
            $status,
            $survey_id
        );

        if ($stmt->execute()) {

            $success = "Survey updated successfully.";

        } else {

            $error = "Error updating survey: " . $stmt->error;
        }

        $stmt->close();
    }
}


// --------------------------------------------------
// Retrieve the survey
// --------------------------------------------------

$stmt = $conn->prepare(
    "SELECT id, title, description, status
     FROM surveys
     WHERE id = ?"
);

$stmt->bind_param("i", $survey_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    die("Survey not found.");
}

$survey = $result->fetch_assoc();

$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Edit Survey</title>

</head>

<body>

    <h1>Edit Survey</h1>

    <p>
        <a href="surveys.php">← Back to Surveys</a>
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

        <div>

            <label for="title">
                Survey Title:
            </label>

            <br>

            <input
                type="text"
                id="title"
                name="title"
                maxlength="200"
                value="<?php echo htmlspecialchars($survey["title"]); ?>"
                required
            >

        </div>

        <br>


        <div>

            <label for="description">
                Survey Description:
            </label>

            <br>

            <textarea
                id="description"
                name="description"
                rows="6"
                cols="50"
            ><?php echo htmlspecialchars($survey["description"] ?? ""); ?></textarea>

        </div>

        <br>


        <div>

            <label for="status">
                Status:
            </label>

            <br>

            <select id="status" name="status">

                <option
                    value="active"
                    <?php echo ($survey["status"] === "active") ? "selected" : ""; ?>
                >
                    Active
                </option>

                <option
                    value="inactive"
                    <?php echo ($survey["status"] === "inactive") ? "selected" : ""; ?>
                >
                    Inactive
                </option>

            </select>

        </div>

        <br>


        <button type="submit">
            Update Survey
        </button>

    </form>

</body>

</html>
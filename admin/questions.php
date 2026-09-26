<?php
require_once "../includes/auth.php";
require_once "../config/database.php";
require_once "../includes/csrf.php";


// Get all surveys
$survey_sql = "SELECT id, title
               FROM surveys
               ORDER BY created_at DESC";

$survey_result = $conn->query($survey_sql);

if (!$survey_result) {
    die("Error retrieving surveys: " . $conn->error);
}


// Check if a survey has been selected
$selected_survey_id = null;

if (isset($_GET["survey_id"]) && is_numeric($_GET["survey_id"])) {
    $selected_survey_id = (int) $_GET["survey_id"];
}


// Get questions
$questions = [];

if ($selected_survey_id !== null) {

    $stmt = $conn->prepare(
        "SELECT id,
                survey_id,
                question_text,
                question_type,
                is_required,
                question_order
         FROM questions
         WHERE survey_id = ?
         ORDER BY question_order ASC, id ASC"
    );

    $stmt->bind_param("i", $selected_survey_id);

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $questions[] = $row;
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Manage Questions</title>

    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

    <h1>Manage Questions</h1>

    <p>
        <a href="dashboard.php">← Back to Dashboard</a>
    </p>

    <hr>


    <h2>Select a Survey</h2>

    <form method="GET">

        <label for="survey_id">
            Survey:
        </label>

        <select
            id="survey_id"
            name="survey_id"
            onchange="this.form.submit()"
        >

            <option value="">
                -- Select a Survey --
            </option>

            <?php while ($survey = $survey_result->fetch_assoc()): ?>

                <option
                    value="<?php echo $survey["id"]; ?>"
                    <?php
                    echo ($selected_survey_id === (int) $survey["id"])
                        ? "selected"
                        : "";
                    ?>
                >
                    <?php echo htmlspecialchars($survey["title"]); ?>
                </option>

            <?php endwhile; ?>

        </select>

    </form>


    <?php if ($selected_survey_id !== null): ?>

        <hr>

        <h2>Questions</h2>

        <p>
            <a href="create_question.php?survey_id=<?php echo $selected_survey_id; ?>">
                Add New Question
            </a>
        </p>


        <?php if (count($questions) > 0): ?>

            <table border="1"
                   cellpadding="10"
                   cellspacing="0">

                <thead>

                    <tr>
                        <th>Order</th>
                        <th>Question</th>
                        <th>Type</th>
                        <th>Required</th>
                        <th>Actions</th>
                    </tr>

                </thead>

                <tbody>

                    <?php foreach ($questions as $question): ?>

                        <tr>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $question["question_order"]
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $question["question_text"]
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $question["question_type"]
                                );
                                ?>
                            </td>

                            <td>

                                <?php
                                echo $question["is_required"]
                                    ? "Yes"
                                    : "No";
                                ?>

                            </td>

                            <td>

                                <a href="edit_question.php?id=<?php echo $question["id"]; ?>">
                                    Edit
                                </a>

                                |

                                <form method="POST" action="delete_question.php" onsubmit="return confirm('Are you sure you want to delete this question?');" style="display:inline;">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="id" value="<?php echo (int)$question["id"]; ?>">
                                    <button type="submit" class="danger">Delete</button>
                                </form>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        <?php else: ?>

            <p>
                No questions have been created for this survey yet.
            </p>

        <?php endif; ?>

    <?php endif; ?>

</body>

</html>
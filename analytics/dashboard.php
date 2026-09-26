<?php
require_once "../config/database.php";

$survey_id = isset($_GET["survey_id"]) ? (int)$_GET["survey_id"] : 0;

$surveys = $conn->query("
    SELECT id, title
    FROM surveys
    ORDER BY id
");

if (!$surveys) {
    die("Failed to load surveys: " . $conn->error);
}

$selected_survey = null;
if ($survey_id > 0) {
    $stmt = $conn->prepare("SELECT id, title FROM surveys WHERE id = ?");
    $stmt->bind_param("i", $survey_id);
    $stmt->execute();
    $selected_survey = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if (!$selected_survey) {
    $first = $surveys->fetch_assoc();

    if ($first) {
        $survey_id = (int)$first["id"];
        $selected_survey = $first;
    }
}

/* Total responses */
$total_responses = 0;

if ($survey_id > 0) {
    $stmt = $conn->prepare("
        SELECT COUNT(*)
        FROM responses
        WHERE survey_id = ?
    ");
    $stmt->bind_param("i", $survey_id);
    $stmt->execute();
    $stmt->bind_result($total_responses);
    $stmt->fetch();
    $stmt->close();
}

/* Average rating */
$average_rating = 0;

if ($survey_id > 0) {
    $stmt = $conn->prepare("
        SELECT ROUND(
            AVG(CAST(a.answer_text AS DECIMAL(10,2))),
            2
        )
        FROM answers a
        JOIN responses r ON a.response_id = r.id
        JOIN questions q ON a.question_id = q.id
        WHERE r.survey_id = ?
          AND q.question_type = 'rating'
    ");

    $stmt->bind_param("i", $survey_id);
    $stmt->execute();
    $stmt->bind_result($average_result);
    $stmt->fetch();
    $stmt->close();

    if ($average_result !== null) {
        $average_rating = $average_result;
    }
}

/* Rating distribution */
$rating_distribution = [
    1 => 0,
    2 => 0,
    3 => 0,
    4 => 0,
    5 => 0
];

if ($survey_id > 0) {
    $stmt = $conn->prepare("
        SELECT
            CAST(a.answer_text AS UNSIGNED) AS rating,
            COUNT(*) AS response_count
        FROM answers a
        JOIN responses r ON a.response_id = r.id
        JOIN questions q ON a.question_id = q.id
        WHERE r.survey_id = ?
          AND q.question_type = 'rating'
        GROUP BY CAST(a.answer_text AS UNSIGNED)
    ");

    $stmt->bind_param("i", $survey_id);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $rating = (int)$row["rating"];

        if (isset($rating_distribution[$rating])) {
            $rating_distribution[$rating] = (int)$row["response_count"];
        }
    }

    $stmt->close();
}

/* Yes/No distribution */
$yes_no = [
    "Yes" => 0,
    "No" => 0
];

if ($survey_id > 0) {
    $stmt = $conn->prepare("
        SELECT
            a.answer_text,
            COUNT(*) AS response_count
        FROM answers a
        JOIN responses r ON a.response_id = r.id
        JOIN questions q ON a.question_id = q.id
        WHERE r.survey_id = ?
          AND q.question_type = 'yes_no'
        GROUP BY a.answer_text
    ");

    $stmt->bind_param("i", $survey_id);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $answer = $row["answer_text"];

        if (isset($yes_no[$answer])) {
            $yes_no[$answer] = (int)$row["response_count"];
        }
    }

    $stmt->close();
}

/* Text feedback */
$text_feedback = [];

if ($survey_id > 0) {
    $stmt = $conn->prepare("
        SELECT
            q.question_text,
            a.answer_text,
            r.submitted_at
        FROM answers a
        JOIN responses r ON a.response_id = r.id
        JOIN questions q ON a.question_id = q.id
        WHERE r.survey_id = ?
          AND q.question_type = 'text'
          AND a.answer_text IS NOT NULL
          AND TRIM(a.answer_text) <> ''
        ORDER BY r.submitted_at DESC
    ");

    $stmt->bind_param("i", $survey_id);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $text_feedback[] = $row;
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Analytics Dashboard</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
        }

        .container {
            width: 90%;
            max-width: 1100px;
            margin: 30px auto;
        }

        h1 {
            color: #170d64;
        }

        .selector {
            margin: 20px 0;
        }

        select {
            padding: 10px;
            min-width: 300px;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin: 20px 0;
        }

        .card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,.1);
        }

        .card h3 {
            margin-top: 0;
            color: #555;
        }

        .value {
            font-size: 30px;
            font-weight: bold;
            color: #170d64;
        }

        .section {
            background: white;
            padding: 20px;
            margin-top: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        th {
            background: #170d64;
            color: white;
        }

        .feedback {
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }

        @media (max-width: 700px) {
            .cards {
                grid-template-columns: 1fr;
            }

            select {
                width: 100%;
                min-width: 0;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <h1>Feedback & Survey Analytics</h1>

    <?php if ($selected_survey): ?>

    <p>
        <a
            href="export_csv.php?survey_id=<?php echo $survey_id; ?>"
            style="
                display: inline-block;
                padding: 10px 16px;
                background: #170d64;
                color: white;
                text-decoration: none;
                border-radius: 6px;
            "
        >
            Export CSV
        </a>
    </p>

    <?php endif; ?>

    <?php if ($selected_survey): ?>

        <div class="selector">
            <form method="GET">

                <label for="survey_id">
                    <strong>Select Survey:</strong>
                </label>

                <select
                    name="survey_id"
                    id="survey_id"
                    onchange="this.form.submit()"
                >

                    <?php
                    $surveys->data_seek(0);

                    while ($survey = $surveys->fetch_assoc()):
                    ?>

                        <option
                            value="<?php echo $survey["id"]; ?>"
                            <?php
                            echo $survey["id"] == $survey_id
                                ? "selected"
                                : "";
                            ?>
                        >
                            <?php echo htmlspecialchars($survey["title"]); ?>
                        </option>

                    <?php endwhile; ?>

                </select>

            </form>
        </div>

        <div class="cards">

            <div class="card">
                <h3>Total Responses</h3>

                <div class="value">
                    <?php echo $total_responses; ?>
                </div>
            </div>

            <div class="card">
                <h3>Average Rating</h3>

                <div class="value">
                    <?php echo number_format((float)$average_rating, 2); ?>
                    / 5
                </div>
            </div>

            <div class="card">
                <h3>Yes Responses</h3>

                <div class="value">
                    <?php echo $yes_no["Yes"]; ?>
                </div>
            </div>

        </div>

        <div class="section">

            <h2>Rating Distribution</h2>

            <table>

                <tr>
                    <th>Rating</th>
                    <th>Responses</th>
                </tr>

                <?php foreach ($rating_distribution as $rating => $count): ?>

                    <tr>
                        <td><?php echo $rating; ?> / 5</td>
                        <td><?php echo $count; ?></td>
                    </tr>

                <?php endforeach; ?>

            </table>

        </div>

        <div class="section">

            <h2>Yes / No Distribution</h2>

            <table>

                <tr>
                    <th>Answer</th>
                    <th>Responses</th>
                </tr>

                <tr>
                    <td>Yes</td>
                    <td><?php echo $yes_no["Yes"]; ?></td>
                </tr>

                <tr>
                    <td>No</td>
                    <td><?php echo $yes_no["No"]; ?></td>
                </tr>

            </table>

        </div>

        <div class="section">

            <h2>Written Feedback</h2>

            <?php if (count($text_feedback) > 0): ?>

                <?php foreach ($text_feedback as $feedback): ?>

                    <div class="feedback">

                        <strong>
                            <?php echo htmlspecialchars($feedback["question_text"]); ?>
                        </strong>

                        <p>
                            <?php echo htmlspecialchars($feedback["answer_text"]); ?>
                        </p>

                        <small>
                            <?php echo htmlspecialchars($feedback["submitted_at"]); ?>
                        </small>

                    </div>

                <?php endforeach; ?>

            <?php else: ?>

                <p>No written feedback available.</p>

            <?php endif; ?>

        </div>

    <?php else: ?>

        <p>No surveys available.</p>

    <?php endif; ?>

</div>

</body>
</html>
<?php
require_once "../config/database.php";

$survey_id = isset($_GET["survey_id"]) ? (int)$_GET["survey_id"] : 0;
$start_date = isset($_GET["start_date"]) ? $_GET["start_date"] : "";
$end_date = isset($_GET["end_date"]) ? $_GET["end_date"] : "";

/* Get surveys */
$surveys = $conn->query("
    SELECT id, title
    FROM surveys
    ORDER BY id
");

if (!$surveys) {
    die("Failed to load surveys: " . $conn->error);
}

/* Select survey */
$selected_survey = null;

if ($survey_id > 0) {
    $stmt = $conn->prepare("
        SELECT id, title
        FROM surveys
        WHERE id = ?
    ");

    $stmt->bind_param("i", $survey_id);
    $stmt->execute();

    $selected_survey = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

/* Select first survey automatically */
if (!$selected_survey) {
    $first = $surveys->fetch_assoc();

    if ($first) {
        $survey_id = (int)$first["id"];
        $selected_survey = $first;
    }
}


/* =====================================================
   TOTAL RESPONSES
   ===================================================== */

$total_responses = 0;

if ($survey_id > 0) {

    $sql = "
        SELECT COUNT(*)
        FROM responses
        WHERE survey_id = ?
    ";

    $params = [$survey_id];
    $types = "i";

    if ($start_date !== "" && $end_date !== "") {
        $sql .= "
            AND DATE(submitted_at)
            BETWEEN ? AND ?
        ";

        $params[] = $start_date;
        $params[] = $end_date;
        $types .= "ss";
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $stmt->bind_result($total_responses);
    $stmt->fetch();
    $stmt->close();
}


/* =====================================================
   AVERAGE RATING
   ===================================================== */

$average_rating = 0;

if ($survey_id > 0) {

    $sql = "
        SELECT ROUND(
            AVG(CAST(a.answer_text AS DECIMAL(10,2))),
            2
        )
        FROM answers a
        JOIN responses r
            ON a.response_id = r.id
        JOIN questions q
            ON a.question_id = q.id
        WHERE r.survey_id = ?
          AND q.question_type = 'rating'
    ";

    $params = [$survey_id];
    $types = "i";

    if ($start_date !== "" && $end_date !== "") {
        $sql .= "
            AND DATE(r.submitted_at)
            BETWEEN ? AND ?
        ";

        $params[] = $start_date;
        $params[] = $end_date;
        $types .= "ss";
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $stmt->bind_result($average_result);
    $stmt->fetch();
    $stmt->close();

    if ($average_result !== null) {
        $average_rating = $average_result;
    }
}


/* =====================================================
   RATING DISTRIBUTION
   ===================================================== */

$rating_distribution = [
    1 => 0,
    2 => 0,
    3 => 0,
    4 => 0,
    5 => 0
];

if ($survey_id > 0) {

    $sql = "
        SELECT
            CAST(a.answer_text AS UNSIGNED) AS rating,
            COUNT(*) AS response_count
        FROM answers a
        JOIN responses r
            ON a.response_id = r.id
        JOIN questions q
            ON a.question_id = q.id
        WHERE r.survey_id = ?
          AND q.question_type = 'rating'
    ";

    $params = [$survey_id];
    $types = "i";

    if ($start_date !== "" && $end_date !== "") {
        $sql .= "
            AND DATE(r.submitted_at)
            BETWEEN ? AND ?
        ";

        $params[] = $start_date;
        $params[] = $end_date;
        $types .= "ss";
    }

    $sql .= "
        GROUP BY CAST(a.answer_text AS UNSIGNED)
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {

        $rating = (int)$row["rating"];

        if (isset($rating_distribution[$rating])) {
            $rating_distribution[$rating] =
                (int)$row["response_count"];
        }
    }

    $stmt->close();
}


/* =====================================================
   YES / NO DISTRIBUTION
   ===================================================== */

$yes_no = [
    "Yes" => 0,
    "No" => 0
];

if ($survey_id > 0) {

    $sql = "
        SELECT
            a.answer_text,
            COUNT(*) AS response_count
        FROM answers a
        JOIN responses r
            ON a.response_id = r.id
        JOIN questions q
            ON a.question_id = q.id
        WHERE r.survey_id = ?
          AND q.question_type = 'yes_no'
    ";

    $params = [$survey_id];
    $types = "i";

    if ($start_date !== "" && $end_date !== "") {
        $sql .= "
            AND DATE(r.submitted_at)
            BETWEEN ? AND ?
        ";

        $params[] = $start_date;
        $params[] = $end_date;
        $types .= "ss";
    }

    $sql .= " GROUP BY a.answer_text";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {

        $answer = $row["answer_text"];

        if (isset($yes_no[$answer])) {
            $yes_no[$answer] =
                (int)$row["response_count"];
        }
    }

    $stmt->close();
}


/* =====================================================
   TEXT FEEDBACK
   ===================================================== */

$text_feedback = [];

if ($survey_id > 0) {

    $sql = "
        SELECT
            q.question_text,
            a.answer_text,
            r.submitted_at
        FROM answers a
        JOIN responses r
            ON a.response_id = r.id
        JOIN questions q
            ON a.question_id = q.id
        WHERE r.survey_id = ?
          AND q.question_type = 'text'
          AND a.answer_text IS NOT NULL
          AND TRIM(a.answer_text) <> ''
    ";

    $params = [$survey_id];
    $types = "i";

    if ($start_date !== "" && $end_date !== "") {
        $sql .= "
            AND DATE(r.submitted_at)
            BETWEEN ? AND ?
        ";

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
        $text_feedback[] = $row;
    }

    $stmt->close();
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Analytics Dashboard</title>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
            color: #222;
        }

        /* NAVBAR */

        .navbar {
            background: #170d64;
            color: white;
            padding: 15px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .brand {
            font-size: 22px;
            font-weight: bold;
        }

        .nav-links {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 5px;
        }

        .nav-links a:hover {
            background: rgba(255,255,255,0.15);
        }

        /* MAIN */

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 30px auto;
        }

        .page-header {
            margin-bottom: 20px;
        }

        .page-header h1 {
            color: #170d64;
            margin-bottom: 5px;
        }

        .page-header p {
            color: #666;
            margin-top: 0;
        }

        /* FILTER */

        .filter-box {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,.08);
            margin-bottom: 20px;
        }

        .filter-form {
            display: flex;
            align-items: end;
            gap: 12px;
            flex-wrap: wrap;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .form-group label {
            font-weight: bold;
            font-size: 14px;
        }

        select,
        input[type="date"] {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        button,
        .export-button {
            padding: 10px 16px;
            background: #170d64;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
        }

        button:hover,
        .export-button:hover {
            opacity: .9;
        }

        /* CARDS */

        .cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }

        .card {
            background: white;
            padding: 22px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,.08);
        }

        .card h3 {
            margin-top: 0;
            color: #666;
            font-size: 16px;
        }

        .value {
            font-size: 32px;
            font-weight: bold;
            color: #170d64;
        }

        /* SECTIONS */

        .section {
            background: white;
            padding: 22px;
            margin-top: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,.08);
        }

        .section h2 {
            margin-top: 0;
            color: #170d64;
        }

        .chart-container {
            max-width: 700px;
            margin: 20px auto;
        }

        /* TABLE */

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 11px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        th {
            background: #170d64;
            color: white;
        }

        /* FEEDBACK */

        .feedback {
            padding: 15px 0;
            border-bottom: 1px solid #ddd;
        }

        .feedback:last-child {
            border-bottom: none;
        }

        .feedback p {
            line-height: 1.5;
        }

        .feedback small {
            color: #777;
        }

        /* MOBILE */

        @media (max-width: 700px) {

            .navbar {
                align-items: flex-start;
                flex-direction: column;
            }

            .nav-links {
                width: 100%;
            }

            .nav-links a {
                padding-left: 0;
            }

            .cards {
                grid-template-columns: 1fr;
            }

            .filter-form {
                flex-direction: column;
                align-items: stretch;
            }

            select,
            input[type="date"],
            button {
                width: 100%;
            }

            .container {
                width: 94%;
            }

            table {
                font-size: 14px;
            }

        }

    </style>

</head>

<body>

<!-- NAVIGATION -->

<nav class="navbar">

    <div class="brand">
        Feedback & Survey Analytics
    </div>

    <div class="nav-links">

        <a href="../admin/dashboard.php">
            Admin Dashboard
        </a>

        <a href="../admin/surveys.php">
            Surveys
        </a>

        <a href="../respondent/surveys.php">
            Public Surveys
        </a>

        <a href="../logout.php">
            Logout
        </a>

    </div>

</nav>


<div class="container">

    <div class="page-header">

        <h1>Analytics Dashboard</h1>

        <p>
            View survey responses and performance statistics.
        </p>

    </div>


    <?php if ($selected_survey): ?>


    <!-- FILTER -->

    <div class="filter-box">

        <form
            method="GET"
            class="filter-form"
        >

            <div class="form-group">

                <label for="survey_id">
                    Survey
                </label>

                <select
                    name="survey_id"
                    id="survey_id"
                >

                    <?php

                    $surveys->data_seek(0);

                    while (
                        $survey =
                        $surveys->fetch_assoc()
                    ):

                    ?>

                        <option
                            value="<?php
                                echo $survey["id"];
                            ?>"
                            <?php

                            echo $survey["id"] == $survey_id
                                ? "selected"
                                : "";

                            ?>
                        >

                            <?php

                            echo htmlspecialchars(
                                $survey["title"]
                            );

                            ?>

                        </option>

                    <?php endwhile; ?>

                </select>

            </div>


            <div class="form-group">

                <label for="start_date">
                    From
                </label>

                <input
                    type="date"
                    name="start_date"
                    id="start_date"
                    value="<?php
                        echo htmlspecialchars(
                            $start_date
                        );
                    ?>"
                >

            </div>


            <div class="form-group">

                <label for="end_date">
                    To
                </label>

                <input
                    type="date"
                    name="end_date"
                    id="end_date"
                    value="<?php
                        echo htmlspecialchars(
                            $end_date
                        );
                    ?>"
                >

            </div>


            <button type="submit">
                Apply Filter
            </button>


            <a
                class="export-button"
                href="export_csv.php?survey_id=<?php
                    echo $survey_id;
                ?>"
            >
                Export CSV
            </a>

        </form>

    </div>


    <!-- SUMMARY -->

    <div class="cards">

        <div class="card">

            <h3>
                Total Responses
            </h3>

            <div class="value">

                <?php
                echo $total_responses;
                ?>

            </div>

        </div>


        <div class="card">

            <h3>
                Average Rating
            </h3>

            <div class="value">

                <?php

                echo number_format(
                    (float)$average_rating,
                    2
                );

                ?>

                / 5

            </div>

        </div>


        <div class="card">

            <h3>
                Yes Responses
            </h3>

            <div class="value">

                <?php
                echo $yes_no["Yes"];
                ?>

            </div>

        </div>

    </div>


    <!-- RATING -->

    <div class="section">

        <h2>
            Rating Distribution
        </h2>

        <div class="chart-container">

            <canvas id="ratingChart"></canvas>

        </div>


        <table>

            <tr>

                <th>
                    Rating
                </th>

                <th>
                    Responses
                </th>

            </tr>

            <?php foreach (
                $rating_distribution
                as $rating => $count
            ): ?>

                <tr>

                    <td>
                        <?php
                        echo $rating;
                        ?> / 5
                    </td>

                    <td>
                        <?php
                        echo $count;
                        ?>
                    </td>

                </tr>

            <?php endforeach; ?>

        </table>

    </div>


    <!-- YES / NO -->

    <div class="section">

        <h2>
            Yes / No Distribution
        </h2>

        <div class="chart-container">

            <canvas id="yesNoChart"></canvas>

        </div>


        <table>

            <tr>

                <th>
                    Answer
                </th>

                <th>
                    Responses
                </th>

            </tr>

            <tr>

                <td>
                    Yes
                </td>

                <td>
                    <?php
                    echo $yes_no["Yes"];
                    ?>
                </td>

            </tr>

            <tr>

                <td>
                    No
                </td>

                <td>
                    <?php
                    echo $yes_no["No"];
                    ?>
                </td>

            </tr>

        </table>

    </div>


    <!-- TEXT FEEDBACK -->

    <div class="section">

        <h2>
            Written Feedback
        </h2>

        <?php if (
            count($text_feedback) > 0
        ): ?>

            <?php foreach (
                $text_feedback
                as $feedback
            ): ?>

                <div class="feedback">

                    <strong>

                        <?php

                        echo htmlspecialchars(
                            $feedback["question_text"]
                        );

                        ?>

                    </strong>

                    <p>

                        <?php

                        echo htmlspecialchars(
                            $feedback["answer_text"]
                        );

                        ?>

                    </p>

                    <small>

                        Submitted:
                        <?php

                        echo htmlspecialchars(
                            $feedback["submitted_at"]
                        );

                        ?>

                    </small>

                </div>

            <?php endforeach; ?>

        <?php else: ?>

            <p>
                No written feedback available.
            </p>

        <?php endif; ?>

    </div>


    <?php else: ?>

        <div class="section">

            <p>
                No surveys available.
            </p>

        </div>

    <?php endif; ?>

</div>


<!-- RATING CHART -->

<script>

const ratingChart =
    document.getElementById("ratingChart");

new Chart(ratingChart, {

    type: "bar",

    data: {

        labels: [
            "1",
            "2",
            "3",
            "4",
            "5"
        ],

        datasets: [{

            label: "Responses",

            data: [

                <?php
                echo $rating_distribution[1];
                ?>,

                <?php
                echo $rating_distribution[2];
                ?>,

                <?php
                echo $rating_distribution[3];
                ?>,

                <?php
                echo $rating_distribution[4];
                ?>,

                <?php
                echo $rating_distribution[5];
                ?>

            ]

        }]

    },

    options: {

        responsive: true,

        scales: {

            y: {

                beginAtZero: true,

                ticks: {
                    precision: 0
                }

            }

        }

    }

});

</script>


<!-- YES / NO CHART -->

<script>

const yesNoChart =
    document.getElementById("yesNoChart");

new Chart(yesNoChart, {

    type: "doughnut",

    data: {

        labels: [
            "Yes",
            "No"
        ],

        datasets: [{

            label: "Responses",

            data: [

                <?php
                echo $yes_no["Yes"];
                ?>,

                <?php
                echo $yes_no["No"];
                ?>

            ]

        }]

    },

    options: {
        responsive: true
    }

});

</script>

</body>

</html>
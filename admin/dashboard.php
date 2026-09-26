<?php
require_once "../includes/auth.php";
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Admin Dashboard</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
        }

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

        .navbar a {
            color: white;
            text-decoration: none;
            margin-left: 15px;
        }

        .navbar a:hover {
            text-decoration: underline;
        }

        .container {
            width: 90%;
            max-width: 1100px;
            margin: 40px auto;
        }

        h1 {
            color: #170d64;
        }

        .cards {
            display: grid;
            grid-template-columns:
                repeat(3, 1fr);
            gap: 20px;
            margin-top: 30px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow:
                0 2px 8px rgba(0,0,0,.1);
        }

        .card h2 {
            color: #170d64;
            margin-top: 0;
        }

        .card p {
            color: #666;
            line-height: 1.5;
        }

        .button {
            display: inline-block;
            padding: 10px 16px;
            background: #170d64;
            color: white;
            text-decoration: none;
            border-radius: 6px;
        }

        .button:hover {
            opacity: .9;
        }

        @media (max-width: 700px) {

            .cards {
                grid-template-columns: 1fr;
            }

            .navbar {
                align-items: flex-start;
                flex-direction: column;
            }

            .navbar a {
                margin-left: 0;
                margin-right: 15px;
            }

        }

    </style>

</head>

<body>

<nav class="navbar">

    <div class="brand">
        Feedback & Survey Analytics
    </div>

    <div>

        <a href="dashboard.php">
            Dashboard
        </a>

        <a href="surveys.php">
            Surveys
        </a>

        <a href="questions.php">
            Questions
        </a>

        <a href="../analytics/dashboard.php">
            Analytics
        </a>

        <a href="../logout.php">
            Logout
        </a>

    </div>

</nav>


<div class="container">

    <h1>
        Admin Dashboard
    </h1>

    <p>
        Manage surveys, questions and view analytics.
    </p>


    <div class="cards">


        <div class="card">

            <h2>
                Surveys
            </h2>

            <p>
                Create, edit, activate, deactivate
                and delete surveys.
            </p>

            <a
                class="button"
                href="surveys.php"
            >
                Manage Surveys
            </a>

        </div>


        <div class="card">

            <h2>
                Questions
            </h2>

            <p>
                Add and manage questions
                for your surveys.
            </p>

            <a
                class="button"
                href="questions.php"
            >
                Manage Questions
            </a>

        </div>


        <div class="card">

            <h2>
                Analytics
            </h2>

            <p>
                View response statistics,
                charts and written feedback.
            </p>

            <a
                class="button"
                href="../analytics/dashboard.php"
            >
                View Analytics
            </a>

        </div>

    </div>

</div>

</body>

</html>
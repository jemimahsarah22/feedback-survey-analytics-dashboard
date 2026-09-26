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


        :root {
            --primary: #170d64;
            --primary-light: #2a1b8d;
            --background: #f5f7fb;
            --surface: #ffffff;
            --text: #1f2937;
            --muted: #6b7280;
            --border: #e5e7eb;
            --shadow: 0 10px 30px rgba(23, 13, 100, 0.08);
            --radius: 14px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: var(--background);
            color: var(--text);
            margin: 0;
            min-height: 100vh;
        }

        .navbar {
            background: var(--primary);
            color: white;
            padding: 14px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 18px;
            flex-wrap: wrap;
            box-shadow: 0 4px 18px rgba(23, 13, 100, 0.18);
        }

        .brand {
            font-size: 21px;
            font-weight: 700;
            letter-spacing: .2px;
        }

        .nav-links {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 6px;
            flex-wrap: wrap;
        }

        .navbar a {
            color: white;
            text-decoration: none;
            padding: 9px 12px;
            border-radius: 8px;
            font-size: 14px;
            transition: background .2s ease, transform .2s ease;
        }

        .navbar a:hover {
            background: rgba(255, 255, 255, .14);
            transform: translateY(-1px);
        }

        .navbar a:last-child {
            background: rgba(255, 255, 255, .12);
        }

        .container {
            width: min(92%, 1120px);
            margin: 42px auto 55px;
        }

        .page-header {
            margin-bottom: 26px;
        }

        h1 {
            color: var(--primary);
            margin: 0 0 8px;
            font-size: clamp(28px, 4vw, 38px);
        }

        .page-header p {
            color: var(--muted);
            margin: 0;
            font-size: 16px;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 22px;
            margin-top: 28px;
        }

        .card {
            background: var(--surface);
            padding: 28px;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            display: flex;
            flex-direction: column;
            min-height: 245px;
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 14px 34px rgba(23, 13, 100, 0.12);
        }

        .card-icon {
            width: 44px;
            height: 44px;
            display: grid;
            place-items: center;
            border-radius: 12px;
            background: #eeeafd;
            color: var(--primary);
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 18px;
        }

        .card h2 {
            color: var(--primary);
            margin: 0 0 10px;
            font-size: 22px;
        }

        .card p {
            color: var(--muted);
            line-height: 1.6;
            margin: 0 0 22px;
            flex: 1;
        }

        .button {
            display: inline-block;
            width: fit-content;
            padding: 11px 17px;
            background: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            transition: background .2s ease, transform .2s ease;
        }

        .button:hover {
            background: var(--primary-light);
            transform: translateY(-1px);
        }

        @media (max-width: 900px) {
            .cards {
                grid-template-columns: repeat(2, 1fr);
            }

            .card:last-child {
                grid-column: 1 / -1;
            }
        }

        @media (max-width: 700px) {
            .navbar {
                align-items: stretch;
                flex-direction: column;
                padding: 15px 4%;
            }

            .brand {
                font-size: 19px;
            }

            .nav-links {
                width: 100%;
                justify-content: flex-start;
            }

            .navbar a {
                margin: 0;
                padding: 9px 10px;
            }

            .container {
                width: 92%;
                margin-top: 30px;
            }

            .cards {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .card:last-child {
                grid-column: auto;
            }

            .card {
                min-height: 0;
                padding: 23px;
            }
        }

        @media (max-width: 420px) {
            .nav-links {
                display: grid;
                grid-template-columns: 1fr 1fr;
            }

            .navbar a {
                text-align: center;
            }

            .container {
                width: 94%;
            }
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

    <link rel="stylesheet" href="../assets/css/style.css">
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

    <div class="page-header">
        <h1>Admin Dashboard</h1>
        <p>Manage surveys, questions and view analytics.</p>
    </div>


    <div class="cards">


        <div class="card">
            <div class="card-icon">S</div>

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
            <div class="card-icon">Q</div>

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
            <div class="card-icon">A</div>

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
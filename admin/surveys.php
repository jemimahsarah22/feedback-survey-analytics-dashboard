<?php
require_once "../includes/auth.php";
require_once "../config/database.php";
require_once "../includes/csrf.php";

$sql = "SELECT 
            surveys.id,
            surveys.title,
            surveys.description,
            surveys.status,
            surveys.created_at,
            users.name AS creator_name
        FROM surveys
        LEFT JOIN users
            ON surveys.created_by = users.id
        ORDER BY surveys.created_at DESC";

$result = $conn->query($sql);

if (!$result) {
    die("Error retrieving surveys: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Manage Surveys</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

    <h1>Manage Surveys</h1>

    <p>
        Welcome,
        <strong>
            <?php echo htmlspecialchars($_SESSION["user_name"]); ?>
        </strong>
    </p>

    <hr>

    <p>
        <a href="dashboard.php">← Back to Dashboard</a>
    </p>

    <p>
        <a href="create_survey.php">Create New Survey</a>
    </p>

    <hr>

    <h2>Existing Surveys</h2>

    <?php if ($result->num_rows > 0): ?>

        <table class="admin-surveys-table" border="1" cellpadding="10" cellspacing="0">

            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Created By</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>

                <?php while ($survey = $result->fetch_assoc()): ?>

                    <tr>

                        <td>
                            <?php echo $survey["id"]; ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($survey["title"]); ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $survey["description"] ?? ""
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $survey["creator_name"] ?? "Unknown"
                            );
                            ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($survey["status"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($survey["created_at"]); ?>
                        </td>

                        <td>
                           <a href="edit_survey.php?id=<?php echo $survey["id"]; ?>">
                                Edit
                            </a>

                            |

                            <form method="POST" action="toggle_survey.php">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="id" value="<?php echo (int)$survey["id"]; ?>">
                                <button type="submit" class="warning">
                                    <?php echo ($survey["status"] === "active") ? "Deactivate" : "Activate"; ?>
                                </button>
                            </form>

                            |

                            <form method="POST" action="delete_survey.php" onsubmit="return confirm('Are you sure you want to delete this survey?');">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="id" value="<?php echo (int)$survey["id"]; ?>">
                                <button type="submit" class="danger">Delete</button>
                            </form>
                        </td>

                    </tr>

                <?php endwhile; ?>

            </tbody>

        </table>

    <?php else: ?>

        <p>No surveys have been created yet.</p>

    <?php endif; ?>

</body>

</html>
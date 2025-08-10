<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$error = $success = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'])) {
        $error = "Invalid CSRF token.";
    } else {
        $issue_type = $_POST['issue_type'];
        $description = htmlspecialchars($_POST['description']);
        $priority = $_POST['priority'];
        $submitter_id = $_SESSION['user_id'];

        // Get team_id from issue_team_mapping
        $stmt = $pdo->prepare("SELECT team_id FROM issue_team_mapping WHERE issue_type = :issue_type");
        // FIX: Changed PDO->PARAM_STR to PDO::PARAM_STR
        $stmt->bindValue(':issue_type', $issue_type, PDO::PARAM_STR);
        $stmt->execute();
        if ($team = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $team_id = $team['team_id'];

            // It's good practice to wrap inserts/updates in a transaction
            $pdo->beginTransaction();

            try {
                // Ensure your 'tickets' table has a 'team_id' column if you're inserting it.
                // Based on previous discussions, your 'tickets' table's foreign key `issue_type`
                // is the primary link. If you also want to explicitly store the initial
                // routing team_id, make sure the `tickets` table schema includes `team_id`.
                // For now, I'll keep the `team_id` in the INSERT statement as per your code.
                $stmt_insert = $pdo->prepare("INSERT INTO tickets (issue_type, description, priority, submitter_id, team_id) VALUES (:issue_type, :description, :priority, :submitter_id, :team_id)");
                // FIX: Changed PDO->PARAM_STR to PDO::PARAM_STR on these lines
                $stmt_insert->bindValue(':issue_type', $issue_type, PDO::PARAM_STR);
                $stmt_insert->bindValue(':description', $description, PDO::PARAM_STR);
                $stmt_insert->bindValue(':priority', $priority, PDO::PARAM_STR);
                // FIX: Changed PDO->PARAM_INT to PDO::PARAM_INT
                $stmt_insert->bindValue(':submitter_id', $submitter_id, PDO::PARAM_INT);
                // FIX: Changed PDO->PARAM_INT to PDO::PARAM_INT
                $stmt_insert->bindValue(':team_id', $team_id, PDO::PARAM_INT); // This assumes `tickets` table has a `team_id` column

                if ($stmt_insert->execute()) {
                    $pdo->commit(); // Commit the transaction on success
                    $success = "Ticket submitted successfully!";
                    // Clear form fields after successful submission for a fresh form
                    $_POST = array(); // Clears all POST data
                } else {
                    $pdo->rollBack(); // Rollback on failure
                    $error = "Error submitting ticket: " . implode(" ", $stmt_insert->errorInfo());
                }
            } catch (PDOException $e) {
                $pdo->rollBack(); // Rollback on exception
                error_log("Ticket submission failed: " . $e->getMessage()); // Log the actual error
                $error = "An unexpected error occurred during ticket submission. Please try again.";
            }
        } else {
            // This 'Invalid issue type' error will still occur if 'Other' is not in issue_team_mapping
            $error = "Invalid issue type selected. Please choose a valid option.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Ticket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>

<body>
    <?php include 'includes/header.php'; ?>
    <div class="container mt-5">
        <h2 class="mb-4">Submit a Support Ticket</h2>
        <?php if ($error) { ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php } ?>
        <?php if ($success) { ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php } ?>
        <form action="submit_ticket.php" method="POST" class="needs-validation" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <div class="mb-3">
                <label for="issue_type" class="form-label">Issue Type</label>
                <select name="issue_type" id="issue_type" class="form-select" required>
                    <option value="">Select Issue Type</option>
                    <option value="Hardware" <?php echo (isset($_POST['issue_type']) && $_POST['issue_type'] == 'Hardware') ? 'selected' : ''; ?>>Hardware</option>
                    <option value="Software" <?php echo (isset($_POST['issue_type']) && $_POST['issue_type'] == 'Software') ? 'selected' : ''; ?>>Software</option>
                    <option value="Network" <?php echo (isset($_POST['issue_type']) && $_POST['issue_type'] == 'Network') ? 'selected' : ''; ?>>Network</option>
                    <option value="Account Access" <?php echo (isset($_POST['issue_type']) && $_POST['issue_type'] == 'Account Access') ? 'selected' : ''; ?>>Account Access</option>
                    <option value="Other" <?php echo (isset($_POST['issue_type']) && $_POST['issue_type'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                </select>
                <div class="invalid-feedback">Please select an issue type.</div>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea name="description" id="description" class="form-control" rows="5" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                <div class="invalid-feedback">Please provide a description.</div>
            </div>
            <div class="mb-3">
                <label for="priority" class="form-label">Priority</label>
                <select name="priority" id="priority" class="form-select" required>
                    <?php
                    $priorities = ['Low', 'Medium', 'High'];
                    foreach ($priorities as $p) :
                        $selected = (isset($_POST['priority']) && $_POST['priority'] == $p) ? 'selected' : '';
                    ?>
                        <option value="<?php echo htmlspecialchars($p); ?>" <?php echo $selected; ?>>
                            <?php echo htmlspecialchars($p); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback">Please select a priority.</div>
            </div>
            <button type="submit" class="btn btn-primary">Submit Ticket</button>
        </form>
    </div>
    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Bootstrap form validation
        (function() {
            'use strict';
            const forms = document.querySelectorAll('.needs-validation');
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        })();
    </script>
</body>

</html>
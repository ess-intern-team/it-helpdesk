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
        $stmt->bindValue(':issue_type', $issue_type, PDO::PARAM_STR);
        $stmt->execute();
        if ($team = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $team_id = $team['team_id'];
            $stmt = $pdo->prepare("INSERT INTO tickets (issue_type, description, priority, submitter_id, team_id) VALUES (:issue_type, :description, :priority, :submitter_id, :team_id)");
            $stmt->bindValue(':issue_type', $issue_type, PDO::PARAM_STR);
            $stmt->bindValue(':description', $description, PDO::PARAM_STR);
            $stmt->bindValue(':priority', $priority, PDO::PARAM_STR);
            $stmt->bindValue(':submitter_id', $submitter_id, PDO::PARAM_INT);
            $stmt->bindValue(':team_id', $team_id, PDO::PARAM_INT);
            if ($stmt->execute()) {
                $success = "Ticket submitted successfully!";
            } else {
                $error = "Error: " . implode(" ", $stmt->errorInfo());
            }
        } else {
            $error = "Invalid issue type.";
        }
        $stmt->closeCursor();
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
                    <option value="Hardware">Hardware</option>
                    <option value="Software">Software</option>
                    <option value="Network">Network</option>
                    <option value="Account Access">Account Access</option>
                </select>
                <div class="invalid-feedback">Please select an issue type.</div>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea name="description" id="description" class="form-control" rows="5" required></textarea>
                <div class="invalid-feedback">Please provide a description.</div>
            </div>
            <div class="mb-3">
                <label for="priority" class="form-label">Priority</label>
                <select name="priority" id="priority" class="form-select" required>
                    <option value="Low">Low</option>
                    <option value="Medium">Medium</option>
                    <option value="High">High</option>
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
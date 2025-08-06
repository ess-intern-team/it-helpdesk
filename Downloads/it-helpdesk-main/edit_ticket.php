<?php
require_once 'config.php';

// Ensure user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

$ticket_id = $_GET['ticket_id'] ?? null;
$ticket = null;
$error = '';

// Generate CSRF token
$csrf_token = generateCsrfToken();

try {
    // Fetch ticket details
    $stmt = $pdo->prepare("SELECT
                                t.ticket_id,
                                t.issue_type,
                                t.description,
                                t.priority,
                                t.status,
                                t.submitter_id
                            FROM
                                tickets t
                            WHERE
                                t.ticket_id = :ticket_id");
    $stmt->bindParam(':ticket_id', $ticket_id, PDO::PARAM_INT);
    $stmt->execute();
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if ticket exists
    if (!$ticket) {
        setFlashMessage('danger', t('Ticket not found'));
        redirect(isSubmitter() ? 'user_dashboard.php' : 'dashboard.php');
    }

    // Authorization check for editing:
    // Only the submitter can edit their own ticket, AND the ticket must be Open or In Progress.
    // Admins/Senior Officers manage tickets via manage_ticket.php, not edit_ticket.php.
    if (!isSubmitter() || $ticket['submitter_id'] !== $_SESSION['user_id']) {
        setFlashMessage('danger', t('unauthorized_access'));
        redirect(isSubmitter() ? 'user_dashboard.php' : 'dashboard.php');
    }

    // Prevent editing if the ticket is closed
    if ($ticket['status'] == 'Closed') {
        setFlashMessage('info', t('closed_ticket_cannot_be_edited'));
        redirect('view_ticket.php?ticket_id=' . $ticket_id);
    }

    // Handle form submission for updating the ticket
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            setFlashMessage('danger', t('invalid_csrf'));
            redirect($_SERVER['REQUEST_URI']);
        }

        $new_issue_type = trim($_POST['issue_type'] ?? '');
        $new_description = trim($_POST['description'] ?? '');
        $new_priority = trim($_POST['priority'] ?? '');

        // Basic validation
        if (empty($new_issue_type) || empty($new_description) || empty($new_priority)) {
            $error = t('please_fill_all_fields');
        } elseif (!in_array($new_priority, ['Low', 'Medium', 'High'])) {
            $error = t('invalid_priority_value');
        } else {
            $pdo->beginTransaction();
            try {
                // Update the ticket details
                $stmtUpdate = $pdo->prepare("UPDATE tickets SET
                                                issue_type = :issue_type,
                                                description = :description,
                                                priority = :priority,
                                                updated_at = NOW()
                                            WHERE
                                                ticket_id = :ticket_id");

                $stmtUpdate->bindParam(':issue_type', $new_issue_type, PDO::PARAM_STR);
                $stmtUpdate->bindParam(':description', $new_description, PDO::PARAM_STR);
                $stmtUpdate->bindParam(':priority', $new_priority, PDO::PARAM_STR);
                $stmtUpdate->bindParam(':ticket_id', $ticket_id, PDO::PARAM_INT);
                $stmtUpdate->execute();

                $pdo->commit();
                setFlashMessage('success', t('Ticket updated successfully'));
                redirect('view_ticket.php?ticket_id=' . $ticket_id); // Redirect to view the updated ticket
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = t('transaction_failed') . ' ' . $e->getMessage(); // In production, just generic error
            }
        }
    }
} catch (PDOException $e) {
    $error = t('database_error') . ': ' . $e->getMessage();
}

// Generate a new CSRF token for the form
// Define available issue types (you might fetch these from a database in a larger app)
$issue_types = ['Software', 'Hardware', 'Network', 'Account', 'Other'];
$priorities = ['Low', 'Medium', 'High'];

?>

<!DOCTYPE html>
<html lang="<?php echo getHtmlLangAttribute(); ?>" dir="<?php echo getHtmlDirAttribute(); ?>" class="<?php echo getThemeClass(); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('edit_ticket'); ?> #<?php echo htmlspecialchars($ticket['ticket_id'] ?? 'N/A'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-5">
        <h2 class="mb-4"><?php echo t('Edit Ticket'); ?> #<?php echo htmlspecialchars($ticket['ticket_id'] ?? 'N/A'); ?></h2>

        <?php echo getFlashMessage(); ?>
        <?php if ($error) : ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($ticket) : ?>
            <form action="" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <div class="mb-3">
                    <label for="Issue Type" class="form-label"><?php echo t('Issue Type'); ?>:</label>
                    <select class="form-select" id="issue_type" name="issue_type" required>
                        <?php foreach ($issue_types as $type) : ?>
                            <option value="<?php echo htmlspecialchars($type); ?>" <?php echo ($ticket['issue_type'] == $type) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($type); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label"><?php echo t('description'); ?>:</label>
                    <textarea class="form-control" id="description" name="description" rows="5" required><?php echo htmlspecialchars($ticket['description']); ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="priority" class="form-label"><?php echo t('priority'); ?>:</label>
                    <select class="form-select" id="priority" name="priority" required>
                        <?php foreach ($priorities as $priority_option) : ?>
                            <option value="<?php echo htmlspecialchars($priority_option); ?>" <?php echo ($ticket['priority'] == $priority_option) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($priority_option); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> <?php echo t('Update Ticket'); ?></button>
                <a href="view_ticket.php?ticket_id=<?php echo htmlspecialchars($ticket_id); ?>" class="btn btn-secondary ms-2"><i class="fas fa-times-circle me-1"></i> <?php echo t('cancel'); ?></a>
            </form>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/scripts.js"></script>
</body>

</html>
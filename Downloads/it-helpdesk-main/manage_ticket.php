<?php
require_once 'config.php';
require_once 'send_notification.php';

// Ensure user is logged in
if (!isLoggedIn()) {
    setFlashMessage('warning', t('unauthorized_access'));
    redirect('login.php');
}

// Access control: Only allow authorized roles to manage tickets
if (!canManageTicket()) {
    setFlashMessage('danger', t('unauthorized_access'));
    redirect('user_dashboard.php');
}

// Get ticket ID from GET parameters
$ticket_id = filter_input(INPUT_GET, 'ticket_id', FILTER_VALIDATE_INT);
if (!$ticket_id) {
    setFlashMessage('danger', t('ticket_not_found'));
    redirect('dashboard.php');
}

// Fetch ticket details
$ticket = fetchTicketDetails($pdo, $ticket_id);
if (!$ticket) {
    setFlashMessage('danger', t('Ticket not found'));
    redirect('dashboard.php');
}

// Additional access check for non-admins
if (!isAdmin() && (isSeniorOfficer() || in_array(strtolower($_SESSION['role'] ?? ''), [
    'software_specialist',
    'hardware_specialist',
    'network_specialist',
    'database_specialist',
    'security_specialist',
    'support_specialist'
]))) {
    if (!isset($_SESSION['team_id']) || $ticket['team_id'] != $_SESSION['team_id']) {
        setFlashMessage('danger', t('ticket_not_found_or_not_assigned_to_your_team'));
        redirect('dashboard.php');
    }
}

// Generate CSRF token
$csrf_token = generateCsrfToken();

// Handle POST request for updating ticket
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    handleTicketUpdate($pdo, $ticket_id, $ticket);
}

// Fetch all teams for the reassignment dropdown
$allTeams = fetchAllTeams($pdo);

// Fetch comments and status logs
$comments = fetchComments($pdo, $ticket_id);
$logs = fetchStatusLogs($pdo, $ticket_id);

function fetchAllTeams(PDO $pdo): array
{
    try {
        $stmt = $pdo->query("SELECT team_id, team_name FROM teams ORDER BY team_name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        setFlashMessage('danger', t('failed_to_fetch_teams', ['message' => $e->getMessage()]));
        return [];
    }
}

function fetchTicketDetails(PDO $pdo, int $ticket_id): ?array
{
    try {
        $stmt = $pdo->prepare("
            SELECT tickets.*, users.name AS submitter_name, users.email AS submitter_email, teams.team_name
            FROM tickets
            JOIN users ON tickets.submitter_id = users.user_id
            LEFT JOIN teams ON tickets.team_id = teams.team_id
            WHERE tickets.ticket_id = :ticket_id
        ");
        $stmt->execute(['ticket_id' => $ticket_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (PDOException $e) {
        setFlashMessage('danger', t('database_error', ['message' => $e->getMessage()]));
        redirect('dashboard.php');
        return null;
    }
}

function fetchComments(PDO $pdo, int $ticket_id): array
{
    try {
        $stmt = $pdo->prepare("
            SELECT comment_text, c.created_at, u.name AS commenter_name
            FROM comments c
            JOIN users u ON c.author_id = u.user_id
            WHERE c.ticket_id = :ticket_id
            ORDER BY c.created_at DESC
        ");
        $stmt->execute(['ticket_id' => $ticket_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        setFlashMessage('danger', t('failed_to_fetch_comments', ['message' => $e->getMessage()]));
        return [];
    }
}
function fetchStatusLogs(PDO $pdo, int $ticket_id): array
{
    try {
        $stmt = $pdo->prepare("
            SELECT sh.old_status, sh.new_status, sh.changed_at, u.name AS changed_by_name
            FROM status_history sh
            JOIN users u ON sh.user_id = u.user_id
            WHERE sh.ticket_id = :ticket_id
            ORDER BY sh.changed_at DESC
        ");
        $stmt->execute(['ticket_id' => $ticket_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        setFlashMessage('danger', t('failed_to_fetch_status_history', ['message' => $e->getMessage()]));
        return [];
    }
}

function handleTicketUpdate(PDO $pdo, int $ticket_id, array $ticket): void
{
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('danger', t('invalid_csrf_token'));
        redirect($_SERVER['REQUEST_URI']);
        return;
    }

    $new_status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);
    $new_team_id = filter_input(INPUT_POST, 'new_team_id', FILTER_VALIDATE_INT);
    $comment_text = trim(filter_input(INPUT_POST, 'comment_text', FILTER_SANITIZE_STRING));
    $author_id = $_SESSION['user_id'];

    // Validate status transition
    $valid_statuses = ['Open', 'In Progress', 'Closed'];
    if (!in_array($new_status, $valid_statuses)) {
        setFlashMessage('danger', t('invalid_status'));
        redirect($_SERVER['REQUEST_URI']);
        return;
    }

    // Restrict status transitions: Only allow Open/In Progress -> Closed
    if ($new_status === 'Closed' && !in_array($ticket['status'], ['Open', 'In Progress'])) {
        setFlashMessage('danger', t('invalid_status_transition'));
        redirect($_SERVER['REQUEST_URI']);
        return;
    }

    // Prevent any status changes from Closed to other statuses
    if ($ticket['status'] === 'Closed' && $new_status !== 'Closed') {
        setFlashMessage('danger', t('cannot_change_closed_ticket'));
        redirect($_SERVER['REQUEST_URI']);
        return;
    }

    $pdo->beginTransaction();
    try {
        // Update ticket status
        $query = "UPDATE tickets SET status = :new_status, updated_at = NOW()";
        $params = ['new_status' => $new_status];

        // Add new team ID if provided and it's a different team
        if ($new_team_id !== null && $new_team_id !== $ticket['team_id']) {
            $query .= ", team_id = :new_team_id";
            $params['new_team_id'] = $new_team_id;
        }

        $query .= " WHERE ticket_id = :ticket_id";
        $params['ticket_id'] = $ticket_id;

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);

        // Log status change if different
        if ($ticket['status'] !== $new_status) {
            $stmt = $pdo->prepare("
                INSERT INTO status_history (ticket_id, user_id, old_status, new_status)
                VALUES (:ticket_id, :user_id, :old_status, :new_status)
            ");
            $stmt->execute([
                'ticket_id' => $ticket_id,
                'user_id' => $author_id,
                'old_status' => $ticket['status'],
                'new_status' => $new_status
            ]);
        }

        // Add comment if provided
        if (!empty($comment_text)) {
            $stmt = $pdo->prepare("
                INSERT INTO comments (ticket_id, comment_text, author_id)
                VALUES (:ticket_id, :comment_text, :author_id)
            ");
            $stmt->execute([
                'ticket_id' => $ticket_id,
                'comment_text' => $comment_text,
                'author_id' => $author_id
            ]);
        }

        // Fetch submitter details for notification
        $stmt = $pdo->prepare("
            SELECT u.email, u.name
            FROM tickets t
            JOIN users u ON t.submitter_id = u.user_id
            WHERE t.ticket_id = :ticket_id
        ");
        $stmt->execute(['ticket_id' => $ticket_id]);
        $submitter = $stmt->fetch(PDO::FETCH_ASSOC);

        $pdo->commit();
        // Send notification
        if ($submitter) {
            $subject = t('ticket_updated_subject', ['ticket_id' => $ticket_id]);
            $message = t('ticket_updated_message_part1', [
                'submitter_name' => $submitter['name'],
                'ticket_id' => $ticket_id,
                'status' => $new_status
            ]);
            if (!empty($comment_text)) {
                $message .= "\n" . t('ticket_updated_message_comment', ['comment_text' => $comment_text]);
            }
            $message .= "\n\n" . t('ticket_updated_message_part2', ['url' => 'http://localhost/it-helpdesk/user_dashboard.php']);
            sendNotification($submitter['email'], $subject, $message);
        }

        setFlashMessage('success', t('ticket_updated_successfully'));
        redirect($_SERVER['REQUEST_URI']);
    } catch (PDOException $e) {
        $pdo->rollBack();
        setFlashMessage('danger', t('transaction_failed', ['message' => $e->getMessage()]));
        redirect($_SERVER['REQUEST_URI']);
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(getHtmlLangAttribute()); ?>" dir="<?php echo htmlspecialchars(getHtmlDirAttribute()); ?>" class="<?php echo htmlspecialchars(getThemeClass()); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('manage_ticket'); ?> #<?php echo htmlspecialchars($ticket_id); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous">
</head>

<body>
    <?php include 'includes/header.php'; ?>
    <div class="container mt-5">
        <div id="flashMessageContainer">
            <?php echo getFlashMessage(); ?>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0"><?php echo t('Manage Ticket'); ?> #<?php echo htmlspecialchars($ticket_id); ?></h2>
            <?php if ((isAdmin() || isSeniorOfficer() || ($ticket['submitter_id'] == $_SESSION['user_id'])) && $ticket['status'] === 'Closed'): ?>
                <form action="delete_ticket.php" method="POST" onsubmit="return confirm('<?php echo t('Are you sure you want to delete this ticket? This action cannot be undone.'); ?>');">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="ticket_id" value="<?php echo htmlspecialchars($ticket_id); ?>">
                    <input type="hidden" name="action" value="delete">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash-alt me-1"></i> <?php echo t('Delete Ticket'); ?>
                    </button>
                </form>
            <?php endif; ?>
        </div>
        <div class="card mb-4 shadow-sm">
            <div class="card-body">
                <h5 class="card-title"><?php echo t('Ticket Details'); ?></h5>
                <p><strong><?php echo t('Submitter'); ?>:</strong> <?php echo htmlspecialchars($ticket['submitter_name']); ?> (<?php echo htmlspecialchars($ticket['submitter_email']); ?>)</p>
                <p><strong><?php echo t('Issue Type'); ?>:</strong> <?php echo htmlspecialchars($ticket['issue_type']); ?></p>
                <p><strong><?php echo t('Description'); ?>:</strong> <?php echo nl2br(htmlspecialchars($ticket['description'])); ?></p>
                <p><strong><?php echo t('Priority'); ?>:</strong>
                    <span class="badge <?php echo match ($ticket['priority']) {
                                            'High' => 'bg-danger',
                                            'Medium' => 'bg-warning text-dark',
                                            default => 'bg-success'
                                        }; ?>">
                        <?php echo htmlspecialchars($ticket['priority']); ?>
                    </span>
                </p>
                <p><strong><?php echo t('Status'); ?>:</strong> <?php echo htmlspecialchars($ticket['status']); ?></p>
                <p><strong><?php echo t('Team'); ?>:</strong> <?php echo htmlspecialchars($ticket['team_name'] ?? t('unassigned')); ?></p>
                <p><strong><?php echo t('Created'); ?>:</strong> <?php echo htmlspecialchars($ticket['created_at']); ?></p>
                <p><strong><?php echo t('Last Updated'); ?>:</strong> <?php echo htmlspecialchars($ticket['updated_at']); ?></p>
            </div>
        </div>

        <h3><?php echo t('Update Ticket'); ?></h3>
        <form action="?ticket_id=<?php echo htmlspecialchars($ticket_id); ?>" method="POST" class="needs-validation" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <input type="hidden" name="action" value="update">
            <div class="mb-3">
                <label for="status" class="form-label"><?php echo t('Status'); ?></label>
                <select name="status" id="status" class="form-select" required>
                    <?php
                    $statuses = ['Open' => t('open'), 'In Progress' => t('in_progress'), 'Closed' => t('closed')];
                    if ($ticket['status'] === 'Closed') {
                        echo "<option value='Closed' selected>" . t('closed') . "</option>";
                    } else {
                        foreach ($statuses as $value => $label) {
                            if ($value !== 'Closed' || in_array($ticket['status'], ['Open', 'In Progress'])) {
                                $selected = $ticket['status'] === $value ? 'selected' : '';
                                echo "<option value=\"$value\" $selected>$label</option>";
                            }
                        }
                    }
                    ?>
                </select>
                <div class="invalid-feedback"><?php echo t('Please select a status'); ?></div>
            </div>

            <div class="mb-3">
                <label for="new_team_id" class="form-label"><?php echo t('Reassign to Team'); ?></label>
                <select name="new_team_id" id="new_team_id" class="form-select">
                    <option value="">-- <?php echo t('Unassigned'); ?> --</option>
                    <?php foreach ($allTeams as $team): ?>
                        <option value="<?php echo htmlspecialchars($team['team_id']); ?>" <?php echo ($ticket['team_id'] == $team['team_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($team['team_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="comment_text" class="form-label"><?php echo t('Comment'); ?></label>
                <textarea name="comment_text" id="comment_text" class="form-control" rows="4" placeholder="<?php echo t('optional comment for update'); ?>"></textarea>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> <?php echo t('Update Ticket'); ?></button>
        </form>

        <h3 class="mt-4"><?php echo t('Comments'); ?></h3>
        <?php if ($comments): ?>
            <div class="list-group mb-4">
                <?php foreach ($comments as $comment): ?>
                    <div class="list-group-item">
                        <p><strong><?php echo htmlspecialchars($comment['commenter_name']); ?> (<?php echo htmlspecialchars($comment['created_at']); ?>):</strong></p>
                        <p><?php echo nl2br(htmlspecialchars($comment['comment_text'])); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p><?php echo t('No comments yet'); ?></p>
        <?php endif; ?>

        <h3 class="mt-4"><?php echo t('Status change history'); ?></h3>
        <?php if ($logs): ?>
            <div class="list-group">
                <?php foreach ($logs as $log): ?>
                    <div class="list-group-item">
                        <p>
                            <strong><?php echo htmlspecialchars($log['changed_by_name']); ?> (<?php echo htmlspecialchars($log['changed_at']); ?>):</strong>
                            <br>
                            Changed status from <span class="badge bg-secondary"><?php echo htmlspecialchars($log['old_status']); ?></span>
                            to <span class="badge bg-primary"><?php echo htmlspecialchars($log['new_status']); ?></span>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p><?php echo t('No status changes yet'); ?></p>
        <?php endif; ?>
    </div>
    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="js/scripts.js"></script>
    <script>
        // Bootstrap form validation
        document.addEventListener('DOMContentLoaded', () => {
            'use strict';
            const forms = document.querySelectorAll('.needs-validation');
            forms.forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });

            // Automatically dismiss flash messages after 2 seconds
            const flashMessageContainer = document.getElementById('flashMessageContainer');
            if (flashMessageContainer) {
                const flashMessages = flashMessageContainer.querySelectorAll('.alert');
                flashMessages.forEach(function(message) {
                    setTimeout(function() {
                        const bsAlert = new bootstrap.Alert(message);
                        bsAlert.close(); // Close the alert with fade-out animation
                    }, 2000); // 2 seconds
                });
            }
        });
    </script>
</body>

</html>
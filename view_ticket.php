<?php
require_once 'config.php';

// Ensure user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

$ticket_id = $_GET['ticket_id'] ?? null;
$ticket = null;
$error = '';
$success = '';

// Validate ticket_id
if (!$ticket_id || !filter_var($ticket_id, FILTER_VALIDATE_INT)) {
    setFlashMessage('danger', t('ticket_not_found'));
    redirect(isSubmitter() ? 'user_dashboard.php' : 'dashboard.php');
}

try {
    // Fetch ticket details
    $stmt = $pdo->prepare("SELECT
                                t.ticket_id,
                                t.issue_type,
                                t.description,
                                t.priority,
                                t.status,
                                t.created_at,
                                t.updated_at,
                                t.submitter_id,
                                u.name AS submitter_name,
                                ta.team_name AS assigned_team
                            FROM
                                tickets t
                            JOIN
                                users u ON t.submitter_id = u.user_id
                            LEFT JOIN
                                teams ta ON t.team_id = ta.team_id
                            WHERE
                                t.ticket_id = :ticket_id");
    $stmt->bindParam(':ticket_id', $ticket_id, PDO::PARAM_INT);
    $stmt->execute();
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if ticket exists and if the user has permission to view it
    if (!$ticket) {
        setFlashMessage('danger', t('ticket_not_found'));
        redirect(isSubmitter() ? 'user_dashboard.php' : 'dashboard.php');
    }

    // Authorization check:
    // A submitter can only view their own tickets.
    // An admin or senior officer can view any ticket.
    if (isSubmitter() && $ticket['submitter_id'] !== $_SESSION['user_id']) {
        setFlashMessage('danger', t('ticket_not_found')); // Generic message for security
        redirect('user_dashboard.php');
    }
} catch (PDOException $e) {
    $error = t('database_error') . ': ' . $e->getMessage();
}

// Generate a new CSRF token for the form (kept for consistency)
$csrf_token = generateCsrfToken();
?>

<!DOCTYPE html>
<html lang="<?php echo getHtmlLangAttribute(); ?>" dir="<?php echo getHtmlDirAttribute(); ?>" class="<?php echo getThemeClass(); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('ticket_details'); ?> - <?php echo htmlspecialchars($ticket['ticket_id'] ?? 'N/A'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-5">
        <?php echo getFlashMessage(); ?>

        <?php if ($error) : ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php elseif ($ticket) : ?>
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4><?php echo t('Ticket Details'); ?> #<?php echo htmlspecialchars($ticket['ticket_id']); ?></h4>
                    <div>
                        <?php if (isSubmitter() && ($ticket['status'] == 'Open' || $ticket['status'] == 'In Progress')) : ?>
                            <a href="edit_ticket.php?ticket_id=<?php echo htmlspecialchars($ticket['ticket_id']); ?>" class="btn btn-warning btn-sm">
                                <i class="fas fa-edit me-1"></i> <?php echo t('Edit Ticket'); ?>
                            </a>
                        <?php elseif (isAdmin() || isSeniorOfficer()) : ?>
                            <a href="manage_ticket.php?ticket_id=<?php echo htmlspecialchars($ticket['ticket_id']); ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-tasks me-1"></i> <?php echo t('Manage Ticket'); ?>
                            </a>
                        <?php endif; ?>
                        <a href="<?php echo isSubmitter() ? 'user_dashboard.php' : 'dashboard.php'; ?>" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i> <?php echo t('Back To Dashboard'); ?>
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6"><strong><?php echo t('submitter'); ?>:</strong> <?php echo htmlspecialchars($ticket['submitter_name']); ?></div>
                        <div class="col-md-6"><strong><?php echo t('issue type'); ?>:</strong> <?php echo htmlspecialchars($ticket['issue_type']); ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-12"><strong><?php echo t('Description'); ?>:</strong><br><?php echo nl2br(htmlspecialchars($ticket['description'])); ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4"><strong><?php echo t('priority'); ?>:</strong>
                            <span class="badge <?php
                                                if ($ticket['priority'] == 'High') {
                                                    echo 'bg-danger';
                                                } elseif ($ticket['priority'] == 'Medium') {
                                                    echo 'bg-warning text-dark';
                                                } else {
                                                    echo 'bg-success';
                                                }
                                                ?>"><?php echo htmlspecialchars($ticket['priority']); ?></span>
                        </div>
                        <div class="col-md-4"><strong><?php echo t('status'); ?>:</strong>
                            <span class="badge <?php
                                                if ($ticket['status'] == 'Open') {
                                                    echo 'bg-info';
                                                } elseif ($ticket['status'] == 'In Progress') {
                                                    echo 'bg-primary';
                                                } else {
                                                    echo 'bg-secondary';
                                                }
                                                ?>"><?php echo htmlspecialchars($ticket['status']); ?></span>
                        </div>
                        <div class="col-md-4"><strong><?php echo t('Assigned Team'); ?>:</strong> <?php echo htmlspecialchars($ticket['assigned_team'] ?? t('unassigned')); ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6"><strong><?php echo t('Created'); ?>:</strong> <?php echo htmlspecialchars($ticket['created_at']); ?></div>
                        <div class="col-md-6"><strong><?php echo t('last updated'); ?>:</strong> <?php echo htmlspecialchars($ticket['updated_at']); ?></div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/scripts.js"></script>
</body>

</html>
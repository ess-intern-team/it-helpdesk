<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// Redirect if not a submitter (admins/senior officers have their own dashboard)
if (!isSubmitter()) {
    header("Location: dashboard.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$tickets = [];
$error = '';

// Fetch tickets submitted by the current user
// Using PDO prepare and execute
$stmt = $pdo->prepare("SELECT ticket_id, issue_type, description, priority, status, created_at FROM tickets WHERE submitter_id = :user_id ORDER BY created_at DESC");

if ($stmt) {
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch all results
} else {
    // In a real application, you'd log this more securely and not expose PDO errors directly
    $error = "Failed to prepare ticket fetching statement.";
    // You could also get detailed error info from $pdo->errorInfo() if needed for debugging
}
?>
<!DOCTYPE html>
<html lang="<?php echo getHtmlLangAttribute(); ?>" dir="<?php echo getHtmlDirAttribute(); ?>" class="<?php echo getThemeClass(); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('user_dashboard'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-5">
        <h2 class="mb-4"><?php printf(t('welcome_user'), htmlspecialchars($_SESSION['name'])); ?></h2>
        <h3 class="mb-3"><?php echo t('my_tickets'); ?></h3>

        <div class="mb-3">
            <a href="submit_ticket.php" class="btn btn-primary"><?php echo t('submit_new_ticket'); ?></a>
        </div>

        <?php echo getFlashMessage(); // Display any flash messages 
        ?>

        <?php if ($error) : ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (empty($tickets)) : ?>
            <div class="alert alert-info"><?php echo t('no_tickets_submitted'); ?></div>
        <?php else : ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th><?php echo t('ticket_id'); ?></th>
                            <th><?php echo t('issue_type'); ?></th>
                            <th><?php echo t('description'); ?></th>
                            <th><?php echo t('priority'); ?></th>
                            <th><?php echo t('status'); ?></th>
                            <th><?php echo t('created'); ?></th>
                            <th><?php echo t('actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tickets as $ticket) : ?>
                            <tr>
                                <td><?php echo htmlspecialchars($ticket['ticket_id']); ?></td>
                                <td><?php echo htmlspecialchars($ticket['issue_type']); ?></td>
                                <td><?php echo htmlspecialchars(substr($ticket['description'], 0, 50)) . (strlen($ticket['description']) > 50 ? '...' : ''); ?></td>
                                <td>
                                    <span class="badge <?php
                                                        // Dynamically assign Bootstrap badge classes based on priority
                                                        if ($ticket['priority'] == 'High') {
                                                            echo 'bg-danger';
                                                        } elseif ($ticket['priority'] == 'Medium') {
                                                            echo 'bg-warning';
                                                        } else {
                                                            echo 'bg-success'; // For Low priority
                                                        }
                                                        ?>">
                                        <?php echo htmlspecialchars($ticket['priority']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?php
                                                        // Dynamically assign Bootstrap badge classes based on status
                                                        if ($ticket['status'] == 'Open') {
                                                            echo 'bg-info';
                                                        } elseif ($ticket['status'] == 'In Progress') {
                                                            echo 'bg-primary';
                                                        } else {
                                                            echo 'bg-secondary'; // For Closed status
                                                        }
                                                        ?>">
                                        <?php echo htmlspecialchars($ticket['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($ticket['created_at']); ?></td>
                                <td>
                                    <a href="view_ticket.php?ticket_id=<?php echo htmlspecialchars($ticket['ticket_id']); ?>" class="btn btn-sm btn-info"><?php echo t('view'); ?></a>
                                    <?php if ($ticket['status'] == 'Open' || $ticket['status'] == 'In Progress') : // Allow editing only if ticket is Open or In Progress 
                                    ?>
                                        <a href="edit_ticket.php?ticket_id=<?php echo htmlspecialchars($ticket['ticket_id']); ?>" class="btn btn-sm btn-warning"><?php echo t('edit'); ?></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/scripts.js"></script>
</body>

</html>
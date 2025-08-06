<?php
require_once 'config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

// Generate CSRF token
$csrf_token = generateCsrfToken();

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
    <title><?php echo t('User Dashboard'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=home" />
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-5">
        <h2 style="text-align: center;" class="mb-4">
            <?php printf(t('Welcome User'), htmlspecialchars($_SESSION['name'])); ?>
        </h2>
        <h3 class="mb-3"><?php echo t('My Tickets'); ?></h3>

        <div class="mb-3">
            <a href="submit_ticket.php" class="btn btn-primary"><?php echo t('Submit New Ticket'); ?></a>
        </div>

        <div id="flashMessageContainer">
            <?php echo getFlashMessage(); // Display any flash messages from actions 
            ?>
        </div>
        <?php if ($error) : ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if (empty($tickets)) : ?>
            <div class="alert alert-info"><?php echo t('no tickets submitted'); ?></div>
        <?php else : ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th><?php echo t('Ticket Id'); ?></th>
                            <th><?php echo t('Issue Type'); ?></th>
                            <th><?php echo t('Description'); ?></th>
                            <th><?php echo t('Priority'); ?></th>
                            <th><?php echo t('Status'); ?></th>
                            <th><?php echo t('Created'); ?></th>
                            <th><?php echo t('Actions'); ?></th>
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
                                                        if ($ticket['priority'] == 'High') {
                                                            echo 'bg-danger';
                                                        } elseif ($ticket['priority'] == 'Medium') {
                                                            echo 'bg-warning text-dark';
                                                        } else {
                                                            echo 'bg-success';
                                                        }
                                                        ?>">
                                        <?php echo htmlspecialchars($ticket['priority']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?php
                                                        if ($ticket['status'] == 'Open') {
                                                            echo 'bg-info text-dark';
                                                        } elseif ($ticket['status'] == 'In Progress') {
                                                            echo 'bg-primary';
                                                        } else {
                                                            echo 'bg-secondary';
                                                        }
                                                        ?>">
                                        <?php echo htmlspecialchars($ticket['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($ticket['created_at']); ?></td>
                                <td class="d-flex align-items-center">
                                    <a href="view_ticket.php?ticket_id=<?php echo htmlspecialchars($ticket['ticket_id']); ?>" class="btn btn-sm btn-info me-1"><?php echo t('View'); ?></a>
                                    <?php if ($ticket['status'] == 'Open' || $ticket['status'] == 'In Progress') : ?>
                                        <a href="edit_ticket.php?ticket_id=<?php echo htmlspecialchars($ticket['ticket_id']); ?>" class="btn btn-sm btn-warning me-1"><?php echo t('Edit'); ?></a>
                                    <?php endif; ?>
                                    <?php if ($ticket['status'] == 'Open' || $ticket['status'] == 'In Progress' || $ticket['status'] == 'Closed') : ?>
                                        <form action="delete_user_ticket.php" method="POST" style="display:inline-block;" class="me-1" onsubmit="return confirm('<?php echo t('Are you sure you want to delete this ticket? This action cannot be undone.'); ?>');">
                                            <input type="hidden" name="ticket_id" value="<?php echo htmlspecialchars($ticket['ticket_id']); ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="btn btn-sm btn-danger"><?php echo t('Delete'); ?></button>
                                        </form>
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Target the specific container for flash messages
            const flashMessageDisplayArea = document.getElementById('flashMessageContainer');

            // Only proceed if the container for flash messages exists
            if (flashMessageDisplayArea) {
                // Get all alert elements within the flashMessageContainer
                const flashMessages = flashMessageDisplayArea.querySelectorAll('.alert');

                flashMessages.forEach(function(message) {
                    // Set timeout to 3 seconds (3000 milliseconds)
                    setTimeout(function() {
                        // Use Bootstrap's native Alert component to dismiss
                        const bsAlert = new bootstrap.Alert(message);
                        bsAlert.close(); // Close the alert (fades out and removes)
                    }, 3000); // 3 seconds
                });
            }
            // The 'no tickets submitted' alert and any $error alert are outside
            // 'flashMessageContainer' and will not be affected by this script,
            // so they will stay on the page.
        });
    </script>
</body>

</html>
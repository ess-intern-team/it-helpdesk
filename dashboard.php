<?php
require_once 'config.php'; // This now provides $pdo

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

if (!isSeniorOfficer() && !isAdmin()) {
    header("Location: user_dashboard.php"); // Redirect non-senior officers/admins
    exit();
}

$seniorOfficerTeamId = $_SESSION['team_id'] ?? null;
$tickets = [];
$error = '';
$teamName = ''; // Initialize teamName variable

try {
    // Fetch the team name if the user is a Senior Officer
    if (isSeniorOfficer() && $seniorOfficerTeamId) {
        $stmtTeam = $pdo->prepare("SELECT team_name FROM teams WHERE team_id = :team_id");
        $stmtTeam->bindParam(":team_id", $seniorOfficerTeamId, PDO::PARAM_INT);
        $stmtTeam->execute();
        $teamResult = $stmtTeam->fetch(PDO::FETCH_ASSOC);
        if ($teamResult) {
            $teamName = $teamResult['team_name'];
        }
    }

    // Fetch tickets assigned to the senior officer's team if senior officer, or all tickets if admin
    $sql = "SELECT t.ticket_id, t.issue_type, t.description, t.priority, t.status, t.created_at, u.name AS submitter_name, tm.team_name
            FROM tickets t
            JOIN users u ON t.submitter_id = u.user_id
            LEFT JOIN teams tm ON t.team_id = tm.team_id";

    if (isSeniorOfficer()) {
        $sql .= " WHERE t.team_id = :team_id ORDER BY t.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(":team_id", $seniorOfficerTeamId, PDO::PARAM_INT);
        $stmt->execute();
        $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif (isAdmin()) {
        $sql .= " ORDER BY t.created_at DESC";
        $stmt = $pdo->query($sql);
        $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Fetch all users for potential future use (converted to PDO)
    $users = [];
    $stmtUsers = $pdo->prepare("SELECT user_id, name FROM users");
    $stmtUsers->execute();
    while ($rowUser = $stmtUsers->fetch(PDO::FETCH_ASSOC)) {
        $users[$rowUser['user_id']] = $rowUser['name'];
    }
} catch (PDOException $e) {
    $error = t('database_error') . ": " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="<?php echo getHtmlLangAttribute(); ?>" dir="<?php echo getHtmlDirAttribute(); ?>" class="<?php echo getThemeClass(); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('dashboard'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body>
    <?php include 'includes/header.php'; ?>
    <div class="container mt-5">
        <h2 class="mb-4">
            <?php
            if (isSeniorOfficer()) {
                if ($teamName) {
                    echo htmlspecialchars($teamName) . ' ' . t('Tickets'); // Display team name + "Tickets"
                } else {
                    echo t('Tickets assigned to your team'); // Fallback if team name not found
                }
            } elseif (isAdmin()) {
                echo t('all_tickets');
            }
            ?>
        </h2>

        <?php if ($error) : ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (empty($tickets)) : ?>
            <div class="alert alert-info"><?php echo t('No Tickets Assigned'); ?></div>
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
                            <th><?php echo t('Submitter Name'); ?></th>
                            <th><?php echo t('Team'); ?></th>
                            <th><?php echo t('Created'); ?></th>
                            <th><?php echo t('Actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tickets as $ticket) : ?>
                            <tr>
                                <td><?php echo $ticket['ticket_id']; ?></td>
                                <td><?php echo htmlspecialchars($ticket['issue_type']); ?></td>
                                <td><?php echo htmlspecialchars(substr($ticket['description'], 0, 50)) . (strlen($ticket['description']) > 50 ? '...' : ''); ?></td>
                                <td>
                                    <span class="badge <?php echo $ticket['priority'] == 'High' ? 'bg-danger' : ($ticket['priority'] == 'Medium' ? 'bg-warning text-dark' : 'bg-success'); ?>">
                                        <?php echo htmlspecialchars($ticket['priority']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($ticket['status']); ?></td>
                                <td><?php echo htmlspecialchars($ticket['submitter_name'] ?? 'Unknown'); ?></td>
                                <td><?php echo htmlspecialchars($ticket['team_name'] ?? t('unassigned')); ?></td>
                                <td><?php echo htmlspecialchars($ticket['created_at']); ?></td>
                                <td>
                                    <a href="manage_ticket.php?ticket_id=<?php echo $ticket['ticket_id']; ?>" class="btn btn-sm btn-info"><?php echo t('manage'); ?></a>
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
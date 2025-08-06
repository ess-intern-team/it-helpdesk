<?php
require_once 'config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure user is logged in
if (!isLoggedIn()) {
    setFlashMessage('error', t('You must be logged in to perform this action.'));
    error_log("Delete Ticket Error: User not logged in, Ticket ID: " . ($_POST['ticket_id'] ?? 'Unknown'));
    header("Location: login.php");
    exit();
}

// Check CSRF token
$csrf_token = $_POST['csrf_token'] ?? '';
if (!validateCsrfToken($csrf_token)) {
    setFlashMessage('error', t('Invalid CSRF token. Please try again.'));
    error_log("Delete Ticket Error: Invalid CSRF token, Ticket ID: " . ($_POST['ticket_id'] ?? 'Unknown'));
    header("Location: user_dashboard.php");
    exit();
}

// Verify POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlashMessage('error', t('Invalid request method.'));
    error_log("Delete Ticket Error: Invalid request method, Ticket ID: " . ($_POST['ticket_id'] ?? 'Unknown'));
    header("Location: user_dashboard.php");
    exit();
}

// Validate ticket ID
$ticket_id = filter_input(INPUT_POST, 'ticket_id', FILTER_VALIDATE_INT);
if (!$ticket_id) {
    setFlashMessage('error', t('Invalid Ticket ID provided.'));
    error_log("Delete Ticket Error: Invalid Ticket ID: " . ($_POST['ticket_id'] ?? 'Unknown'));
    header("Location: user_dashboard.php");
    exit();
}

$user_id = $_SESSION['user_id'] ?? 0;

try {
    $pdo->beginTransaction();

    // Fetch ticket details to verify ownership
    $stmt = $pdo->prepare("SELECT submitter_id FROM tickets WHERE ticket_id = :ticket_id");
    $stmt->execute(['ticket_id' => $ticket_id]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        setFlashMessage('error', t('Ticket not found.'));
        error_log("Delete Ticket Error: Ticket not found, Ticket ID: $ticket_id");
        $pdo->rollBack();
        header("Location: user_dashboard.php");
        exit();
    }

    // Check authorization: only the submitter can delete
    if ($ticket['submitter_id'] != $user_id) {
        setFlashMessage('error', t('You are not authorized to delete this ticket.'));
        error_log("Delete Ticket Error: User $user_id not authorized, Ticket ID: $ticket_id, Submitter ID: {$ticket['submitter_id']}");
        $pdo->rollBack();
        header("Location: user_dashboard.php");
        exit();
    }

    // Delete related records
    $stmt = $pdo->prepare("DELETE FROM ticket_logs WHERE ticket_id = :ticket_id");
    $stmt->execute(['ticket_id' => $ticket_id]);

    $stmt = $pdo->prepare("DELETE FROM comments WHERE ticket_id = :ticket_id");
    $stmt->execute(['ticket_id' => $ticket_id]);

    $stmt = $pdo->prepare("DELETE FROM status_history WHERE ticket_id = :ticket_id");
    $stmt->execute(['ticket_id' => $ticket_id]);

    // Delete the ticket itself
    $stmt = $pdo->prepare("DELETE FROM tickets WHERE ticket_id = :ticket_id AND submitter_id = :user_id");
    $stmt->execute(['ticket_id' => $ticket_id, 'user_id' => $user_id]);

    if ($stmt->rowCount() > 0) {
        $pdo->commit();
        setFlashMessage('success', t('Ticket deleted successfully.'));
        error_log("Delete Ticket Success: Ticket deleted, Ticket ID: $ticket_id, User ID: $user_id");
        header("Location: user_dashboard.php");
        exit();
    } else {
        $pdo->rollBack();
        setFlashMessage('error', t('Failed to delete ticket.'));
        error_log("Delete Ticket Error: No rows affected, Ticket ID: $ticket_id");
        header("Location: user_dashboard.php");
        exit();
    }
} catch (PDOException $e) {
    $pdo->rollBack();
    setFlashMessage('error', t('Database Error: ') . $e->getMessage());
    error_log("Delete Ticket PDO Error: " . $e->getMessage() . ", Ticket ID: $ticket_id");
    header("Location: user_dashboard.php");
    exit();
}

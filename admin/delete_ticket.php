<?php

session_start();
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';

redirectIfNotLoggedIn();
redirectIfNotAdmin();

if (!isset($_GET['id'])) {
    $_SESSION['error'] = "ID du ticket manquant";
    header('Location: dashboard.php');
    exit();
}

$ticketId = intval($_GET['id']);

$ticket = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM tickets WHERE ticket_id = $ticketId"));
if (!$ticket) {
    $_SESSION['error'] = "Ticket non trouvé";
    header('Location: dashboard.php');
    exit();
}

if ($ticket['statut'] !== 'résolu') {
    $_SESSION['error'] = "Seuls les tickets résolus peuvent être supprimés.";
    header('Location: dashboard.php');
    exit();
}

mysqli_query($conn, "DELETE FROM tickets WHERE ticket_id = $ticketId");
$_SESSION['success'] = "Ticket supprimé avec succès";
header('Location: dashboard.php');
exit();
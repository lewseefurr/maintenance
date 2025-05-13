<?php
session_start();
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';

redirectIfNotLoggedIn();
redirectIfNotTechnician();

if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$ticketId = $_GET['id'];
$techId = $_SESSION['user_id'];

$checkQuery = "SELECT statut, assigned_to FROM tickets WHERE ticket_id = ?";
$stmt = mysqli_prepare($conn, $checkQuery);
mysqli_stmt_bind_param($stmt, "i", $ticketId);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $statut, $assignedTo);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

if ($assignedTo !== null) {
    $_SESSION['error'] = "Ce ticket est déjà assigné";
    header("Location: dashboard.php");
    exit();
}

$updateQuery = "UPDATE tickets SET assigned_to = ?, statut = 'en_cours' WHERE ticket_id = ?";
$stmt = mysqli_prepare($conn, $updateQuery);
mysqli_stmt_bind_param($stmt, "ii", $techId, $ticketId);
$success = mysqli_stmt_execute($stmt);
$_SESSION['stats_updated'] = time();
if ($success) {
    $_SESSION['success'] = "Ticket assigné avec succès";
} else {
    $_SESSION['error'] = "Erreur lors de l'assignation du ticket";
}

header("Location: dashboard.php");
exit();
?>
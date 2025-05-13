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

$checkQuery = "SELECT assigned_to FROM tickets WHERE ticket_id = ?";
$stmt = mysqli_prepare($conn, $checkQuery);
mysqli_stmt_bind_param($stmt, "i", $ticketId);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt); 

if (mysqli_stmt_num_rows($stmt) === 0) {
    $_SESSION['error'] = "Ticket introuvable";
    header("Location: dashboard.php");
    exit();
}

mysqli_stmt_bind_result($stmt, $assignedTo);
mysqli_stmt_fetch($stmt);

if ($assignedTo != $techId) {
    $_SESSION['error'] = "Vous ne pouvez pas supprimer ce ticket";
    header("Location: dashboard.php");
    exit();
}

$deleteQuery = "DELETE FROM tickets WHERE ticket_id = ?";
$stmt = mysqli_prepare($conn, $deleteQuery);
mysqli_stmt_bind_param($stmt, "i", $ticketId);
$success = mysqli_stmt_execute($stmt);

if ($success) {
    $_SESSION['success'] = "Ticket supprimé avec succès";
} else {
    $_SESSION['error'] = "Erreur lors de la suppression du ticket";
}

header("Location: dashboard.php");
exit();
?>
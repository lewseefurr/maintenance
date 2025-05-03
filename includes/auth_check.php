<?php
session_start();

$required_roles = ['admin', 'technicien', 'employe'];

if (empty($_SESSION['user_id']) || empty($_SESSION['role']) || !in_array($_SESSION['role'], $required_roles)) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    $_SESSION['error'] = "Authentification requise";
    header('Location: login.php');
    exit();
}

// Vérification CSRF pour les formulaires
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Erreur de sécurité CSRF");
    }
}
?>
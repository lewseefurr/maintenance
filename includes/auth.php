<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
function redirectIfNotLoggedIn() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../auth/login.php");
        exit();
    }
}

function redirectIfNotAdmin() {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        header('Location: ../index.php');
        exit();
    }
}

function redirectIfNotTechnician() {
    if ($_SESSION['role'] !== 'technicien') {
        header("Location: ../auth/unauthorized.php");
        exit();
    }
}


function verifyPassword($inputPassword, $hashedPassword) {
    return password_verify($inputPassword, $hashedPassword);
}

function sanitizeInput($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}
?>
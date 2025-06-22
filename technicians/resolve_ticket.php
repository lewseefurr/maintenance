<?php
session_start();
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';

redirectIfNotLoggedIn();
redirectIfNotTechnician();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['ticket_id'])) {
        $_SESSION['error'] = "Ticket introuvable";
        header("Location: dashboard.php");
        exit();
    }

    $ticketId = $_POST['ticket_id'];
    $techId = $_SESSION['user_id'];
    $comment = isset($_POST['resolution_comment']) ? trim($_POST['resolution_comment']) : null;

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
        $_SESSION['error'] = "Vous ne pouvez pas résoudre ce ticket";
        header("Location: dashboard.php");
        exit();
    }

    $resolveQuery = "UPDATE tickets SET statut = 'résolu', date_resolution = NOW(), resolution_comment = ? WHERE ticket_id = ?";
    $stmt = mysqli_prepare($conn, $resolveQuery);
    mysqli_stmt_bind_param($stmt, "si", $comment, $ticketId);
    $success = mysqli_stmt_execute($stmt);

    if ($success) {
        $_SESSION['success'] = "Ticket marqué comme résolu" . ($comment ? " avec commentaire" : "");
    } else {
        $_SESSION['error'] = "Erreur lors de la résolution du ticket";
    }

    header("Location: dashboard.php");
    exit();
} elseif (isset($_GET['id'])) {
    $ticketId = $_GET['id'];
    $techId = $_SESSION['user_id'];

    $checkQuery = "SELECT t.*, e.nom as equipement_nom 
                   FROM tickets t
                   JOIN equipements e ON t.equipement_id = e.equipement_id
                   WHERE t.ticket_id = ? AND t.assigned_to = ?";
    $stmt = mysqli_prepare($conn, $checkQuery);
    mysqli_stmt_bind_param($stmt, "ii", $ticketId, $techId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) === 0) {
        $_SESSION['error'] = "Ticket introuvable ou non assigné à vous";
        header("Location: dashboard.php");
        exit();
    }

    $ticket = mysqli_fetch_assoc($result);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Résoudre le ticket | TechDashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Résoudre le ticket #<?= $ticket['ticket_id'] ?></h4>
                    </div>
                    <div class="card-body">
                        <h5><?= htmlspecialchars($ticket['title']) ?></h5>
                        <p class="text-muted">Équipement: <?= htmlspecialchars($ticket['equipement_nom']) ?></p>
                        <hr>
                        
                        <form method="POST" action="resolve_ticket.php">
                            <input type="hidden" name="ticket_id" value="<?= $ticket['ticket_id'] ?>">
                            
                            <div class="mb-3">
                                <label for="resolution_comment" class="form-label">Commentaire de résolution (facultatif)</label>
                                <textarea class="form-control" id="resolution_comment" name="resolution_comment" 
                                          rows="4" placeholder="Décrivez comment vous avez résolu le problème..."></textarea>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="dashboard.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left"></i> Retour
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle"></i> Confirmer la résolution
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
} else {
    header("Location: dashboard.php");
    exit();
}
?>
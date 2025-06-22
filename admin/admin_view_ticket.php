<?php
session_start();
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';

redirectIfNotLoggedIn();
redirectIfNotAdmin();

if (!isset($_GET['id'])) {
    header('Location: admin_dashboard.php');
    exit();
}

$ticketId = intval($_GET['id']);
$ticket = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT t.*, u.username AS createur, e.nom AS equipement_nom, tech.username AS technicien
     FROM tickets t
     JOIN users u ON t.user_id = u.user_id
     LEFT JOIN equipements e ON t.equipement_id = e.equipement_id
     LEFT JOIN users tech ON t.assigned_to = tech.user_id
     WHERE t.ticket_id = $ticketId"));

if (!$ticket) {
    $_SESSION['error'] = "Ticket non trouvé";
    header('Location: admin_dashboard.php');
    exit();
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['statut'])) {
    $newStatus = mysqli_real_escape_string($conn, $_POST['statut']);
    $comment = mysqli_real_escape_string($conn, $_POST['comment'] ?? '');
    
    $query = "UPDATE tickets SET statut = '$newStatus'";
    if ($newStatus === 'résolu') {
        $query .= ", date_resolution = NOW(), resolution_comment = '$comment'";
    }
    $query .= " WHERE ticket_id = $ticketId";
    
    mysqli_query($conn, $query);
    $_SESSION['success'] = "Statut du ticket mis à jour";
    header("Location: admin_view_ticket.php?id=$ticketId");
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord Admin | <?= htmlspecialchars($_SESSION['username']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --primary-light: #6366f1;
            --secondary: #10b981;
            --accent: #f59e0b;
            --danger: #ef4444;
            --dark: #1e293b;
            --light: #f8fafc;
            --gray: #94a3b8;
            --background: #f1f5f9;
        }
        
        .admin-header {
            background: linear-gradient(rgba(0, 0, 0, 0.7), url('admin-bg.jpg') no-repeat center center;
            background-size: cover;
            color: white;
            padding: 3rem 0 4rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            color: white;
        }
        
        .stat-card.primary { background-color: var(--primary); }
        .stat-card.success { background-color: var(--secondary); }
        .stat-card.warning { background-color: var(--accent); }
        .stat-card.danger { background-color: var(--danger); }
        
        .nav-pills .nav-link.active {
            background-color: var(--primary);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-shield-lock"></i>
                <span>Admin Dashboard</span>
            </a>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="bi bi-speedometer2"></i> Tableau de bord
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_users.php">
                            <i class="bi bi-people"></i> Utilisateurs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_equipements.php">
                            <i class="bi bi-pc-display"></i> Équipements
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_reports.php">
                            <i class="bi bi-graph-up"></i> Rapports
                        </a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle text-white" id="userDropdown" data-bs-toggle="dropdown">
                            <div class="me-2 d-none d-lg-block text-end">
                                <div class="fw-medium"><?= htmlspecialchars($_SESSION['nom'] ?? $_SESSION['username']) ?></div>
                                <small class="text-white-50">Administrateur</small>
                            </div>
                            <i class="bi bi-person-circle fs-4"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                        
                            <li><a class="dropdown-item text-danger" href="../auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i> Déconnexion</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <main class="container mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Détails du Ticket #<?= $ticket['ticket_id'] ?></h2>
            <a href="admin_dashboard.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><?= htmlspecialchars($ticket['title']) ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <h6>Description:</h6>
                            <p><?= nl2br(htmlspecialchars($ticket['description'])) ?></p>
                        </div>

                        <?php if ($ticket['resolution_comment']): ?>
                        <div class="mb-3">
                            <h6>Commentaire de résolution:</h6>
                            <p><?= nl2br(htmlspecialchars($ticket['resolution_comment'])) ?></p>
                        </div>
                        <?php endif; ?>

                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Créé par:</strong> <?= $ticket['createur'] ?></p>
                                <p><strong>Date création:</strong> <?= $ticket['date_creation'] ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Équipement:</strong> <?= $ticket['equipement_nom'] ?? 'Aucun' ?></p>
                                <p><strong>Technicien:</strong> <?= $ticket['technicien'] ?? 'Non assigné' ?></p>
                                <?php if ($ticket['date_resolution']): ?>
                                    <p><strong>Date résolution:</strong> <?= $ticket['date_resolution'] ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="mb-0">Actions</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Statut</label>
                                <select name="statut" class="form-select" required>
                                    <option value="ouvert" <?= $ticket['statut'] === 'ouvert' ? 'selected' : '' ?>>Ouvert</option>
                                    <option value="en_cours" <?= $ticket['statut'] === 'en_cours' ? 'selected' : '' ?>>En cours</option>
                                    <option value="résolu" <?= $ticket['statut'] === 'résolu' ? 'selected' : '' ?>>Résolu</option>
                                </select>
                            </div>
                            
                            <?php if ($ticket['statut'] !== 'résolu'): ?>
                            <div class="mb-3">
                                <label class="form-label">Commentaire de résolution</label>
                                <textarea name="comment" class="form-control" rows="3"></textarea>
                            </div>
                            <?php endif; ?>
                            
                            <button type="submit" class="btn btn-primary w-100 mb-2">
                                <i class="bi bi-save"></i> Mettre à jour
                            </button>
                        </form>

                        <a href="delete_ticket.php?id=<?= $ticket['ticket_id'] ?>" 
                           class="btn btn-danger w-100"
                           onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce ticket?')">
                            <i class="bi bi-trash"></i> Supprimer le ticket
                        </a>

                        <?php if ($ticket['assigned_to'] && $ticket['assigned_to'] !== $_SESSION['user_id']): ?>
                            <a href="assign_ticket.php?id=<?= $ticket['ticket_id'] ?>&to=<?= $_SESSION['user_id'] ?>" 
                               class="btn btn-warning w-100 mt-2">
                                <i class="bi bi-person-check"></i> Se l'assigner
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
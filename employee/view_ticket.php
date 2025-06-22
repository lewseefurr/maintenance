<?php
session_start();
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';

redirectIfNotLoggedIn();

if(!isset($_GET['id'])) {
    header("Location: tickets.php");
    exit;
}

$ticketId = $_GET['id'];
$userId = $_SESSION['user_id'];
$isTechnician = $_SESSION['role'] === 'technicien';

$stmt = $conn->prepare("SELECT t.*, e.nom as equipement_nom, u.username as createur 
                       FROM tickets t
                       JOIN equipements e ON t.equipement_id = e.equipement_id
                       JOIN users u ON t.user_id = u.user_id
                       WHERE t.ticket_id = ?");
$stmt->bind_param("i", $ticketId);
$stmt->execute();
$ticket = $stmt->get_result()->fetch_assoc();

if(!$ticket) {
    header("Location: tickets.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket #<?= $ticketId ?> | Maintenance</title>
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
        
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--background);
            color: var(--dark);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .main-navbar {
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            padding: 0.75rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .ticket-header {
            border-left: 4px solid;
            padding-left: 1rem;
        }
        
        .ticket-high { border-left-color: var(--danger); }
        .ticket-medium { border-left-color: var(--accent); }
        .ticket-low { border-left-color: var(--secondary); }
        
        .description-box {
            background-color: #f8f9fa;
            border-radius: 0.5rem;
            padding: 1.5rem;
            white-space: pre-wrap;
        }
        .main-footer {
            background-color: rgba(96, 93, 163, 0.9);
            color: white;
            padding: 2rem 0;
            margin-top: auto;
        }
        
        .footer-links {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .footer-links a {
            color: var(--gray);
            text-decoration: none;
            transition: color 0.2s;
        }
        
        .footer-links a:hover {
            color: white;
        }
        
        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 1.5rem;
            margin-top: 1.5rem;
            color: var(--gray);
            font-size: 0.875rem;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-fade-in {
            animation: fadeIn 0.6s ease-out forwards;
        }
        
        .delay-1 { animation-delay: 0.1s; }
        .delay-2 { animation-delay: 0.2s; }
        .delay-3 { animation-delay: 0.3s; }
    </style>
</head>
<body>
    <!-- Navigation principale -->
    <nav class="navbar navbar-expand-lg main-navbar">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-ticket-detailed"></i>
                <span>SupportTick</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-speedometer2"></i> Tableau de bord
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="tickets.php">
                            <i class="bi bi-ticket-detailed"></i> Tous les tickets
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="equipements.php">
                            <i class="bi bi-pc-display"></i> Équipements
                        </a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <span class="me-3 d-none d-lg-block text-muted">
                        <i class="bi bi-person-circle me-1"></i>
                        <?= htmlspecialchars($_SESSION['prenom'] ?? $_SESSION['username']) ?>
                    </span>
                    <a href="../auth/logout.php" class="btn btn-sm btn-outline-danger">
                        <i class="bi bi-box-arrow-right"></i> Déconnexion
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container py-5" style="flex: 1;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <a href="tickets.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
            <?php if($_SESSION['user_id'] == $ticket['user_id'] || $isTechnician): ?>
                <a href="edit_ticket.php?id=<?= $ticketId ?>" class="btn btn-warning">
                    <i class="bi bi-pencil"></i> Modifier
                </a>
            <?php endif; ?>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="ticket-header ticket-<?= $ticket['urgence'] ?> mb-4">
                    <h2>Ticket #<?= $ticketId ?>: <?= htmlspecialchars($ticket['title']) ?></h2>
                    <div class="d-flex gap-2 align-items-center">
                        <span class="badge bg-<?= $ticket['urgence'] === 'haute' ? 'danger' : ($ticket['urgence'] === 'moyenne' ? 'warning' : 'success') ?>">
                            <?= ucfirst($ticket['urgence']) ?>
                        </span>
                        <span class="badge bg-<?= $ticket['statut'] === 'résolu' ? 'success' : ($ticket['statut'] === 'en_cours' ? 'primary' : 'secondary') ?>">
                            <?= ucfirst(str_replace('_', ' ', $ticket['statut'])) ?>
                        </span>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5><i class="bi bi-person"></i> Créé par</h5>
                        <p><?= htmlspecialchars($ticket['createur']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <h5><i class="bi bi-calendar"></i> Date de création</h5>
                        <p><?= date('d/m/Y H:i', strtotime($ticket['date_creation'])) ?></p>
                    </div>
                    <div class="col-md-6">
                        <h5><i class="bi bi-pc-display"></i> Équipement</h5>
                        <p><?= htmlspecialchars($ticket['equipement_nom']) ?></p>
                    </div>
                    <?php if($ticket['statut'] === 'résolu'): ?>
                    <div class="col-md-6">
                        <h5><i class="bi bi-check-circle"></i> Résolu le</h5>
                        <p><?= date('d/m/Y H:i', strtotime($ticket['date_resolution'])) ?></p>
                    </div>
                    <?php endif; ?>
                </div>

                <h5><i class="bi bi-card-text"></i> Description</h5>
                <div class="description-box mb-4">
                    <?= nl2br(htmlspecialchars($ticket['description'])) ?>
                </div>
            </div>
        </div>
    </div>

    <footer class="main-footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <h5 class="text-white mb-4">SupportTick</h5>
                    <p class="text-muted">Votre outil de maintenance des équipements préféré.</p>
                </div>
                <div class="col-lg-2 col-md-6 mb-4 mb-md-0">
                    <h6 class="text-white mb-4">Navigation</h6>
                    <div class="footer-links">
                        <a href="dashboard.php">Tableau de bord</a>
                        <a href="tickets.php">Tous les tickets</a>
                        <a href="equipements.php">Équipements</a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6 mb-4 mb-md-0">
                    <h6 class="text-white mb-4">Support</h6>
                    <div class="footer-links">
                        <a href="aide.php">Centre d'aide</a>
                        <a href="contact.php">Contact</a>
                        <a href="faq.php">FAQ</a>
                    </div>
                </div>
                <div class="col-lg-4">
                    <h6 class="text-white mb-4">Contactez-nous</h6>
                    <ul class="list-unstyled text-muted">
                        <li class="mb-2"><i class="bi bi-envelope me-2"></i> support@technien.com</li>
                        <li class="mb-2"><i class="bi bi-telephone me-2"></i> +212 653 551 234</li>
                        <li><i class="bi bi-geo-alt me-2"></i> 25 Rue Moujahidin, 90005 Maroc</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom text-center text-md-start">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">
                    <div class="mb-2 mb-md-0">
                        &copy; <?= date('Y') ?> SupportTick. Tous droits réservés.
                    </div>
                    <div class="d-flex gap-3">
                        <a href="#" class="text-muted"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="text-muted"><i class="bi bi-twitter"></i></a>
                        <a href="#" class="text-muted"><i class="bi bi-linkedin"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
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

$stmt = $conn->prepare("SELECT * FROM tickets WHERE ticket_id = ?");
$stmt->bind_param("i", $ticketId);
$stmt->execute();
$ticket = $stmt->get_result()->fetch_assoc();

if(!$ticket) {
    header("Location: tickets.php");
    exit;
}

if($ticket['user_id'] != $userId && !$isTechnician) {
    header("Location: view_ticket.php?id=$ticketId");
    exit;
}

$equipments = $conn->query("SELECT * FROM equipements");

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $equipement_id = $_POST['equipement_id'];
    $urgence = $_POST['urgence'];
    $statut = $_POST['statut'] ?? $ticket['statut'];
    
    $stmt = $conn->prepare("UPDATE tickets SET title = ?, description = ?, equipement_id = ?, urgence = ?, statut = ? WHERE ticket_id = ?");
    $stmt->bind_param("ssissi", $title, $description, $equipement_id, $urgence, $statut, $ticketId);
    
    if($stmt->execute()) {
        $_SESSION['success'] = "Ticket mis à jour avec succès";
        header("Location: view_ticket.php?id=$ticketId");
        exit;
    } else {
        $error = "Erreur lors de la mise à jour du ticket";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Ticket #<?= $ticketId ?> | Maintenance</title>
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
        
        .form-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .form-header {
            border-bottom: 2px solid #eee;
            padding-bottom: 1rem;
            margin-bottom: 2rem;
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
        <div class="form-container">
            <div class="form-header">
                <h2><i class="bi bi-pencil"></i> Modifier Ticket #<?= $ticketId ?></h2>
                <a href="view_ticket.php?id=<?= $ticketId ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Retour
                </a>
            </div>

            <?php if(isset($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label for="title" class="form-label">Titre</label>
                    <input type="text" class="form-control" id="title" name="title" 
                           value="<?= htmlspecialchars($ticket['title']) ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" 
                              rows="5" required><?= htmlspecialchars($ticket['description']) ?></textarea>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="equipement_id" class="form-label">Équipement</label>
                        <select class="form-select" id="equipement_id" name="equipement_id" required>
                            <?php while($equipment = $equipments->fetch_assoc()): ?>
                                <option value="<?= $equipment['equipement_id'] ?>" 
                                    <?= $equipment['equipement_id'] == $ticket['equipement_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($equipment['nom']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="urgence" class="form-label">Urgence</label>
                        <select class="form-select" id="urgence" name="urgence" required>
                            <option value="haute" <?= $ticket['urgence'] === 'haute' ? 'selected' : '' ?>>Haute</option>
                            <option value="moyenne" <?= $ticket['urgence'] === 'moyenne' ? 'selected' : '' ?>>Moyenne</option>
                            <option value="basse" <?= $ticket['urgence'] === 'basse' ? 'selected' : '' ?>>Basse</option>
                        </select>
                    </div>
                </div>
                
                <?php if($isTechnician): ?>
                <div class="mb-3">
                    <label for="statut" class="form-label">Statut</label>
                    <select class="form-select" id="statut" name="statut">
                        <option value="ouvert" <?= $ticket['statut'] === 'ouvert' ? 'selected' : '' ?>>Ouvert</option>
                        <option value="en_cours" <?= $ticket['statut'] === 'en_cours' ? 'selected' : '' ?>>En cours</option>
                        <option value="résolu" <?= $ticket['statut'] === 'résolu' ? 'selected' : '' ?>>Résolu</option>
                    </select>
                </div>
                <?php endif; ?>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Enregistrer
                    </button>
                </div>
            </form>
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
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
        
        .navbar-brand {
            font-weight: 700;
            color: var(--primary);
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .navbar-brand i {
            font-size: 1.5rem;
        }
        
        .nav-link {
            font-weight: 500;
            color: var(--dark);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all 0.2s;
        }
        
        .nav-link:hover, .nav-link.active {
            color: var(--primary);
            background-color: rgba(79, 70, 229, 0.1);
        }
        
        .nav-link i {
            margin-right: 0.5rem;
        }
        
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        
        .card-header {
            background-color: var(--primary);
            color: white;
            border-radius: 12px 12px 0 0 !important;
            padding: 1.25rem 1.5rem;
        }
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        .btn-outline-secondary {
            transition: all 0.3s ease;
        }
        
        .btn-outline-secondary:hover {
            transform: translateY(-2px);
        }
        
        textarea {
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            min-height: 120px;
        }
        
        textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(79, 70, 229, 0.25);
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .main-footer {
            background-color: rgba(37, 84, 122, 0.8);
            color: white;
            padding: 3rem 0 1.5rem;
            margin-top: auto;
        }
        
        .footer-links {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
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
            margin-top: 2rem;
            color: var(--gray);
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg main-navbar">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-tools"></i>
                <span>TechDashboard</span>
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
                        <a class="nav-link" href="tickets.php">
                            <i class="bi bi-ticket-detailed"></i> Tickets
                        </a>
                    </li>
                    
                </ul>
                <div class="d-flex align-items-center">
                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" id="userDropdown" data-bs-toggle="dropdown">
                            <div class="me-2 d-none d-lg-block text-end">
                                <div class="fw-medium"><?= htmlspecialchars($_SESSION['nom'] ?? $_SESSION['username']) ?></div>
                                <small class="text-muted">Technicien</small>
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

    <div class="container py-5 fade-in">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0 d-flex align-items-center">
                            <i class="bi bi-check-circle me-2"></i>
                            Résoudre le ticket #<?= $ticket['ticket_id'] ?>
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <h5 class="fw-bold"><?= htmlspecialchars($ticket['title']) ?></h5>
                            <div class="d-flex align-items-center gap-2 mt-2">
                                <span class="badge bg-light text-dark">
                                    <i class="bi bi-pc-display me-1"></i>
                                    <?= htmlspecialchars($ticket['equipement_nom']) ?>
                                </span>
                                <span class="badge bg-secondary">
                                    <i class="bi bi-calendar me-1"></i>
                                    <?= date('d/m/Y', strtotime($ticket['date_creation'])) ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <div class="alert alert-light">
                                <h6 class="fw-bold mb-2">Description du problème :</h6>
                                <p class="mb-0"><?= nl2br(htmlspecialchars($ticket['description'])) ?></p>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <form method="POST" action="resolve_ticket.php" class="needs-validation" novalidate>
                            <input type="hidden" name="ticket_id" value="<?= $ticket['ticket_id'] ?>">
                            
                            <div class="mb-4">
                                <label for="resolution_comment" class="form-label fw-bold">
                                    <i class="bi bi-chat-left-text me-1"></i>
                                    Commentaire de résolution (facultatif)
                                </label>
                                <textarea class="form-control" id="resolution_comment" name="resolution_comment" 
                                          rows="5" placeholder="Décrivez comment vous avez résolu le problème..."></textarea>
                                <div class="form-text">Ce commentaire sera visible par le demandeur.</div>
                            </div>
                            
                            <div class="d-flex justify-content-between pt-3">
                                <a href="dashboard.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left me-1"></i> Retour
                                </a>
                                <button type="submit" class="btn btn-primary px-4">
                                    <i class="bi bi-check-circle me-1"></i> Confirmer la résolution
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="main-footer">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <h5 class="text-white mb-4">TechDashboard</h5>
                    <p class="text-muted">Plateforme de gestion des tickets pour les techniciens. Simplifiez votre workflow et gérez efficacement les demandes.</p>
                </div>
                <div class="col-lg-2 col-md-4">
                    <h6 class="text-white mb-4">Navigation</h6>
                    <div class="footer-links">
                        <a href="dashboard.php">Tableau de bord</a>
                        <a href="tickets.php">Tickets</a>
                        
                    </div>
                </div>
                <div class="col-lg-2 col-md-4">
                    <h6 class="text-white mb-4">Support</h6>
                    <div class="footer-links">
                        <a href="help.php">Centre d'aide</a>
                        <a href="contact.php">Contact</a>
                        <a href="faq.php">FAQ</a>
                    </div>
                </div>
                <div class="col-lg-4 col-md-4">
                    <h6 class="text-white mb-4">Contact</h6>
                    <ul class="list-unstyled text-muted">
                        <li class="mb-2 d-flex align-items-center gap-2">
                            <i class="bi bi-envelope"></i> support@entreprise.com
                        </li>
                        <li class="mb-2 d-flex align-items-center gap-2">
                            <i class="bi bi-telephone"></i> +212 653 551 234
                        </li>
                        <li class="d-flex align-items-center gap-2">
                            <i class="bi bi-geo-alt"></i> 25 Rue Moujahidin, 90005 Maroc
                        </li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom text-center text-md-start">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">
                    <div class="mb-2 mb-md-0">
                        &copy; <?= date('Y') ?> TechDashboard. Tous droits réservés.
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
    <script>
        // Animation for form elements
        document.querySelectorAll('input, textarea, select').forEach(el => {
            el.addEventListener('focus', () => {
                el.parentElement.classList.add('animate__animated', 'animate__pulse');
            });
            
            el.addEventListener('blur', () => {
                el.parentElement.classList.remove('animate__animated', 'animate__pulse');
            });
        });
        
        // Form validation
        (() => {
            'use strict'
            const forms = document.querySelectorAll('.needs-validation')
            
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    
                    form.classList.add('was-validated')
                }, false)
            })
        })()
    </script>
</body>
</html>
<?php
} else {
    header("Location: dashboard.php");
    exit();
}
?>
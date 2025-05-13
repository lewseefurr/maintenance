<?php
session_start();
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';

redirectIfNotLoggedIn();

$userId = $_SESSION['user_id'];

$query = "
    SELECT 
        t.ticket_id,
        t.title,
        e.nom AS equipement_nom,
        t.urgence,
        t.description,
        t.statut,
        DATE_FORMAT(t.date_creation, '%d/%m/%Y %H:%i') AS date_creation,
        DATE_FORMAT(t.date_resolution, '%d/%m/%Y %H:%i') AS date_resolution,
        u.nom AS user_nom
        
    FROM tickets t
    JOIN equipements e ON t.equipement_id = e.equipement_id
    JOIN users u ON t.user_id = u.user_id
    WHERE t.user_id = ?
    ORDER BY t.date_creation DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$stats = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT 
        SUM(CASE WHEN statut = 'ouvert' THEN 1 ELSE 0 END) AS open_count,
        SUM(CASE WHEN statut = 'en_cours' THEN 1 ELSE 0 END) AS in_progress_count,
        SUM(CASE WHEN statut = 'résolu' AND date_resolution >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) AS resolved_recent_count,
        SUM(CASE WHEN statut = 'résolu' THEN 1 ELSE 0 END) AS resolved_total_count
    FROM tickets
    WHERE user_id = $userId
")) ?: ['open_count' => 0, 'in_progress_count' => 0, 'resolved_recent_count' => 0, 'resolved_total_count' => 0];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord | <?= htmlspecialchars($_SESSION['username']) ?></title>
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
            --header-gradient: linear-gradient(135deg, #4f46e5, #7c3aed);
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
        
        .dashboard-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            padding: 3rem 0 4rem;
            position: relative;
            overflow: hidden;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .dashboard-header::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 50px;
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1200 120' preserveAspectRatio='none'%3E%3Cpath d='M0,0V46.29c47.79,22.2,103.59,32.17,158,28,70.36-5.37,136.33-33.31,206.8-37.5C438.64,32.43,512.34,53.67,583,72.05c69.27,18,138.3,24.88,209.4,13.08,36.15-6,69.85-17.84,104.45-29.34C989.49,25,1113-14.29,1200,52.47V0Z' opacity='.25' fill='%23f1f5f9'%3E%3C/path%3E%3Cpath d='M0,0V15.81C13,36.92,27.64,56.86,47.69,72.05,99.41,111.27,165,111,224.58,91.58c31.15-10.15,60.09-26.07,89.67-39.8,40.92-19,84.73-46,130.83-49.67,36.26-2.85,70.9,9.42,98.6,31.56,31.77,25.39,62.32,62,103.63,73,40.44,10.79,81.35-6.69,119.13-24.28s75.16-39,116.92-43.05c59.73-5.85,113.28,22.88,168.9,38.84,30.2,8.66,59,6.17,87.09-7.5,22.43-10.89,48-26.93,60.65-49.24V0Z' opacity='.5' fill='%23f1f5f9'%3E%3C/path%3E%3Cpath d='M0,0V5.63C149.93,59,314.09,71.32,475.83,42.57c43-7.64,84.23-20.12,127.61-26.46,59-8.63,112.48,12.24,165.56,35.4C827.93,77.22,886,95.24,951.2,90c86.53-7,172.46-45.71,248.8-84.81V0Z' fill='%23f1f5f9'%3E%3C/path%3E%3C/svg%3E");
            background-size: cover;
            transform: rotate(180deg);
        }
        
        .header-content {
            position: relative;
            z-index: 2;
        }
        
        .greeting {
            font-weight: 700;
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
        }
        
        .user-name {
            font-weight: 600;
            font-size: 1.75rem;
            margin-bottom: 0;
        }
        
        .stat-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            overflow: hidden;
            position: relative;
            z-index: 1;
            background: white;
        }
        
        .stat-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-light), var(--primary));
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card.open::before { background: var(--accent); }
        .stat-card.progress::before { background: var(--primary); }
        .stat-card.resolved::before { background: var(--secondary); }
        
        .stat-number {
            font-weight: 700;
            font-size: 1.75rem;
            color: var(--dark);
        }
        
        .stat-label {
            color: var(--gray);
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .main-content {
            flex: 1;
            padding-bottom: 3rem;
        }
        
        .tickets-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .table-header {
            background-color: var(--primary);
            color: white;
        }
        
        .table th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            color: var(--gray);
            border-bottom-width: 2px;
        }
        
        .ticket-row:hover {
            background-color: #f8fafc;
        }
        
        .badge-urgency {
            padding: 4px 10px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge-low { background-color: #ecfdf5; color: #059669; }
        .badge-medium { background-color: #fef3c7; color: #b45309; }
        .badge-high { background-color: #fee2e2; color: #b91c1c; }
        
        .badge-status {
            padding: 4px 10px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.75rem;
        }
        
        .badge-open { background-color: #dbeafe; color: #1e40af; }
        .badge-progress { background-color: #e0e7ff; color: #4338ca; }
        .badge-resolved { background-color: #dcfce7; color: #166534; }
        
        .btn-new-ticket {
            background: white;
            color: var(--primary);
            border: none;
            padding: 10px 20px;
            font-weight: 600;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-new-ticket:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            color: var(--primary-dark);
        }
        
        .btn-logout {
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.2);
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-logout:hover {
            background: rgba(255,255,255,0.25);
            color: white;
        }
        
        .floating-btn {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: all 0.3s;
            z-index: 100;
        }
        
        .floating-btn:hover {
            transform: translateY(-3px) scale(1.05);
            color: white;
            background: var(--primary-dark);
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
                        <a class="nav-link active" href="dashboard.php">
                            <i class="bi bi-speedometer2"></i> Tableau de bord
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="tickets.php">
                            <i class="bi bi-ticket-detailed"></i> Mes tickets
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="equipements.php">
                            <i class="bi bi-pc-display"></i> Équipements
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profil.php">
                            <i class="bi bi-person-circle"></i> Mon profil
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

    <!-- Header moderne -->
    <header class="dashboard-header">
        <div class="container header-content">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="greeting">Bonjour,</p>
                    <h1 class="user-name"><?= htmlspecialchars($_SESSION['prenom'] ?? $_SESSION['username']) ?></h1>
                </div>
                <div class="d-flex gap-3">
                    <a href="create_ticket.php" class="btn btn-new-ticket">
                        <i class="bi bi-plus-lg"></i> Nouveau ticket
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main class="container main-content mb-5">
        <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show mb-4 animate-fade-in">
            <div class="d-flex align-items-center">
                <i class="bi bi-check-circle-fill me-2"></i>
                <div><?= $_SESSION['success'] ?></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="stat-card open h-100 p-3 animate-fade-in delay-1">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="bi bi-exclamation-circle text-warning" style="font-size: 1.5rem;"></i>
                        </div>
                        <div>
                            <div class="stat-number"><?= $stats['open_count'] ?></div>
                            <div class="stat-label">Tickets ouverts</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card progress h-100 p-3 animate-fade-in delay-2">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="bi bi-arrow-repeat text-primary" style="font-size: 1.5rem;"></i>
                        </div>
                        <div>
                            <div class="stat-number"><?= $stats['in_progress_count'] ?></div>
                            <div class="stat-label">En cours de traitement</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card resolved h-100 p-3 animate-fade-in delay-3">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="bi bi-check-circle text-success" style="font-size: 1.5rem;"></i>
                        </div>
                        <div>
                            <div class="stat-number"><?= $stats['resolved_recent_count'] ?></div>
                            <div class="stat-label">Résolus (7 derniers jours)</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tickets-table animate-fade-in">
            <div class="card border-0">
                <div class="card-header bg-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-ticket-detailed me-2"></i>Mes tickets récents</h5>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="filterDropdown" data-bs-toggle="dropdown">
                                <i class="bi bi-funnel"></i> Filtrer
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="?filter=all">Tous les tickets</a></li>
                                <li><a class="dropdown-item" href="?filter=open">Ouverts</a></li>
                                <li><a class="dropdown-item" href="?filter=progress">En cours</a></li>
                                <li><a class="dropdown-item" href="?filter=resolved">Résolus</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Titre</th>
                                    <th>Équipement</th>
                                    <th>Urgence</th>
                                    <th>Statut</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result->num_rows > 0): ?>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr class="ticket-row align-middle">
                                        <td class="fw-bold">#<?= $row['ticket_id'] ?></td>
                                        <td><?= htmlspecialchars($row['title']) ?></td>
                                        <td><?= htmlspecialchars($row['equipement_nom']) ?></td>
                                        <td>
                                            <span class="badge-urgency badge-<?= $row['urgence'] ?>">
                                                <?= ucfirst($row['urgence']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge-status badge-<?= str_replace('_', '-', strtolower($row['statut'])) ?>">
                                                <?= ucfirst(str_replace('_', ' ', $row['statut'])) ?>
                                            </span>
                                        </td>
                                        <td><?= $row['date_creation'] ?></td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <a href="view_ticket.php?id=<?= $row['ticket_id'] ?>" class="btn btn-sm btn-outline-primary" title="Voir détails">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <?php if ($row['statut'] == 'ouvert'): ?>
                                                <a href="edit_ticket.php?id=<?= $row['ticket_id'] ?>" class="btn btn-sm btn-outline-warning" title="Modifier">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-5">
                                            <i class="bi bi-inbox" style="font-size: 3rem; color: #e2e8f0;"></i>
                                            <h5 class="mt-3">Aucun ticket trouvé</h5>
                                            <p class="text-muted">Créez votre premier ticket pour commencer</p>
                                            <a href="create_ticket.php" class="btn btn-primary mt-2">
                                                <i class="bi bi-plus-circle"></i> Créer un ticket
                                            </a>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="main-footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <h5 class="text-white mb-4">SupportTick</h5>
                    <p class="text-muted">Votre outil de maintenance des équipements préferée.</p>
                </div>
                <div class="col-lg-2 col-md-6 mb-4 mb-md-0">
                    <h6 class="text-white mb-4">Navigation</h6>
                    <div class="footer-links">
                        <a href="dashboard.php">Tableau de bord</a>
                        <a href="tickets.php">Mes tickets</a>
                        <a href="equipements.php">Équipements</a>
                        <a href="profil.php">Mon profil</a>
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

    <a href="create_ticket.php" class="floating-btn d-lg-none">
        <i class="bi bi-plus-lg"></i>
    </a>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const animatedElements = document.querySelectorAll('.animate-fade-in');
            
            animatedElements.forEach((el, index) => {
                
            });
            
           
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    
                    const targetId = this.getAttribute('href');
                    if (targetId === '#') return;
                    
                    const targetElement = document.querySelector(targetId);
                    if (targetElement) {
                        targetElement.scrollIntoView({
                            behavior: 'smooth'
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>
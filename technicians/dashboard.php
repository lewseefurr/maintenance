<?php
session_start();
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';

redirectIfNotLoggedIn();
redirectIfNotTechnician();

$techId = $_SESSION['user_id'];

$result = mysqli_query($conn, 
    "SELECT 
        t.ticket_id,
        t.title,
        t.description,
        t.urgence,
        t.statut,
        t.date_creation,
        t.equipement_id,
        t.assigned_to,
        u.username AS createur,
        e.nom AS equipement_nom
     FROM tickets t
     JOIN users u ON t.user_id = u.user_id 
     JOIN equipements e ON t.equipement_id = e.equipement_id  
     WHERE t.statut != 'résolu' 
     ORDER BY t.date_creation DESC"  
);

if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}

$stats_query = mysqli_query($conn, "
    SELECT 
        SUM(CASE WHEN statut = 'ouvert' AND (assigned_to IS NULL OR assigned_to = 0) THEN 1 ELSE 0 END) AS open_count,
        SUM(CASE WHEN statut = 'en_cours' AND assigned_to = $techId THEN 1 ELSE 0 END) AS in_progress_count,
        SUM(CASE WHEN statut = 'résolu' AND assigned_to = $techId THEN 1 ELSE 0 END) AS resolved_count
    FROM tickets");

$stats = mysqli_fetch_assoc($stats_query) ?? [
    'open_count' => 0,
    'in_progress_count' => 0,
    'resolved_count' => 0
];

$assigned_count = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT COUNT(*) as count FROM tickets 
     WHERE assigned_to = $techId AND statut != 'résolu'"))['count'] ?? 0;

$resolved_count = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT COUNT(*) as count FROM tickets 
     WHERE assigned_to = $techId 
     AND statut = 'résolu' 
     AND date_resolution >= DATE_SUB(NOW(), INTERVAL 7 DAY)"))['count'] ?? 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord Technicien | <?= htmlspecialchars($_SESSION['username']) ?></title>
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
        
        .tech-header {
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('techh.jpg') no-repeat center center;
            background-size: cover;
            color: white;
            padding: 3rem 0 4rem;
            position: relative;
            overflow: hidden;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .tech-header::after {
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
        
        .main-content {
            flex: 1;
            padding-bottom: 3rem;
        }
        
        .section-title {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .section-title::after {
            content: "";
            flex: 1;
            height: 1px;
            background: linear-gradient(90deg, var(--primary), transparent);
            margin-left: 1rem;
        }
        
        .tickets-assigned-container {
            position: relative;
            transition: all 0.3s ease;
        }
        
        .tickets-assigned-toggle {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 10;
        }
        
        .tickets-assigned {
            background: rgba(255,255,255,0.15);
            border-radius: 12px;
            padding: 1.5rem;
            max-height: 400px;
            overflow-y: auto;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
            transition: all 0.3s ease;
        }
        
        .tickets-assigned.collapsed {
            max-height: 60px;
            overflow: hidden;
        }
        
        .ticket-item {
            background: rgba(255,255,255,0.1);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            transition: all 0.2s;
            border-left: 3px solid var(--primary);
            cursor: pointer;
        }
        
        .ticket-item:hover {
            background: rgba(255,255,255,0.2);
            transform: translateX(5px);
        }
        
        .ticket-title {
            font-weight: 600;
            font-size: 0.95rem;
        }
        
        .ticket-meta {
            font-size: 0.8rem;
            opacity: 0.9;
        }
        
        .ticket-card {
            border-radius: 12px;
            transition: all 0.3s;
            margin-bottom: 1rem;
            border-left: 4px solid;
            padding: 1.25rem;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .ticket-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        
        .ticket-high { border-left-color: var(--danger); }
        .ticket-medium { border-left-color: var(--accent); }
        .ticket-low { border-left-color: var(--secondary); }
        
        .badge-urgency {
            padding: 5px 12px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
        }
        
        .badge-high { background-color: #fee2e2; color: #b91c1c; }
        .badge-medium { background-color: #fef3c7; color: #b45309; }
        .badge-low { background-color: #dcfce7; color: #166534; }
        
        .action-btn {
            border-radius: 8px;
            padding: 6px 15px;
            font-weight: 500;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .toggle-description {
            font-size: 0.8rem;
            padding: 0.3rem 0.75rem;
        }
        
        .ticket-description .card-body {
            font-size: 0.9rem;
            white-space: pre-wrap;
            background: var(--light);
            border-radius: 8px;
        }
        
        .stats-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            height: 100%;
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
                        <a class="nav-link active" href="dashboard.php">
                            <i class="bi bi-speedometer2"></i> Tableau de bord
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="tickets.php">
                            <i class="bi bi-ticket-detailed"></i> Gestion des tickets
                        </a>
                    </li>
                    <!-- <li class="nav-item">
                        <a class="nav-link" href="equipements.php">
                            <i class="bi bi-pc-display"></i> Équipements
                        </a>
                    </li> -->
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">
                            <i class="bi bi-graph-up"></i> Rapports
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
                            <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i> Mon profil</a></li>
                            <li><a class="dropdown-item" href="settings.php"><i class="bi bi-gear me-2"></i> Paramètres</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="../auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i> Déconnexion</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <header class="tech-header">
        <div class="container header-content">
            <div class="row">
                <div class="col-lg-8">
                    <div class="d-flex flex-column h-100 justify-content-center">
                        <p class="greeting">Bonjour,</p>
                        <h1 class="user-name"><?= htmlspecialchars($_SESSION['nom'] ?? $_SESSION['username']) ?></h1>
                        <p class="mt-2">Tableau de bord technicien - Gestion des tickets</p>
                    </div>
                </div>
                
                <div class="col-lg-4 mt-4 mt-lg-0">
                    <div class="tickets-assigned-container">
                        <button class="tickets-assigned-toggle" id="toggleAssignedTickets">
                            <i class="bi bi-dash"></i>
                        </button>
                        <div class="tickets-assigned" id="assignedTickets">
                            <h5 class="d-flex align-items-center gap-2">
                                <i class="bi bi-list-check"></i> 
                                <span>Mes Tickets Assignés <span class="badge bg-white text-primary"><?= $assigned_count ?></span></span>
                            </h5>
                            
                            <?php 
                            $assignedTickets = mysqli_query($conn, 
                                "SELECT t.*, e.nom as equipement_nom, u.username as createur
                                 FROM tickets t
                                 JOIN equipements e ON t.equipement_id = e.equipement_id
                                 JOIN users u ON t.user_id = u.user_id
                                 WHERE t.assigned_to = $techId AND t.statut != 'résolu'
                                 ORDER BY t.urgence DESC, t.date_creation DESC");
                            
                            if (mysqli_num_rows($assignedTickets) > 0): ?>
                                <div class="mt-3">
                                    <?php while ($ticket = mysqli_fetch_assoc($assignedTickets)): ?>
                                    <div class="ticket-item" onclick="window.location.href='view_ticket.php?id=<?= $ticket['ticket_id'] ?>'">
                                        <div class="ticket-title">#<?= $ticket['ticket_id'] ?> - <?= htmlspecialchars($ticket['title']) ?></div>
                                        <div class="ticket-meta mt-1 d-flex align-items-center gap-2">
                                            <span class="badge badge-<?= $ticket['urgence'] ?>">
                                                <?= ucfirst($ticket['urgence']) ?>
                                            </span>
                                            <span><?= htmlspecialchars($ticket['equipement_nom']) ?></span>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-3" style="opacity: 0.8;">
                                    <i class="bi bi-check-circle" style="font-size: 2rem;"></i>
                                    <p class="mt-2 mb-0">Aucun ticket assigné</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="container main-content mb-5">
        <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show mb-4">
            <div class="d-flex align-items-center">
                <i class="bi bi-check-circle-fill me-2"></i>
                <div><?= $_SESSION['success'] ?></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-8">
                <h3 class="section-title">
                    <i class="bi bi-list-task text-primary"></i>
                    <span>Tickets à Traiter</span>
                </h3>
                
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <?php if (mysqli_num_rows($result) > 0): ?>
                        <div class="list-group list-group-flush">
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <div class="list-group-item border-0 px-0 py-3 ticket-card ticket-<?= $row['urgence'] ?>">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div style="flex: 1;">
                                        <h6 class="mb-1 fw-bold">#<?= $row['ticket_id'] ?> - <?= htmlspecialchars($row['title']) ?></h6>
                                        <div class="d-flex align-items-center gap-2 mt-1">
                                            <small class="text-muted d-flex align-items-center gap-1">
                                                <i class="bi bi-person"></i> <?= htmlspecialchars($row['createur']) ?>
                                            </small>
                                            <small class="text-muted d-flex align-items-center gap-1">
                                                <i class="bi bi-clock"></i> <?= $row['date_creation'] ?>
                                            </small>
                                        </div>
                                        <div class="ticket-description mt-2">
                                            <button class="btn btn-sm btn-outline-secondary toggle-description" 
                                                    data-bs-toggle="collapse" 
                                                    data-bs-target="#desc-<?= $row['ticket_id'] ?>">
                                                <i class="bi bi-eye"></i> Voir description
                                            </button>
                                            <div class="collapse mt-2" id="desc-<?= $row['ticket_id'] ?>">
                                                <div class="card card-body">
                                                    <?= nl2br(htmlspecialchars($row['description'])) ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-2 d-flex gap-2">
                                            <span class="badge badge-<?= $row['urgence'] ?>">
                                                <?= ucfirst($row['urgence']) ?>
                                            </span>
                                            <span class="badge bg-light text-dark d-flex align-items-center gap-1">
                                                <i class="bi bi-pc-display"></i> <?= htmlspecialchars($row['equipement_nom']) ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="d-flex flex-column gap-2 ms-3">
                                        <?php if (empty($row['assigned_to'])): ?>
                                            <a href="claim_ticket.php?id=<?= $row['ticket_id'] ?>" 
                                               class="action-btn btn btn-success">
                                               <i class="bi bi-check-circle"></i> Prendre
                                            </a>
                                        <?php elseif ($row['assigned_to'] == $_SESSION['user_id']): ?>
                                            <a href="resolve_ticket.php?id=<?= $row['ticket_id'] ?>" 
                                               class="action-btn btn btn-primary">
                                               <i class="bi bi-check-circle"></i> Résoudre
                                            </a>
                                            <a href="delete_ticket.php?id=<?= $row['ticket_id'] ?>" 
                                               class="action-btn btn btn-outline-danger"
                                               onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce ticket?')">
                                               <i class="bi bi-trash"></i> Supprimer
                                            </a>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Assigné</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-check-circle" style="font-size: 3rem; color: var(--primary);"></i>
                            <h5 class="mt-3">Aucun ticket en attente</h5>
                            <p class="text-muted">Tous les tickets ont été traités</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-graph-up"></i> Statistiques</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="statsChart" height="200"></canvas>
                        <div class="mt-3">
                            <div class="d-flex justify-content-between">
                                <div class="text-center">
                                    <h6><?= $stats['open_count'] ?></h6>
                                    <small class="text-muted">Ouverts</small>
                                </div>
                                <div class="text-center">
                                    <h6><?= $stats['in_progress_count'] ?></h6>
                                    <small class="text-muted">En cours</small>
                                </div>
                                <div class="text-center">
                                    <h6><?= $stats['resolved_count'] ?></h6>
                                    <small class="text-muted">Résolus</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
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
                        <!-- <a href="equipements.php">Équipements</a> -->
                        <a href="reports.php">Rapports</a>
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

    <!-- Bouton flottant -->
    <a href="create_ticket.php" class="floating-btn d-lg-none">
        <i class="bi bi-plus-lg"></i>
    </a>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>

        const ctx = document.getElementById('statsChart').getContext('2d');
        const myChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Ouverts', 'En cours', 'Résolus'],
                datasets: [{
                    data: [
                        <?= $stats['open_count'] ?>,
                        <?= $stats['in_progress_count'] ?>,
                        <?= $stats['resolved_count'] ?>
                    ],
                    backgroundColor: ['#ff7675', '#fdcb6e', '#55efc4']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.label}: ${context.raw} ticket(s)`;
                            }
                        }
                    }
                }
            }
        });


        document.querySelectorAll('[href^="claim_ticket.php"]').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                fetch(this.href)
                    .then(() => {
                        location.reload(); 
                    });
            });
        });

        const toggleButton = document.getElementById('toggleAssignedTickets');
        const assignedTickets = document.getElementById('assignedTickets');
        let isCollapsed = false;
        
        toggleButton.addEventListener('click', function(e) {
            e.stopPropagation();
            isCollapsed = !isCollapsed;
            
            if (isCollapsed) {
                assignedTickets.classList.add('collapsed');
                toggleButton.innerHTML = '<i class="bi bi-plus"></i>';
            } else {
                assignedTickets.classList.remove('collapsed');
                toggleButton.innerHTML = '<i class="bi bi-dash"></i>';
            }
        });
    </script>
</body>
</html>
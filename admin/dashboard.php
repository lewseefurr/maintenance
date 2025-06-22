<?php
session_start();
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';

redirectIfNotLoggedIn();
redirectIfNotAdmin();

$tickets = mysqli_query($conn, 
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
        e.nom AS equipement_nom,
        tech.username AS technicien
     FROM tickets t
     JOIN users u ON t.user_id = u.user_id 
     LEFT JOIN equipements e ON t.equipement_id = e.equipement_id
     LEFT JOIN users tech ON t.assigned_to = tech.user_id
     ORDER BY t.date_creation DESC");

$users = mysqli_query($conn, "SELECT * FROM users ORDER BY role, username");

$equipments = mysqli_query($conn, "SELECT * FROM equipements ORDER BY statut, nom");

$stats = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT 
        COUNT(*) as total_tickets,
        SUM(CASE WHEN statut = 'ouvert' THEN 1 ELSE 0 END) as open_tickets,
        SUM(CASE WHEN statut = 'en_cours' THEN 1 ELSE 0 END) as in_progress_tickets,
        SUM(CASE WHEN statut = 'résolu' THEN 1 ELSE 0 END) as resolved_tickets,
        (SELECT COUNT(*) FROM users) as total_users,
        (SELECT COUNT(*) FROM equipements) as total_equipments
     FROM tickets"));
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

    <header class="admin-header">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <h1 class="display-4">Tableau de bord Administrateur</h1>
                    <p class="lead">Gestion complète du système de maintenance</p>
                </div>
            </div>
        </div>
    </header>

    <main class="container mb-5">
        <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show mb-4">
            <?= $_SESSION['success'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card primary">
                    <h5>Tickets Totaux</h5>
                    <h2><?= $stats['total_tickets'] ?></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card danger">
                    <h5>Tickets Ouverts</h5>
                    <h2><?= $stats['open_tickets'] ?></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card warning">
                    <h5>Tickets en Cours</h5>
                    <h2><?= $stats['in_progress_tickets'] ?></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card success">
                    <h5>Tickets Résolus</h5>
                    <h2><?= $stats['resolved_tickets'] ?></h2>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-ticket-detailed"></i> Derniers Tickets</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Titre</th>
                                        <th>Créateur</th>
                                        <th>Technicien</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($ticket = mysqli_fetch_assoc($tickets)): ?>
                                    <tr>
                                        <td>#<?= $ticket['ticket_id'] ?></td>
                                        <td><?= htmlspecialchars($ticket['title']) ?></td>
                                        <td><?= htmlspecialchars($ticket['createur']) ?></td>
                                        <td><?= $ticket['technicien'] ?? 'Non assigné' ?></td>
                                        <td>
                                            <span class="badge bg-<?= 
                                                $ticket['statut'] == 'ouvert' ? 'danger' : 
                                                ($ticket['statut'] == 'en_cours' ? 'warning' : 'success') 
                                            ?>">
                                                <?= ucfirst($ticket['statut']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="admin_view_ticket.php?id=<?= $ticket['ticket_id'] ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <?php if ($ticket['statut'] === 'résolu'): ?>
                                                <a href="delete_ticket.php?id=<?= $ticket['ticket_id'] ?>" 
                                                class="btn btn-sm btn-danger"
                                                onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce ticket?')">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-people"></i> Utilisateurs</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Nom</th>
                                        <th>Rôle</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($user = mysqli_fetch_assoc($users)): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($user['nom']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= 
                                                $user['role'] == 'admin' ? 'primary' : 
                                                ($user['role'] == 'technicien' ? 'warning' : 'secondary') 
                                            ?>">
                                                <?= ucfirst($user['role']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="admin_edit_user.php?id=<?= $user['user_id'] ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <a href="admin_add_user.php" class="btn btn-secondary w-100 mt-2">
                            <i class="bi bi-plus"></i> Ajouter un utilisateur
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
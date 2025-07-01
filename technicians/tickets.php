<?php
session_start();
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';

redirectIfNotLoggedIn();
redirectIfNotTechnician();

$techId = $_SESSION['user_id'];

$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$urgency = $_GET['urgency'] ?? '';

$query = "SELECT 
            t.ticket_id,
            t.title,
            t.description,
            t.urgence,
            t.statut,
            t.date_creation,
            t.date_resolution,
            t.resolution_comment,
            t.equipement_id,
            t.assigned_to,
            u.username AS createur,
            u.nom AS createur_nom,
            e.nom AS equipement_nom,
            tech.username AS technicien_assign
          FROM tickets t
          JOIN users u ON t.user_id = u.user_id 
          LEFT JOIN equipements e ON t.equipement_id = e.equipement_id
          LEFT JOIN users tech ON t.assigned_to = tech.user_id
          WHERE 1=1";

$params = [];
$types = '';

if (!empty($search)) {
    $query .= " AND (t.title LIKE ? OR t.description LIKE ? OR e.nom LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
    $types .= 'sss';
}

if (!empty($status) && in_array($status, ['ouvert', 'en_cours', 'résolu'])) {
    $query .= " AND t.statut = ?";
    $params[] = $status;
    $types .= 's';
}

if (!empty($urgency) && in_array($urgency, ['basse', 'moyenne', 'haute'])) {
    $query .= " AND t.urgence = ?";
    $params[] = $urgency;
    $types .= 's';
}

$sort = $_GET['sort'] ?? 'date_creation';
$order = $_GET['order'] ?? 'DESC';
$validSorts = ['ticket_id', 'title', 'urgence', 'statut', 'date_creation', 'date_resolution'];
$sort = in_array($sort, $validSorts) ? $sort : 'date_creation';
$order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
$query .= " ORDER BY $sort $order";

$perPage = 10;
$page = max(1, $_GET['page'] ?? 1);
$offset = ($page - 1) * $perPage;
$totalRows = 0;
$totalPages = 1;

$countQuery = str_replace('SELECT t.ticket_id, t.title', 'SELECT COUNT(*) as total', $query);
$stmt = mysqli_prepare($conn, $countQuery);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
if (mysqli_stmt_execute($stmt)) {
    $totalResult = mysqli_stmt_get_result($stmt);
    if ($totalData = mysqli_fetch_assoc($totalResult)) {
        $totalRows = (int)($totalData['total'] ?? 0);
        $totalPages = max(1, ceil($totalRows / $perPage));
    }
}

$statsQuery = mysqli_query($conn, "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN statut = 'ouvert' THEN 1 ELSE 0 END) as open,
        SUM(CASE WHEN statut = 'en_cours' THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN statut = 'résolu' THEN 1 ELSE 0 END) as resolved,
        SUM(CASE WHEN urgence = 'haute' THEN 1 ELSE 0 END) as high,
        SUM(CASE WHEN urgence = 'moyenne' THEN 1 ELSE 0 END) as medium,
        SUM(CASE WHEN urgence = 'basse' THEN 1 ELSE 0 END) as low
    FROM tickets
") or die("Erreur dans la requête des statistiques: " . mysqli_error($conn));

// Initialisez le tableau stats avec des valeurs par défaut
$stats = [
    'total' => 0,
    'open' => 0,
    'in_progress' => 0,
    'resolved' => 0,
    'high' => 0,
    'medium' => 0,
    'low' => 0
];

// Si la requête retourne des résultats, mettez à jour les valeurs
if ($statsQuery && $statsRow = mysqli_fetch_assoc($statsQuery)) {
    $stats = array_merge($stats, $statsRow);
}
$totalPages = ceil($totalRows / $perPage);

$query .= " LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;
$types .= 'ii';

$stmt = mysqli_prepare($conn, $query);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Remplacez la partie des statistiques par ce code :

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Tickets | <?= htmlspecialchars($_SESSION['username']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
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
        }
        
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
        
        .ticket-card {
            border-left: 4px solid;
            transition: all 0.2s;
        }
        
        .ticket-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .ticket-high { border-left-color: var(--danger); }
        .ticket-medium { border-left-color: var(--accent); }
        .ticket-low { border-left-color: var(--secondary); }
        
        .filter-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .pagination .page-item.active .page-link {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .pagination .page-link {
            color: var(--primary);
        }
        
        .sortable:hover {
            cursor: pointer;
            color: var(--primary);
        }
        
        .active-filter {
            background-color: var(--primary) !important;
            color: white !important;
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
        .btn-primary {
            background-color: rgba(41, 98, 124, 0.8);
            border-color: var(--primary);
        }
        .btn-primary:hover {
            background-color: rgba(33, 46, 82, 0.47);
            border-color: var(--primary-dark);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="dashboard.php">
                <i class="bi bi-tools me-2"></i>TechDashboard
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-speedometer2 me-1"></i> Tableau de bord
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="tickets.php">
                            <i class="bi bi-ticket-detailed me-1"></i> Gestion des Tickets
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

    <div class="container py-4">
        <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show mb-4">
            <div class="d-flex align-items-center">
                <i class="bi bi-check-circle-fill me-2"></i>
                <div><?= $_SESSION['success'] ?></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); endif; ?>

        <div class="row mb-4">
            <div class="col-md-8">
                <h2 class="fw-bold">
                    <i class="bi bi-ticket-detailed text-primary me-2"></i>
                    Gestion des Tickets
                </h2>
                <p class="text-muted mb-0">Consultez et gérez tous les tickets</p>
            </div>
            <div class="col-md-4 d-flex align-items-center justify-content-end">
                <a href="dashboard.php" class="btn btn-outline-primary me-2">
                    <i class="bi bi-arrow-left me-1"></i> Retour
                </a>
                
            </div>
        </div>

        <div class="row">
            <div class="col-lg-3 mb-4">
                <div class="filter-card p-3 mb-4">
                    <h5 class="fw-bold mb-3">
                        <i class="bi bi-funnel me-2"></i>Filtres
                    </h5>
                    
                    <form method="get" action="tickets.php">
                        <div class="mb-3">
                            <label for="search" class="form-label">Recherche</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="<?= htmlspecialchars($search) ?>" placeholder="Titre, description...">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Statut</label>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="?<?= buildQueryString(['status' => '', 'page' => '']) ?>" 
                                   class="btn btn-sm btn-outline-secondary <?= empty($status) ? 'active-filter' : '' ?>">
                                   Tous (<?= $stats['total'] ?>)
                                </a>
                                <a href="?<?= buildQueryString(['status' => 'ouvert', 'page' => '']) ?>" 
                                   class="btn btn-sm btn-outline-secondary <?= $status === 'ouvert' ? 'active-filter' : '' ?>">
                                   Ouverts (<?= $stats['open'] ?>)
                                </a>
                                <a href="?<?= buildQueryString(['status' => 'en_cours', 'page' => '']) ?>" 
                                   class="btn btn-sm btn-outline-secondary <?= $status === 'en_cours' ? 'active-filter' : '' ?>">
                                   En cours (<?= $stats['in_progress'] ?>)
                                </a>
                                <a href="?<?= buildQueryString(['status' => 'résolu', 'page' => '']) ?>" 
                                   class="btn btn-sm btn-outline-secondary <?= $status === 'résolu' ? 'active-filter' : '' ?>">
                                   Résolus (<?= $stats['resolved'] ?>)
                                </a>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Urgence</label>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="?<?= buildQueryString(['urgency' => '', 'page' => '']) ?>" 
                                   class="btn btn-sm btn-outline-secondary <?= empty($urgency) ? 'active-filter' : '' ?>">
                                   Tous
                                </a>
                                <a href="?<?= buildQueryString(['urgency' => 'haute', 'page' => '']) ?>" 
                                   class="btn btn-sm btn-outline-secondary <?= $urgency === 'haute' ? 'active-filter' : '' ?>">
                                   Haute (<?= $stats['high'] ?>)
                                </a>
                                <a href="?<?= buildQueryString(['urgency' => 'moyenne', 'page' => '']) ?>" 
                                   class="btn btn-sm btn-outline-secondary <?= $urgency === 'moyenne' ? 'active-filter' : '' ?>">
                                   Moyenne (<?= $stats['medium'] ?>)
                                </a>
                                <a href="?<?= buildQueryString(['urgency' => 'basse', 'page' => '']) ?>" 
                                   class="btn btn-sm btn-outline-secondary <?= $urgency === 'basse' ? 'active-filter' : '' ?>">
                                   Basse (<?= $stats['low'] ?>)
                                </a>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-funnel me-1"></i> Appliquer
                        </button>
                    </form>
                </div>
                
                <div class="filter-card p-3">
                    <h5 class="fw-bold mb-3">
                        <i class="bi bi-info-circle me-2"></i>Statistiques
                    </h5>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span>Tickets ouverts</span>
                            <span class="badge bg-primary rounded-pill"><?= $stats['open'] ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span>Tickets en cours</span>
                            <span class="badge bg-warning rounded-pill"><?= $stats['in_progress'] ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span>Tickets résolus</span>
                            <span class="badge bg-success rounded-pill"><?= $stats['resolved'] ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span>Total tickets</span>
                            <span class="badge bg-secondary rounded-pill"><?= $stats['total'] ?></span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="col-lg-9">
                <div class="card shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Liste des Tickets</h5>
                        <div class="text-muted small">
                            Affichage <?= ($page-1)*$perPage+1 ?>-<?= min($page*$perPage, $totalRows) ?> sur <?= $totalRows ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th width="80">ID 
                                                <a href="?<?= buildQueryString(['sort' => 'ticket_id', 'order' => $sort === 'ticket_id' && $order === 'ASC' ? 'DESC' : 'ASC']) ?>">
                                                    <i class="bi bi-arrow-<?= $sort === 'ticket_id' ? ($order === 'ASC' ? 'up' : 'down') : 'up' ?> sortable"></i>
                                                </a>
                                            </th>
                                            <th>Titre</th>
                                            <th>Urgence 
                                                <a href="?<?= buildQueryString(['sort' => 'urgence', 'order' => $sort === 'urgence' && $order === 'ASC' ? 'DESC' : 'ASC']) ?>">
                                                    <i class="bi bi-arrow-<?= $sort === 'urgence' ? ($order === 'ASC' ? 'up' : 'down') : 'up' ?> sortable"></i>
                                                </a>
                                            </th>
                                            <th>Statut 
                                                <a href="?<?= buildQueryString(['sort' => 'statut', 'order' => $sort === 'statut' && $order === 'ASC' ? 'DESC' : 'ASC']) ?>">
                                                    <i class="bi bi-arrow-<?= $sort === 'statut' ? ($order === 'ASC' ? 'up' : 'down') : 'up' ?> sortable"></i>
                                                </a>
                                            </th>
                                            <th>Date 
                                                <a href="?<?= buildQueryString(['sort' => 'date_creation', 'order' => $sort === 'date_creation' && $order === 'ASC' ? 'DESC' : 'ASC']) ?>">
                                                    <i class="bi bi-arrow-<?= $sort === 'date_creation' ? ($order === 'ASC' ? 'up' : 'down') : 'up' ?> sortable"></i>
                                                </a>
                                            </th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($ticket = mysqli_fetch_assoc($result)): ?>
                                        <tr class="ticket-card ticket-<?= $ticket['urgence'] ?>">
                                            <td class="fw-bold">#<?= $ticket['ticket_id'] ?></td>
                                            <td>
                                                <div class="fw-semibold"><?= htmlspecialchars($ticket['title']) ?></div>
                                                <small class="text-muted"><?= htmlspecialchars($ticket['equipement_nom'] ?? 'N/A') ?></small>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?= $ticket['urgence'] ?>">
                                                    <?= ucfirst($ticket['urgence']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($ticket['statut'] === 'ouvert'): ?>
                                                    <span class="badge bg-light text-dark"><?= ucfirst($ticket['statut']) ?></span>
                                                <?php elseif ($ticket['statut'] === 'en_cours'): ?>
                                                    <span class="badge bg-warning text-dark"><?= ucfirst($ticket['statut']) ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-success"><?= ucfirst($ticket['statut']) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small class="text-muted" title="<?= $ticket['date_creation'] ?>">
                                                    <?= time_elapsed_string($ticket['date_creation']) ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <a href="view_ticket.php?id=<?= $ticket['ticket_id'] ?>" 
                                                       class="btn btn-sm btn-outline-primary" 
                                                       title="Voir détails">
                                                       <i class="bi bi-eye"></i>
                                                    </a>
                                                    <?php if ($ticket['statut'] === 'ouvert' && empty($ticket['assigned_to'])): ?>
                                                        <a href="claim_ticket.php?id=<?= $ticket['ticket_id'] ?>" 
                                                           class="btn btn-sm btn-success" 
                                                           title="Prendre en charge">
                                                           <i class="bi bi-check-circle"></i>
                                                        </a>
                                                    <?php elseif ($ticket['assigned_to'] == $techId && $ticket['statut'] !== 'résolu'): ?>
                                                        <a href="resolve_ticket.php?id=<?= $ticket['ticket_id'] ?>" 
                                                           class="btn btn-sm btn-primary" 
                                                           title="Marquer comme résolu">
                                                           <i class="bi bi-check-all"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Pagination -->
                            <nav class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?= buildQueryString(['page' => $page-1]) ?>">
                                                <i class="bi bi-chevron-left"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                            <a class="page-link" href="?<?= buildQueryString(['page' => $i]) ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?= buildQueryString(['page' => $page+1]) ?>">
                                                <i class="bi bi-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="bi bi-inbox" style="font-size: 3rem; color: var(--gray);"></i>
                                <h4 class="mt-3">Aucun ticket trouvé</h4>
                                <p class="text-muted">Essayez de modifier vos filtres de recherche</p>
                                <a href="tickets.php" class="btn btn-primary mt-2">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i> Réinitialiser
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
</body>
</html>

<?php
function buildQueryString($newParams = []) {
    $params = $_GET;
    foreach ($newParams as $key => $value) {
        if ($value === '') {
            unset($params[$key]);
        } else {
            $params[$key] = $value;
        }
    }
    return http_build_query($params);
}

function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $weeks = floor($diff->d / 7);
    $days = $diff->d % 7;

    $string = [
        'y' => 'an',
        'm' => 'mois',
        'w' => 'semaine',
        'd' => 'jour',
        'h' => 'heure',
        'i' => 'minute',
        's' => 'seconde'
    ];
    
    $values = [
        'y' => $diff->y,
        'm' => $diff->m,
        'w' => $weeks,
        'd' => $days,
        'h' => $diff->h,
        'i' => $diff->i,
        's' => $diff->s
    ];
    
    $result = [];
    foreach ($string as $k => $v) {
        if ($values[$k]) {
            $result[] = $values[$k] . ' ' . $v . ($values[$k] > 1 ? 's' : '');
        }
    }

    if (!$full) $result = array_slice($result, 0, 1);
    return $result ? 'Il y a ' . implode(', ', $result) : 'À l\'instant';
}
?>
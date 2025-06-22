<?php
session_start();
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';

redirectIfNotLoggedIn();
redirectIfNotAdmin();

if (!isset($_GET['id'])) {
    header('Location: admin_equipements.php');
    exit();
}

$equipId = intval($_GET['id']);
$statuts = ['actif', 'en_panne', 'maintenance', 'hors_service'];

$equip = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT * FROM equipements WHERE equipement_id = $equipId"));

if (!$equip) {
    $_SESSION['error'] = "Équipement non trouvé";
    header('Location: admin_equipements.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = mysqli_real_escape_string($conn, $_POST['nom']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $statut = mysqli_real_escape_string($conn, $_POST['statut']);

    mysqli_query($conn, "UPDATE equipements 
                         SET nom = '$nom', description = '$description', statut = '$statut'
                         WHERE equipement_id = $equipId");
    
    $_SESSION['success'] = "Équipement mis à jour avec succès";
    header('Location: admin_equipements.php');
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
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-pencil"></i> Modifier l'équipement</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="nom" class="form-label">Nom de l'équipement</label>
                                <input type="text" class="form-control" id="nom" name="nom" 
                                       value="<?= htmlspecialchars($equip['nom']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" 
                                          rows="3" required><?= htmlspecialchars($equip['description']) ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="statut" class="form-label">Statut</label>
                                <select class="form-select" id="statut" name="statut" required>
                                    <?php foreach ($statuts as $stat): ?>
                                        <option value="<?= $stat ?>" <?= $equip['statut'] === $stat ? 'selected' : '' ?>>
                                            <?= ucfirst($stat) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Mettre à jour</button>
                            <a href="admin_equipements.php" class="btn btn-secondary">Annuler</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
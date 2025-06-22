<?php
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/auth.php';

$error = '';
$success = '';

$equipements = $conn->query("SELECT * FROM equipements ORDER BY nom")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $equipement_id = (int)$_POST['equipement_id'];
    $description = trim($_POST['description']);
    $urgence = $_POST['urgence'];
    $title = trim($_POST['title']); 
    $user_id = $_SESSION['user_id']; 
    
    if (empty($title) || empty($description)) {
        $error = "Le titre et la description sont obligatoires";
    } else {
        $stmt = $conn->prepare("SELECT equipement_id FROM equipements WHERE equipement_id = ?");
        $stmt->bind_param("i", $equipement_id);
        $stmt->execute();
        
        if (!$stmt->get_result()->num_rows) {
            $error = "L'équipement sélectionné n'existe pas";
        } else {
            $stmt = $conn->prepare("INSERT INTO tickets 
                (title, description, user_id, equipement_id, urgence) 
                VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssiis", $title, $description, $user_id, $equipement_id, $urgence);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Ticket créé avec succès!";
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Erreur lors de la création du ticket: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau Ticket | Assistance Technique</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-dark: #4834d4;
            --primary-light: #6c5ce7;
            --accent-color: #a29bfe;
            --text-dark: #2d3436;
            --text-light: #636e72;
        }
        
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background-color: #f9f9f9;
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(108, 92, 231, 0.05) 0%, transparent 20%),
                radial-gradient(circle at 90% 80%, rgba(162, 155, 254, 0.05) 0%, transparent 20%);
        }
        
        .main-header {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-light));
            color: white;
            padding: 2rem 0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }
        
        .main-header::before {
            content: "";
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 20px;
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1200 120' preserveAspectRatio='none'%3E%3Cpath d='M0,0V46.29c47.79,22.2,103.59,32.17,158,28,70.36-5.37,136.33-33.31,206.8-37.5C438.64,32.43,512.34,53.67,583,72.05c69.27,18,138.3,24.88,209.4,13.08,36.15-6,69.85-17.84,104.45-29.34C989.49,25,1113-14.29,1200,52.47V0Z' opacity='.25' fill='%23ffffff'%3E%3C/path%3E%3Cpath d='M0,0V15.81C13,36.92,27.64,56.86,47.69,72.05,99.41,111.27,165,111,224.58,91.58c31.15-10.15,60.09-26.07,89.67-39.8,40.92-19,84.73-46,130.83-49.67,36.26-2.85,70.9,9.42,98.6,31.56,31.77,25.39,62.32,62,103.63,73,40.44,10.79,81.35-6.69,119.13-24.28s75.16-39,116.92-43.05c59.73-5.85,113.28,22.88,168.9,38.84,30.2,8.66,59,6.17,87.09-7.5,22.43-10.89,48-26.93,60.65-49.24V0Z' fill='%23ffffff'%3E%3C/path%3E%3Cpath d='M0,0V5.63C149.93,59,314.09,71.32,475.83,42.57c43-7.64,84.23-20.12,127.61-26.46,59-8.63,112.48,12.24,165.56,35.4C827.93,77.22,886,95.24,951.2,90c86.53-7,172.46-45.71,248.8-84.81V0Z' fill='%23ffffff'%3E%3C/path%3E%3C/svg%3E");
            background-size: cover;
            transform: rotate(180deg);
            animation: wave 20s linear infinite;
        }
        
        @keyframes wave {
            0% { background-position-x: 0; }
            100% { background-position-x: 1200px; }
        }
        
        .header-content {
            position: relative;
            z-index: 2;
        }
        
        .page-title {
            font-weight: 800;
            font-size: 2.5rem;
            letter-spacing: -0.5px;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .page-subtitle {
            font-weight: 400;
            opacity: 0.9;
        }
        
        .logout-btn {
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.2);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
        }
        
        .logout-btn:hover {
            background: rgba(255,255,255,0.25);
            transform: translateY(-1px);
            color: white;
            text-decoration: none;
        }

        .main-container {
            flex: 1;
            padding: 3rem 0;
            background: 
                linear-gradient(rgba(249, 249, 249, 0.9), rgba(249, 249, 249, 0.9)),
                url('https://assets.codepen.io/3364143/abstract-grid.png') center/cover no-repeat;
        }
        
        .form-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            padding: 2.5rem;
            border: 1px solid rgba(0,0,0,0.04);
            transition: transform 0.3s ease;
        }
        
        .form-container:hover {
            transform: translateY(-5px);
        }
        
        .form-label {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
            display: block;
        }
        
        .form-control, .form-select {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 12px 16px;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 4px rgba(108, 92, 231, 0.15);
        }
        
        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }
        
        .btn-primary {
            background: linear-gradient(to right, var(--primary-dark), var(--primary-light));
            border: none;
            padding: 12px 24px;
            font-weight: 600;
            letter-spacing: 0.5px;
            border-radius: 10px;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(108, 92, 231, 0.25);
        }
        
        .nav-sidebar {
            width: 250px;
            background: white;
            box-shadow: 4px 0 16px rgba(0,0,0,0.05);
            padding: 2rem 1rem;
            position: fixed;
            height: 100vh;
        }
        
        .nav-link {
            color: var(--text-light);
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            transition: all 0.3s;
        }
        
        .nav-link:hover, .nav-link.active {
            background: rgba(108, 92, 231, 0.1);
            color: var(--primary-dark);
        }
        
        .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .content-area {
            margin-left: 250px;
            flex: 1;
        }
        
        .status-badge {
            font-size: 0.75rem;
            padding: 3px 10px;
            border-radius: 12px;
            font-weight: 500;
        }
        
        .status-actif { background: #e8f5e9; color: #2e7d32; }
        .status-en_panne { background: #ffebee; color: #c62828; }
        .status-maintenance { background: #e3f2fd; color: #1565c0; }
        
        .error-alert {
            border-left: 4px solid #c62828;
            background: #ffebee;
        }
    </style>
</head>
<body>
    <nav class="nav-sidebar d-none d-lg-block">
        <div class="mb-4 px-2">
            <h4 class="fw-bold" style="color: var(--primary-dark);">Assistance Technique</h4>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="dashboard.php">
                    <span>Tableau de bord</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="create_ticket.php">
                    <span>Nouveau ticket</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="tickets.php">
                    <span>Historique</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <span>Paramètres</span>
                </a>
            </li>
        </ul>
    </nav>

    <!-- Contenu Principal -->
    <div class="content-area">
        <!-- En-tête -->
        <header class="main-header">
            <div class="container header-content">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h1 class="page-title">Créer un nouveau ticket</h1>
                        <p class="page-subtitle">Décrivez le problème que vous rencontrez avec l'équipement</p>
                    </div>
                    <a href="../auth/logout.php" class="logout-btn">
                        <i class="bi bi-box-arrow-right me-1"></i> Déconnexion
                    </a>
                </div>
            </div>
        </header>

        <!-- Contenu -->
        <main class="main-container">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <?php if ($error): ?>
                            <div class="alert error-alert mb-4">
                                <?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>

                        <div class="form-container">
                            <form method="post">
                                <div class="mb-4">
                                    <label for="title" class="form-label">Titre du ticket</label>
                                    <input type="text" class="form-control" id="title" name="title" required 
                                           value="<?= isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '' ?>">
                                </div>
                                
                                <div class="mb-4">
                                    <label for="equipement_id" class="form-label">Équipement concerné</label>
                                    <select class="form-select" id="equipement_id" name="equipement_id" required>
                                        <option value="">Sélectionnez un équipement</option>
                                        <?php foreach ($equipements as $e): ?>
                                            <option value="<?= $e['equipement_id'] ?>" 
                                                <?= isset($_POST['equipement_id']) && $_POST['equipement_id'] == $e['equipement_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($e['nom']) ?>
                                                <span class="status-badge status-<?= str_replace(' ', '_', strtolower($e['statut'])) ?>">
                                                    <?= ucfirst($e['statut']) ?>
                                                </span>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="description" class="form-label">Description détaillée</label>
                                    <textarea class="form-control" id="description" name="description" required><?= 
                                        isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '' 
                                    ?></textarea>
                                    <small class="text-muted">Décrivez le problème aussi précisément que possible</small>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="urgence" class="form-label">Niveau d'urgence</label>
                                    <select class="form-select" id="urgence" name="urgence">
                                        <option value="basse" <?= isset($_POST['urgence']) && $_POST['urgence'] === 'basse' ? 'selected' : '' ?>>Basse</option>
                                        <option value="moyenne" <?= !isset($_POST['urgence']) || $_POST['urgence'] === 'moyenne' ? 'selected' : '' ?>>Moyenne</option>
                                        <option value="haute" <?= isset($_POST['urgence']) && $_POST['urgence'] === 'haute' ? 'selected' : '' ?>>Haute</option>
                                    </select>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="dashboard.php" class="text-decoration-none" style="color: var(--primary-light);">
                                        ← Retour au tableau de bord
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        Envoyer le ticket
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
session_start();
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';

redirectIfNotLoggedIn();
redirectIfNotAdmin();

$ticketStats = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN statut = 'ouvert' THEN 1 ELSE 0 END) as ouvert,
        SUM(CASE WHEN statut = 'en_cours' THEN 1 ELSE 0 END) as en_cours,
        SUM(CASE WHEN statut = 'résolu' THEN 1 ELSE 0 END) as resolu
     FROM tickets"));

$urgencyStats = mysqli_query($conn, 
    "SELECT urgence, COUNT(*) as count 
     FROM tickets 
     GROUP BY urgence");

$equipmentStats = mysqli_query($conn, 
    "SELECT e.nom, COUNT(t.ticket_id) as count
     FROM equipements e
     LEFT JOIN tickets t ON e.equipement_id = t.equipement_id
     GROUP BY e.equipement_id
     ORDER BY count DESC");

$techStats = mysqli_query($conn, 
    "SELECT u.nom, COUNT(t.ticket_id) as count
     FROM users u
     LEFT JOIN tickets t ON u.user_id = t.assigned_to
     WHERE u.role = 'technicien'
     GROUP BY u.user_id
     ORDER BY count DESC");

$resolutionStats = mysqli_query($conn, 
    "SELECT 
        AVG(TIMESTAMPDIFF(HOUR, date_creation, date_resolution)) as avg_hours,
        MIN(TIMESTAMPDIFF(HOUR, date_creation, date_resolution)) as min_hours,
        MAX(TIMESTAMPDIFF(HOUR, date_creation, date_resolution)) as max_hours
     FROM tickets
     WHERE statut = 'résolu'");
$resolutionData = mysqli_fetch_assoc($resolutionStats);
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #3F51B5;
            --primary-dark: #303F9F;
            --secondary: #009688;
            --accent: #FF5722;
            --dark: #212121;
            --light: #F5F5F5;
        }
        
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f9f9f9;
            color: var(--dark);
        }
        
        .card {
            border: none;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            font-weight: 600;
        }
        
        .chart-container {
            position: relative;
            height: 250px;
            width: 100%;
        }
        
        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table th {
            background-color: var(--primary);
            color: white;
        }
        
        .alert-stat {
            border-radius: 8px;
            border-left: 4px solid;
        }
        
        .alert-stat h5 {
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            color: rgba(255, 255, 255, 0.9);
        }
        
        .alert-stat h2 {
            font-weight: 600;
            margin-bottom: 0;
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
    <main class="container py-4">
        <h2 class="mb-4"><i class="bi bi-graph-up"></i> Rapports et Statistiques</h2>

        <div class="row mb-4">
            <div class="col-md-3">
                <div class="alert-stat alert bg-primary">
                    <h5>Tickets Totaux</h5>
                    <h2><?= $ticketStats['total'] ?></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="alert-stat alert bg-danger">
                    <h5>Ouverts</h5>
                    <h2><?= $ticketStats['ouvert'] ?></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="alert-stat alert bg-warning">
                    <h5>En Cours</h5>
                    <h2><?= $ticketStats['en_cours'] ?></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="alert-stat alert bg-success">
                    <h5>Résolus</h5>
                    <h2><?= $ticketStats['resolu'] ?></h2>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Statut des Tickets</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Urgence des Tickets</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="urgencyChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Tickets par Équipement (Top 10)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Équipement</th>
                                        <th>Nombre de Tickets</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($equip = mysqli_fetch_assoc($equipmentStats)): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($equip['nom']) ?></td>
                                            <td><?= $equip['count'] ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Tickets par Technicien</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Technicien</th>
                                        <th>Tickets Assignés</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($tech = mysqli_fetch_assoc($techStats)): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($tech['nom']) ?></td>
                                            <td><?= $tech['count'] ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Temps de Résolution</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="alert-stat alert bg-info">
                            <h5>Moyenne</h5>
                            <h2><?= round($resolutionData['avg_hours'], 1) ?> heures</h2>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="alert-stat alert bg-success">
                            <h5>Plus rapide</h5>
                            <h2><?= $resolutionData['min_hours'] ?> heures</h2>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="alert-stat alert bg-warning">
                            <h5>Plus long</h5>
                            <h2><?= $resolutionData['max_hours'] ?> heures</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Status Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        const statusChart = new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Ouverts', 'En Cours', 'Résolus'],
                datasets: [{
                    data: [
                        <?= $ticketStats['ouvert'] ?>,
                        <?= $ticketStats['en_cours'] ?>,
                        <?= $ticketStats['resolu'] ?>
                    ],
                    backgroundColor: [
                        '#3F51B5', // Indigo for Open
                        '#009688', // Teal for In Progress
                        '#FF5722'  // Deep Orange for Resolved
                    ],
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: {
                                size: 12
                            },
                            padding: 20
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        bodyFont: {
                            size: 14
                        },
                        callbacks: {
                            label: function(context) {
                                return ` ${context.label}: ${context.raw} tickets (${Math.round(context.parsed)}%)`;
                            }
                        }
                    }
                },
                cutout: '70%'
            }
        });

        // Urgency Chart
        const urgencyData = {
            <?php 
            $data = [];
            while ($row = mysqli_fetch_assoc($urgencyStats)) {
                $data[] = "{label: '" . ucfirst($row['urgence']) . "', value: " . $row['count'] . "}";
            }
            echo 'datasets: [' . implode(',', $data) . ']';
            ?>
        };

        const urgencyCtx = document.getElementById('urgencyChart').getContext('2d');
        const urgencyChart = new Chart(urgencyCtx, {
            type: 'bar',
            data: {
                labels: urgencyData.datasets.map(item => item.label),
                datasets: [{
                    label: 'Nombre de Tickets',
                    data: urgencyData.datasets.map(item => item.value),
                    backgroundColor: [
                        '#4CAF50', // Green for Low
                        '#FF9800', // Orange for Medium
                        '#F44336'  // Red for High
                    ],
                    borderColor: '#fff',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0,0,0,0.8)'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        },
                        ticks: {
                            font: {
                                size: 12
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 12
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
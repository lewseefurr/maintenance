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
        DATE_FORMAT(t.date_creation, '%d/%m/%Y %H:%i') AS date_creation
    FROM tickets t
    JOIN equipements e ON t.equipement_id = e.equipement_id
    WHERE t.user_id = ?
    ORDER BY t.date_creation DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Employee Dashboard</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        .new-ticket { 
            background: #2196F3; 
            color: white; 
            padding: 8px 15px; 
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
        }
        .urgency-low { color: green; }
        .urgency-medium { color: orange; }
        .urgency-high { color: red; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Tableau de Bord Employé</h1>
    <a href="create_ticket.php" class="new-ticket">Nouveau Ticket</a>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div style="color:green; margin-bottom:15px;"><?= $_SESSION['success'] ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <h2>Mes Tickets</h2>
    <?php if ($result->num_rows > 0): ?>
    <table>
        <tr>
            <th>ID</th>
            <th>Titre</th>
            <th>Équipement</th>
            <th>Urgence</th>
            <th>Description</th>
            <th>Statut</th>
            <th>Date</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['ticket_id'] ?></td>
            <td><?= htmlspecialchars($row['title']) ?></td>
            <td><?= htmlspecialchars($row['equipement_nom']) ?></td>
            <td class="urgency-<?= $row['urgence'] ?>">
                <?= ucfirst($row['urgence']) ?>
            </td>
            <td><?= htmlspecialchars($row['description']) ?></td>
            <td><?= ucfirst(str_replace('_', ' ', $row['statut'])) ?></td>
            <td><?= $row['date_creation'] ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
    <?php else: ?>
    <p>Aucun ticket trouvé.</p>
    <?php endif; ?>
    
    <p><a href="../auth/logout.php">Déconnexion</a></p>
</body>
</html>
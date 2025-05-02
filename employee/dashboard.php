<?php
session_start();
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';

redirectIfNotLoggedIn();

$userId = $_SESSION['user_id'];
$result = mysqli_query($conn, "
    SELECT id, title, equipment, urgency, description, status, created_at 
    FROM tickets 
    WHERE created_by = $userId
    ORDER BY created_at DESC
");

if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}
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
    <h1>Mode : Employée</h1>
    <a href="create_ticket.php" class="new-ticket">New Ticket</a>
    
    <h2>Mes Tickets</h2>
    <?php if (mysqli_num_rows($result) > 0): ?>
    <table>
        <tr>
            <th>ID</th>
            <th>Titre</th>
            <th>Equipment</th>
            <th>Niv.Urgence</th>
            <th>Description</th>
            <th>Statut</th>
            <th>Date Création</th>
        </tr>
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['title']) ?></td>
            <td><?= htmlspecialchars($row['equipment']) ?></td>
            <td class="urgency-<?= $row['urgency'] ?>">
                <?= ucfirst($row['urgency']) ?>
            </td>
            <td><?= !empty($row['description']) ? htmlspecialchars($row['description']) : '-' ?></td>
            <td><?= ucfirst(str_replace('_', ' ', $row['status'])) ?></td>
            <td><?= date('Y-m-d H:i', strtotime($row['created_at'])) ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
    <?php else: ?>
    <p>Vous n'avez aucun ticket au moment.</p>
    <?php endif; ?>
    
    <p><a href="../auth/logout.php">Se deconnécter</a></p>
</body>
</html>
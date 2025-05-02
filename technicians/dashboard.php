<?php
session_start();
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';

redirectIfNotLoggedIn();
redirectIfNotTechnician();

$techId = $_SESSION['user_id'];
$result = mysqli_query($conn, 
    "SELECT t.*, u.username as creator 
    FROM tickets t
    JOIN users u ON t.created_by = u.id
    WHERE t.status != 'resolved' 
    AND (t.assigned_to IS NULL OR t.assigned_to = $techId)"
);

if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Tech Dashboard</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        .claim-btn { background: #4CAF50; color: white; padding: 5px 10px; text-decoration: none; }
    </style>
</head>
<body style="align-items: center; text-align: center;">
    <h1>Mode Technicien</h1>
    <h2>Tickets en Attente :</h2>
    
    <?php if (mysqli_num_rows($result) > 0): ?>
    <table>
        <tr>
            <th>ID</th>
            <th>Description</th>
            <th>Created By</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['description']) ?></td>
            <td><?= htmlspecialchars($row['creator']) ?></td>
            <td><?= $row['status'] ?></td>
            <td>
                <?php if (!$row['assigned_to']): ?>
                    <a href="claim_ticket.php?id=<?= $row['id'] ?>" class="claim-btn">Claim</a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
    <?php else: ?>
    <p>No pending tickets found.</p>
    <?php endif; ?>
    
    <p><a href="../auth/logout.php">Logout</a></p>
</body>
</html>
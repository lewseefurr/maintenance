<?php
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/auth.php';

$equipements = $pdo->query("SELECT * FROM equipements WHERE statut = 'actif'")->fetchAll();

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $required = ['equipement_id', 'description'];
        foreach($required as $field) {
            if(empty($_POST[$field])) {
                throw new Exception("Le champ $field est requis");
            }
        }

        $equipement_id = $_POST['equipement_id'];
        $description = $_POST['description'];
        $urgence = $_POST['urgence'] ?? 'moyenne';

        $stmt = $pdo->prepare("SELECT 1 FROM equipements WHERE equipement_id = ?");
        $stmt->execute([$equipement_id]);
        if(!$stmt->fetch()) {
            throw new Exception("Équipement invalide");
        }

        $insert = $pdo->prepare("
            INSERT INTO tickets 
            (title, description, user_id, equipement_id, urgence, statut)
            VALUES (?, ?, ?, ?, ?, 'en_attente')
        ");
        
        $insert->execute([
            "Problème avec équipement $equipement_id",
            $description,
            $_SESSION['user_id'],
            $equipement_id,
            $urgence
        ]);

        $_SESSION['success'] = "Ticket créé avec succès";
        header('Location: dashboard.php');
        exit();

    } catch(Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header('Location: create_ticket.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create New Ticket</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .error { color: red; margin-bottom: 15px; }
        form { max-width: 500px; }
        input, select, button, textarea { 
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            font-family: 'Times New Roman', Times, serif;
        }
        textarea {
            height: 100px;
            resize: vertical;
        }
        button { 
            background: #2196F3;
            color: white;
            border: none;
            padding: 10px;
            
        }
        a:hover {
            text-decoration: overline;
            color: red;
        }
    </style>
</head>
<body>
    <h1>Create New Ticket</h1>
    
    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <form method="post">
    <div class="form-group">
        <label>Équipement</label>
        <select name="equipement_id" class="form-control" required>
            <option value="">Sélectionnez un équipement</option>
            <?php foreach($equipements as $eq): ?>
                <option value="<?= $eq['equipement_id'] ?>">
                    <?= htmlspecialchars($eq['nom']) ?> (<?= $eq['reference'] ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <div class="form-group">
        <label>Description du problème</label>
        <textarea name="description" class="form-control" required></textarea>
    </div>
    
    <div class="form-group">
        <label>Urgence</label>
        <select name="urgence" class="form-control">
            <option value="basse">Basse</option>
            <option value="moyenne" selected>Moyenne</option>
            <option value="haute">Haute</option>
        </select>
    </div>
    
    <button type="submit" class="btn btn-primary">Créer le ticket</button>
</form>
    
    <p><a href="dashboard.php">Précedent</a></p>
</body>
</html>
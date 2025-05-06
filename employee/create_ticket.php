<?php
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/auth.php';

$error = '';

$equipements = $conn->query("SELECT * FROM equipements ORDER BY nom")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $equipement_id = (int)$_POST['equipement_id'];
    $description = $conn->real_escape_string($_POST['description']);
    $user_id = $_SESSION['user_id']; 
    
    $stmt = $conn->prepare("SELECT equipement_id FROM equipements WHERE equipement_id = ?");
    $stmt->bind_param("i", $equipement_id);
    $stmt->execute();
    
    if (!$stmt->get_result()->num_rows) {
        $error = "L'équipement sélectionné n'existe pas";
    } else {
        $stmt = $conn->prepare("INSERT INTO tickets (equipement_id, user_id, description) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $equipement_id, $user_id, $description);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Ticket créé avec succès!";
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Erreur: " . $conn->error;
        }
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
    <h1>Créer un Ticket</h1>
    
    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
        <form method="post">
        <div class="mb-3">
            <label class="form-label">Équipement</label>
            <select name="equipement_id" class="form-select" required>
                <option value="">-- Sélectionnez --</option>
                <?php foreach ($equipements as $e): ?>
                    <option value="<?= $e['equipement_id'] ?>">
                        <?= htmlspecialchars($e['nom']) ?> - <?= $e['statut'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="mb-3">
            <label>Description</label>
            <textarea name="description" required><?= 
                isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '' 
            ?></textarea>
        </div>
        
        <div class="mb-3">
            <label>Urgence</label>
            <select name="urgence">
                <option value="basse" <?= isset($_POST['urgence']) && $_POST['urgence'] === 'basse' ? 'selected' : '' ?>>Basse</option>
                <option value="moyenne" <?= !isset($_POST['urgence']) || $_POST['urgence'] === 'moyenne' ? 'selected' : '' ?>>Moyenne</option>
                <option value="haute" <?= isset($_POST['urgence']) && $_POST['urgence'] === 'haute' ? 'selected' : '' ?>>Haute</option>
            </select>
        </div>
        
        <button type="submit" class="btn btn-primary">Soumettre</button>
    
        <?php if (isset($error)): ?>
            <div class="alert alert-danger mt-3"><?= $error ?></div>
        <?php endif; ?>
    </form>
    
    <p><a href="dashboard.php">Retour</a></p>
</body>
</html>

<?php
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/auth.php';

$error = '';
$equipements = [];
$result = $conn->query("SELECT equipement_id, nom FROM equipements WHERE statut = 'actif'");
while ($row = $result->fetch_assoc()) {
    $equipements[] = $row;
}

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $required = ['equipement_id', 'description'];
        foreach($required as $field) {
            if(empty($_POST[$field])) {
                throw new Exception("Le champ $field est requis");
            }
        }

        $equipement_id = (int)$_POST['equipement_id'];
        $description = $conn->real_escape_string($_POST['description']);
        $urgence = isset($_POST['urgence']) && in_array($_POST['urgence'], ['basse','moyenne','haute']) 
                 ? $_POST['urgence'] 
                 : 'moyenne';

        $check = $conn->prepare("SELECT 1 FROM equipements WHERE equipement_id = ?");
        $check->bind_param("i", $equipement_id);
        $check->execute();
        if(!$check->get_result()->fetch_assoc()) {
            throw new Exception("Équipement invalide");
        }

        $insert = $conn->prepare("
            INSERT INTO tickets 
            (title, description, user_id, equipement_id, urgence, statut, date_creation)
            VALUES (?, ?, ?, ?, ?, 'en_attente', NOW())
        ");
        
        $title = "Ticket #" . time(); 
        
        if(!$insert->bind_param("ssiis", $title, $description, $_SESSION['user_id'], $equipement_id, $urgence) || 
           !$insert->execute()) {
            throw new Exception("Erreur création ticket: " . $insert->error);
        }

        $_SESSION['success'] = "Ticket créé avec succès";
        header('Location: dashboard.php');
        exit();

    } catch(Exception $e) {
        $error = $e->getMessage();
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
            <label for="equipement_id">Nom de l'Équipement</label>
            <input type="text" id="equipement_id" name="equipement_id" required 
                   value="<?= isset($_POST['equipement_id']) ? htmlspecialchars($_POST['equipement_id']) : '' ?>">
        </div>
        
        <div class="form-group">
            <label>Description du problème</label>
            <textarea name="description" class="form-control" required><?= 
                isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '' 
            ?></textarea>
        </div>
        
        <div class="form-group">
            <label>Urgence</label>
            <select name="urgence" class="form-control">
                <option value="basse" <?= (isset($_POST['urgence']) && $_POST['urgence'] === 'basse') ? 'selected' : '' ?>>Basse</option>
                <option value="moyenne" <?= !isset($_POST['urgence']) || $_POST['urgence'] === 'moyenne' ? 'selected' : '' ?>>Moyenne</option>
                <option value="haute" <?= (isset($_POST['urgence']) && $_POST['urgence'] === 'haute') ? 'selected' : '' ?>>Haute</option>
            </select>
        </div>
        
        <button type="submit" class="btn btn-primary">Créer le ticket</button>
    </form>
    
    <p><a href="dashboard.php">Précedent</a></p>
</body>
</html>


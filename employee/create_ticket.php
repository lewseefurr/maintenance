<?php
session_start();
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';

redirectIfNotLoggedIn();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $equipement_id = trim($_POST['equipement_id'] ?? '');
    $urgence = $_POST['urgence'] ?? 'moyenne';
    $userId = $_SESSION['user_id'];
    
    $check_user = $conn->prepare("SELECT user_id FROM users WHERE user_id = ?");
    $check_user->bind_param("i", $userId);
    $check_user->execute();
    $check_user->store_result();

    if ($check_user->num_rows === 0) {
        $error = "Utilisateur invalide";
    } elseif (empty($title)) {
        $error = "Le titre est requis";
    } else {
        $stmt = $conn->prepare("INSERT INTO tickets (title, description, equipement_id, urgence, user_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssi", $title, $description, $equipement_id, $urgence, $userId);
        
        if ($stmt->execute()) {
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Erreur lors de la création du ticket: " . $conn->error;
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
    <h1>Create New Ticket</h1>
    
    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div>
            <label for="title">Titre:</label>
            <input type="text" name="title" id="title" required>
        </div>
        
        <div>
            <label for="equipement_id">Equipment:</label>
            <input type="text" name="equipment_id" id="equipment_id" required>
        </div>
        
        <div>
            <label for="urgence">Niveau d'urgence</label>
            <select name="urgence" id="urgence" required>
                <option value="low">Bas</option>
                <option value="medium" selected>Moyen</option>
                <option value="high">Elevé</option>
            </select>
        </div>

        <div>
            <label for="description">Description:</label>
            <textarea name="description" id="description"></textarea>
        </div>
        
        <button type="submit">Soumettre le ticket</button>
    </form>
    
    <p><a href="dashboard.php">Précedent</a></p>
</body>
</html>
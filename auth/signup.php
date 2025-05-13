<?php
session_start();
include __DIR__ . '/../includes/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $nom = $_POST['nom'];
    $password = $_POST['password'];
    $role = 'employe';
    $allowed_roles = ['employe', 'technicien'];
    if (isset($_POST['role']) && in_array($_POST['role'], $allowed_roles)) {
        $role = $_POST['role'];
    }

    $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $error = "Ce nom d'utilisateur est déjà pris.";
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (nom, username, password, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $nom, $username, $hashedPassword, $role);
        
        if ($stmt->execute()) {
            header("Location: login.php?signup=success");
            exit();
        } else {
            $error = "Creation de compte échouée. Veuillez réessayer.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Sign Up</title>
    <style>
        .role-selection {
            margin: 15px 0;
        }
        .role-selection label {
            margin-right: 15px;
        }
    </style>
</head>

<form method="POST" action="signup.php">
    <h2>Créer un compte :</h2>
    <p>Déjà un compte ? <a href="login.php">Se connecter</a></p>
    <input type="text" name="nom" placeholder="Nom Complet" required><br>
    <input type="text" name="username" placeholder="Nom d'utilisateur" required><br>
    <input type="password" name="password" placeholder="Mot de passe" required><br>

    <div class="role-selection">
        <label>
            <input type="radio" name="role" value="employe" checked> Employee
        </label>
        <label>
            <input type="radio" name="role" value="technicien"> Technician
        </label>
    </div>
    <button type="submit">S'inscrire</button>
    <br>
    
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-image: url("todolist.jpg"); 
            background-size: cover; 
            background-position: center;
            
        }
        form {
            display: flex;
            flex-direction: column;
            width: 450px;
            margin: auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            background-color: #f9f9f9;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            background: rgba(255, 255, 255, 0.5); 
            font-family: monospace, sans-serif;
            font-size: 16px;
            color: #333;
            text-align: center;
            transition: all 0.3s ease;
            margin-top: 8%;
        }
        input[type="text"], input[type="password"] {
            padding: 10px;
            margin: 5px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            width: 95%;
        }
        button:hover {
            background-color: #007BFF;
            color: white;
            border: 2px solid #007BFF;
            cursor: pointer;
            transition: all 0.3s ease;

            
        }
        button[type="submit"]:hover {
            background-color: green;
            color: white;
            border: 2px solid black;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        p {
            font-size: 14px;
            color: #555;
        }
    </style>
</form>
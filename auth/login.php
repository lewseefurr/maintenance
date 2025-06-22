<?php
session_start();
require __DIR__ . '/../includes/auth.php';
include __DIR__ . '/../includes/db.php';

$error = '';


if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'technicien') {
        header("Location: ../technicians/dashboard.php");
    } elseif ($_SESSION['role'] === 'admin') {
        header("Location: ../admin/dashboard.php");
    }else {
        header("Location: ../employee/dashboard.php");
    }
    exit();
}

if (isset($_POST['username']) && isset($_POST['password'])) {
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password']; 
    
    $result = mysqli_query($conn, "SELECT * FROM users WHERE username='$username'");
    $user = mysqli_fetch_assoc($result);

    if ($user && verifyPassword($password, $user['password'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $user['role']; 
        $_SESSION['nom'] = $user['nom'];
        
        if ($user['role'] === 'technicien') {
            header("Location: ../technicians/dashboard.php");
        } elseif ($user['role'] === 'admin') {
            header("Location: ../admin/dashboard.php");
        } else {
            header("Location: ../employee/dashboard.php");
        }
        exit();
    } else {
        $error = "Identifiants incorrects.";
    }
}
?>


<form method="POST">
    <h2>S'authentifier :</h2>
    <input type="text" name="username" placeholder="Nom d'utilisateur" required><br>
    <input type="password" name="password" placeholder="Mot de passe" required><br>
    <button type="submit">Se connecter</button>
    <br>
    <button type="button" id="BtnCreer">Créer un compte</button>
    <br>
    <?php if (!empty($error)): ?>
    <div style="color: red; text-align: center;">
        <?= $error ?>
    </div>
    <?php endif; ?>
    <script>
        document.getElementById('BtnCreer').addEventListener('click', function(event) {
            window.location.href = 'signup.php';
        });
    </script>
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
        input, button {
            margin: 5px 0;
            padding: 10px;
            border: 1px solid #ccc;
            width: 75%;
            align-self: center;
        }
        button:hover {
            background-color: #007BFF;
            color: white;
            border: 2px solid #007BFF;
            cursor: pointer;
            transition: all 0.3s ease;

            
        }
        button[type="button"]:hover {
            background-color: green;
            color: white;
            border: 2px solid black;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        button {
            background-color: beige;
            color: black;
            border: 2px solid black;
            padding: 10px;
            cursor: pointer; 
        }

    </style>
</form>
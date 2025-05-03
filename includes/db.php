<?php
$conn = mysqli_connect("localhost", "root", "", "maintenance_db");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

$queries = [
    "CREATE TABLE IF NOT EXISTS users (
        user_id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) UNIQUE,
        password VARCHAR(255),
        role ENUM('employe','technicien','admin') DEFAULT 'employe'
    )",
    
    "CREATE TABLE IF NOT EXISTS tickets (
        ticket_id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(100) NOT NULL,
        equipement VARCHAR(50) NOT NULL,
        urgence ENUM('low','medium','high') DEFAULT 'medium',
        status ENUM('open','in_progress','resolved') DEFAULT 'open',
        created_by INT REFERENCES users(user_id),
        assigned_to INT NULL REFERENCES users(user_id),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )"
];

foreach ($queries as $query) {
    if (!mysqli_query($conn, $query)) {
        die("Error creating table: " . mysqli_error($conn));
    }
}
?>
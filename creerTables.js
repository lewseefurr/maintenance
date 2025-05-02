const mysql = require('mysql2');
require('dotenv').config();

const connection = mysql.createConnection({
  host: process.env.DB_HOST,
  user: process.env.DB_USER,
  password: process.env.DB_PASSWORD,
  multipleStatements: true // Important pour exécuter plusieurs requêtes
});

connection.connect((err) => {
  if (err) throw err;
  console.log('Connecté à MySQL !');

  const createDatabaseAndTables = `
    CREATE DATABASE IF NOT EXISTS ${process.env.DB_NAME};
    USE ${process.env.DB_NAME};

    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100),
        email VARCHAR(100) UNIQUE,
        password VARCHAR(255),
        role ENUM('employe', 'technicien', 'manager', 'admin') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS equipements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(100),
        description TEXT,
        date_achat DATE
    );

    CREATE TABLE IF NOT EXISTS tickets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        equipement_id INT,
        description TEXT,
        urgence ENUM('basse', 'moyenne', 'haute') DEFAULT 'moyenne',
        statut ENUM('en attente', 'en cours', 'résolu') DEFAULT 'en attente',
        date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (equipement_id) REFERENCES equipements(id)
    );

    CREATE TABLE IF NOT EXISTS interventions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ticket_id INT,
        technicien_id INT,
        date_intervention DATE,
        cout DECIMAL(10,2),
        details TEXT,
        FOREIGN KEY (ticket_id) REFERENCES tickets(id),
        FOREIGN KEY (technicien_id) REFERENCES users(id)
    );

    CREATE TABLE IF NOT EXISTS stock (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom_piece VARCHAR(100),
        quantite INT,
        seuil_alerte INT,
        fournisseur VARCHAR(100)
    );

    CREATE TABLE IF NOT EXISTS utilisation_stock (
        id INT AUTO_INCREMENT PRIMARY KEY,
        intervention_id INT,
        piece_id INT,
        quantite_utilisee INT,
        FOREIGN KEY (intervention_id) REFERENCES interventions(id),
        FOREIGN KEY (piece_id) REFERENCES stock(id)
    );
  `;

  connection.query(createDatabaseAndTables, (err, result) => {
    if (err) throw err;
    console.log('Base de données et tables créées avec succès.');
    connection.end();
  });
});
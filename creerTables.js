const mysql = require('mysql2');
require('dotenv').config();

const connection = mysql.createConnection({
  host: process.env.DB_HOST,
  user: process.env.DB_USER,
  password: process.env.DB_PASSWORD
});
async function seedEquipements() {
  const equipements = [
    ['PC Dell Precision', 'Workstation i7, 32GB RAM', 'En service'],
    ['Imprimante Brother HL-L2370DW', 'Noir et blanc, réseau', 'En panne']
  ];
  try {
    await pool.query(`
      INSERT INTO equipement (nom, description, statut)
      VALUES ?
    `, [equipements]);
    console.log('✅ Équipements exemples ajoutés');
  } catch (err) {
    console.error('❌ Erreur lors de l\'insertion :', err);
  }
}

const tables = [
  `CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('employe', 'technicien', 'admin') NOT NULL DEFAULT 'employe',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  )`,

  `CREATE TABLE IF NOT EXISTS equipements (
    equipement_id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    description TEXT,
    statut ENUM('actif', 'en_panne', 'maintenance', 'hors_service') NOT NULL DEFAULT 'actif',
    date_ajout TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  )`,

  `CREATE TABLE IF NOT EXISTS tickets (
  ticket_id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  description TEXT NOT NULL,
  user_id INT NOT NULL,
  equipement_id INT,
  urgence ENUM('basse', 'moyenne', 'haute') DEFAULT 'moyenne',
  statut ENUM('en attente', 'en cours', 'résolu') DEFAULT 'en attente',
  date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(user_id),
  FOREIGN KEY (equipement_id) REFERENCES equipements(equipement_id)
  )`,
];

connection.connect((err) => {
  if (err) throw err;
  console.log('Connecté à MySQL');

  connection.query(`CREATE DATABASE IF NOT EXISTS ${process.env.DB_NAME}`, (err) => {
    if (err) throw err;
    console.log('Base de données crée');

    connection.query(`USE ${process.env.DB_NAME}`, (err) => {
      if (err) throw err;

      tables.forEach((tableSQL, index) => {
        connection.query(tableSQL, (err) => {
          if (err) throw err;
          console.log(`Table ${index + 1} crée`);
          
          if (index === tables.length - 1) {
            console.log('Succes');
            connection.end();
          }
        });
      });
    });
  });
});
seedEquipements();
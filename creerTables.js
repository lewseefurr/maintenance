const mysql = require('mysql2/promise'); 
require('dotenv').config();

const pool = mysql.createPool({
  host: process.env.DB_HOST,
  user: process.env.DB_USER,
  password: process.env.DB_PASSWORD,
  database: process.env.DB_NAME
  
});

const equipements = [
  ['PC Dell Precision', 'Workstation i7, 32GB RAM', 'actif'],
  ['Imprimante Brother HL-L2370DW', 'Noir et blanc, réseau', 'en_panne'],
  ['Moniteur Samsung 27"', 'Résolution 4K UHD, HDMI/DisplayPort', 'actif'],
  ['Routeur Cisco RV340', 'VPN, double WAN, 4 ports LAN', 'actif'],
  ['PC HP EliteBook 840', 'Laptop i5, 16GB RAM, SSD 512GB', 'en_panne'],
  ['Scanner Epson Perfection V600', 'Haute résolution, USB', 'actif'],
  ['Onduleur APC Back-UPS 700VA', 'Protection électrique', 'hors_service'],
  ['Serveur Dell PowerEdge T40', 'Xeon E-2224G, 32GB ECC RAM', 'actif'],
  ['Clé USB Kingston 64GB', 'USB 3.0, stockage portable', 'actif'],
  ['Projecteur Epson EB-S41', 'SVGA, 3300 lumens', 'en_panne'],
  ['Caméra de surveillance Hikvision', 'Infra-rouge, IP, PoE', 'maintenance'],
  ['Badgeuse ZKTeco K40', 'Contrôle d’accès et pointage biométrique', 'actif'],
  ['NAS Synology DS220+', '2 baies, RAID1, 4To de stockage', 'actif'],
  ['PC Portable Lenovo ThinkPad T14', 'Ryzen 5 PRO, 16GB RAM', 'actif'],
  ['Imprimante multifonction HP LaserJet M428fdn', 'Scan, copie, fax', 'actif'],
  ['Onduleur Eaton 5E', 'Protection 1200VA pour serveurs', 'en_panne'],
  ['Switch manageable TP-Link TL-SG3210', '10 ports, VLAN, SNMP', 'actif'],
  ['Caméra intérieure Xiaomi Mi 360°', 'Wi-Fi, détection de mouvement', 'actif'],
  ['Climatisation LG 12 000 BTU', 'Climatiseur de salle serveur', 'hors_service'],
  ['Disque dur externe WD Elements 2To', 'Sauvegardes de postes utilisateurs', 'actif']
];

const tables = [
  `CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
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
    assigned_to INT NULL,
    urgence ENUM('basse', 'moyenne', 'haute') DEFAULT 'moyenne',
    statut ENUM('ouvert', 'en_cours', 'résolu') DEFAULT 'ouvert',
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_resolution TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (equipement_id) REFERENCES equipements(equipement_id),
    FOREIGN KEY (assigned_to) REFERENCES users(user_id)
  )`
];

async function initializeDatabase() {
  try {
    await pool.query(`CREATE DATABASE IF NOT EXISTS ${process.env.DB_NAME}`);
    await pool.query(`USE ${process.env.DB_NAME}`);

    for (const tableSQL of tables) {
      await pool.query(tableSQL);
    }
    console.log('✅ Toutes les tables ont été créées');

    await pool.query(`
      INSERT INTO equipements (nom, description, statut)
      VALUES ?
    `, [equipements]);
    console.log('✅ Équipements exemples ajoutés');

  } catch (err) {
    console.error('❌ Erreur:', err);
  } finally {
    await pool.end();
  }
}

initializeDatabase();
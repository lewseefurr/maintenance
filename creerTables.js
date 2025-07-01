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

const defaultUsers = [
  // Admin (password: "password")
  ['Admin', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'],
  
  // Employé (password: "password")
  ['Employé Demo', 'emp1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employe'],
  
  // Technicien (password: "password") 
  ['Technicien Demo', 'tech1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'technicien']
];

const demo_tickets = [
  // Tickets urgents
  [
    'PC ne démarre pas', 
    'Le PC Dell ne montre aucun signe de vie après la mise sous tension',
    2, 1, 3, 'haute', 'en_cours', null, null
  ],
  [
    'Serveur en surchauffe',
    'Le serveur émet des bips alarmants et la température CPU dépasse 90°C',
    2, 8, null, 'haute', 'ouvert', null, null  
  ],
  
  // Tickets moyens  
  [
    'Imprimante en panne',
    'L\'imprimante affiche "Erreur de cartouche" malgré le remplacement',
    2, 2, 3, 'moyenne', 'en_cours', null, null
  ],
  [
    'Problème réseau',
    'Connexion internet intermittente dans le service comptabilité',
    2, 4, null, 'moyenne', 'ouvert', null, null
  ],
  [
    'Scanner bloqué',
    'Le scanner ne répond plus depuis hier matin',
    2, 6, 3, 'moyenne', 'résolu', '2023-06-15 10:00:00', 'Nettoyage des têtes d\'impression'
  ],
  [
    'Clavier défectueux',
    'Touches F1-F12 ne fonctionnent plus sur le clavier du poste 12',
    2, 1, null, 'moyenne', 'ouvert', null, null  
  ],
  
  // Tickets basse priorité
  [
    'Écran scintille',
    'L\'écran du poste 5 scintille légèrement en fond blanc',
    2, 3, 3, 'basse', 'résolu', '2023-06-14 16:30:00', 'Remplacement du câble DVI'
  ],
  [
    'Souris sans fil',
    'La souris sans fil du bureau 42 a un lag perceptible',
    2, null, null, 'basse', 'ouvert', null, null
  ],
  [
    'Clé USB non reconnue',
    'La clé Kingston n\'est pas détectée sur certains postes',
    2, 9, 3, 'basse', 'en_cours', null, null
  ],
  [
    'Projecteur couleur',
    'Les couleurs du projecteur sont délavées (dominante verte)',
    2, 10, null, 'basse', 'ouvert', null, null  
  ],
  
  // Tickets résolus avec historique
  [
    'NAS inaccessible',
    'Le stockage réseau ne répond plus depuis ce matin',
    2, 13, 3, 'haute', 'résolu', '2023-06-13 11:15:00', 'Redémarrage du NAS effectué'
  ],
  [
    'Badgeuse défectueuse',
    'La badgeuse ne lit plus les badges RFID',
    2, 12, 3, 'moyenne', 'résolu', '2023-06-12 14:00:00', 'Mise à jour du firmware'
  ],
  [
    'Climatisation fuite',
    'Fuite d\'eau sous la climatisation de la salle serveur',
    2, 19, 3, 'haute', 'résolu', '2023-06-10 09:45:00', 'Intervention technicien externe'
  ],
  [
    'Switch port morte',
    'Le port 7 du switch ne fournit plus de connexion',
    2, 17, 3, 'moyenne', 'résolu', '2023-06-09 16:20:00', 'Port désactivé et remplacé par port 8'
  ],
  [
    'Caméra hors ligne',
    'La caméra de surveillance du couloir est hors ligne',
    2, 11, 3, 'moyenne', 'résolu', '2023-06-08 13:10:00', "Problème d'alimentation PoE résolu"
  ]
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
    resolution_comment TEXT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (equipement_id) REFERENCES equipements(equipement_id),
    FOREIGN KEY (assigned_to) REFERENCES users(user_id)
  )`
];

async function initializeDatabase() {
  try {
    await pool.query(`CREATE DATABASE IF NOT EXISTS ${process.env.DB_NAME}`);
    await pool.query(`USE ${process.env.DB_NAME}`);

    // Création des tables
    for (const tableSQL of tables) {
      await pool.query(tableSQL);
    }
    console.log('✅ Tables créées');

    // Insertion des utilisateurs
    await pool.query(`
      INSERT INTO users (nom, username, password, role)
      VALUES ?
      ON DUPLICATE KEY UPDATE nom = VALUES(nom)
    `, [defaultUsers]);
    console.log('✅ Comptes démo créés :');
    console.log('- Admin : admin / password');
    console.log('- Employé : emp1 / 123');
    console.log('- Technicien : tech1 / 123');

    // Insertion des équipements
    await pool.query(`
      INSERT INTO equipements (nom, description, statut)
      VALUES ?
      ON DUPLICATE KEY UPDATE nom = VALUES(nom)
    `, [equipements]);
    console.log('✅ Équipements initialisés');

    // Insertion des tickets de démo
    await pool.query(`
      INSERT INTO tickets 
        (title, description, user_id, equipement_id, assigned_to, urgence, statut, date_resolution, resolution_comment)
      VALUES ?
    `, [demo_tickets]);
    console.log('✅ Tickets de démonstration créés');

  } catch (err) {
    console.error('❌ Erreur:', err);
  } finally {
    await pool.end();
  }
}

initializeDatabase();
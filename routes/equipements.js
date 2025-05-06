const express = require('express');
const router = express.Router();
const pool = require('../db.js');

router.get('/', async (req, res) => {
  try {
    const [equipements] = await pool.query('SELECT * FROM equipement');
    res.json(equipements);
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

router.post('/', async (req, res) => {
  const { nom, description } = req.body;
  if (!nom) return res.status(400).json({ error: "Le nom est requis" });

  try {
    await pool.query(
      'INSERT INTO equipement (nom, description) VALUES (?, ?)',
      [nom, description]
    );
    res.json({ success: true });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

module.exports = router;
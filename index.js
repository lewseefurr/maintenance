const express = require('express');
const db = require('./db');

const app = express();
app.use(express.json());

app.get('/users', (req, res) => {
  db.query('SELECT * FROM users', (err, results) => {
    if (err) {
      res.status(500).send('Erreur serveur');
    } else {
      res.json(results);
    }
  });
});

app.listen(3000, () => {
  console.log('Serveur démarré sur http://localhost:3000');
});
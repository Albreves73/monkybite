const mysql = require('mysql2/promise');

const db = mysql.createPool({
  host: 'localhost',
  user: 'root',          // coloque o usuário que funciona no seu MySQL
  password: 'SENHA_AQUI', // coloque a senha que funciona no seu MySQL
  database: 'monkybite'
});

module.exports = db;

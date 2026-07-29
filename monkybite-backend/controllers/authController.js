const db = require('../config/db');
const bcrypt = require('bcrypt');
const jwt = require('jsonwebtoken');

const JWT_SECRET = 'monkybite_super_secret';

exports.signup = async (req, res) => {
  try {
    const { email, password } = req.body;

    if (!email || !password)
      return res.status(400).json({ error: 'Missing fields' });

    const [exists] = await db.query('SELECT id FROM users WHERE email = ?', [email]);
    if (exists.length > 0)
      return res.status(409).json({ error: 'Email already exists' });

    const hash = await bcrypt.hash(password, 10);

    await db.query('INSERT INTO users (email, password) VALUES (?, ?)', [email, hash]);

    res.status(201).json({ message: 'User created' });

  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Server error' });
  }
};

exports.login = async (req, res) => {
  try {
    const { email, password } = req.body;

    const [rows] = await db.query('SELECT id, password FROM users WHERE email = ?', [email]);
    if (rows.length === 0)
      return res.status(401).json({ error: 'Invalid credentials' });

    const user = rows[0];

    const match = await bcrypt.compare(password, user.password);
    if (!match)
      return res.status(401).json({ error: 'Invalid credentials' });

    const token = jwt.sign({ id: user.id, email }, JWT_SECRET, { expiresIn: '7d' });


    res.json({ token });

  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Server error' });
  }
};

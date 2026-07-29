const express = require('express');
const cors = require('cors');

// Rotas
const paymentRoutes = require('./routes/paymentRoutes');

const app = express();

app.use(cors());
app.use(express.json());

// Rotas da API
app.use('/api', paymentRoutes);

// Porta do servidor
app.listen(3000, () => {
  console.log('Backend running on port 3000');
});


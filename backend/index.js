require('dotenv').config();

const path = require('path');
const fs = require('fs');
const express = require('express');
const cors = require('cors');
const healthRouter = require('./routes/health');

const app = express();
const PORT = Number(process.env.PORT) || 3000;

const distPath = path.join(__dirname, '../frontend/dist');

app.use(cors());
app.use(express.json());

app.use('/api', healthRouter);
app.use('/api', (req, res) => {
  res.status(404).json({ error: true, message: 'Não encontrado' });
});

if (fs.existsSync(distPath)) {
  app.use(express.static(distPath, { index: false }));
  app.use((req, res, next) => {
    if (req.method !== 'GET' && req.method !== 'HEAD') return next();
    if (req.path.startsWith('/api')) return next();
    res.sendFile(path.join(distPath, 'index.html'), (err) => next(err));
  });
}

app.use((req, res) => {
  res.status(404).json({ error: true, message: 'Não encontrado' });
});

app.use((err, req, res, _next) => {
  console.error(err);
  const isProd = process.env.NODE_ENV === 'production';
  res.status(err.statusCode || err.status || 500).json({
    error: true,
    message: isProd ? 'Erro interno do servidor' : err.message,
  });
});

app.listen(PORT, () => {
  console.log(`NPS — API em http://localhost:${PORT}`);
});

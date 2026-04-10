const express = require('express');
const { query } = require('../db');

const router = express.Router();

router.get('/health', (req, res) => {
  res.json({
    data: {
      ok: true,
      app: 'nps',
      ts: new Date().toISOString(),
    },
  });
});

router.get('/db-teste', async (req, res) => {
  try {
    const resultado = await query('SELECT NOW() as agora');
    res.json({ banco: 'conectado', horario: resultado.rows[0].agora });
  } catch (erro) {
    res.status(500).json({ banco: 'erro', detalhe: erro.message });
  }
});

module.exports = router;
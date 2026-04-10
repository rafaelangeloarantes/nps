-- NPS — schema inicial PostgreSQL
-- Criar DB: CREATE DATABASE nps;
-- Aplicar: psql -h HOST -U USER -d nps -f sql/schema.sql

CREATE EXTENSION IF NOT EXISTS pgcrypto;

-- Tabela de exemplo (substitua ou amplie conforme o domínio do app)
CREATE TABLE IF NOT EXISTS exemplo (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  nome TEXT NOT NULL,
  criado_em TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS idx_exemplo_criado_em ON exemplo (criado_em DESC);

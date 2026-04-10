# Checklist — início de projeto (Node + React + Postgres)

Marque mentalmente ou copie para um issue/nota.

## Antes de codar

- [ ] Node.js e npm ok (`node -v`, `npm -v`)
- [ ] PostgreSQL rodando; banco criado
- [ ] Pasta do projeto aberta no Cursor com `.cursor/` presente

## Backend (`backend/`)

- [ ] `npm init` + dependências: `express`, `pg`, `dotenv`, `cors`
- [ ] `index.js` com CORS, `express.json()`, rotas sob `/api`
- [ ] `db.js` com `Pool` e variáveis de ambiente
- [ ] `.env.example` + `.env` local (gitignore em `.env`)

## Banco (`sql/`)

- [ ] `schema.sql` (ou migração inicial) escrito
- [ ] Script aplicado no Postgres (`psql` ou ferramenta gráfica)

## Frontend (`frontend/`)

- [ ] Vite + React criado e `npm install`
- [ ] `vite.config.js` com `proxy` `/api` → URL do backend

## Rodar

- [ ] Backend sobe sem erro
- [ ] Frontend sobe sem erro
- [ ] Health ou endpoint simples responde (direto ou via `/api`)

## Lembrete Cursor

- [ ] Comando **scaffold-node-react-postgres** se quiser o agente preencher o que faltar
- [ ] Guia completo: [inicio-projeto-node-react-postgres.md](inicio-projeto-node-react-postgres.md)

---
name: node-react-postgres-scaffold
description: >-
  Scaffolds monorepo-style apps with Node.js (Express), Vite + React frontend,
  and PostgreSQL via pg and env-based config. Use when starting a new
  full-stack project, when the user asks for basic folder structure, or when
  Node.js, React, and PostgreSQL are mentioned together for a new app.
---

# Node + React + PostgreSQL — scaffold

## Documentação para consulta (humano)

- Guia passo a passo: [inicio-projeto-node-react-postgres.md](../../guias/inicio-projeto-node-react-postgres.md)
- Checklist rápida: [checklist-inicio.md](../../guias/checklist-inicio.md)
- Índice da pasta `.cursor`: [README.md](../../README.md)

## Target layout (match this repo)

```
projeto/
├── backend/
│   ├── package.json
│   ├── .env.example
│   ├── index.js              # or src/server.js if user prefers src/
│   ├── db.js                 # Pool + query helper
│   └── routes/               # optional: one file per resource
├── frontend/
│   ├── package.json
│   ├── vite.config.js
│   ├── index.html
│   └── src/
│       ├── main.jsx
│       ├── App.jsx
│       └── assets/
└── sql/
    └── schema.sql            # DDL inicial (extensions, tabelas)
```

## Execution order

1. **Backend**
   - `npm init -y` in `backend/` if missing.
   - Dependencies: `express`, `pg`, `dotenv`, `cors`.
   - Scripts: `"start": "node index.js"`, `"dev": "node --watch index.js"` (Node 18+).
   - `db.js`: `Pool` from `pg`, read `DATABASE_URL` or `PGHOST`, `PGUSER`, `PGPASSWORD`, `PGDATABASE`, `PGPORT` from `process.env`.
   - `index.js`: load `dotenv`, `express` + `cors`, JSON middleware, health route `GET /api/health`, mount routes under `/api`.
   - `.env.example` with commented placeholders (no real secrets).

2. **Frontend**
   - If missing: `npm create vite@latest frontend -- --template react` (or align with existing React version in repo).
   - `vite.config.js`: `server.proxy` for `/api` → `http://localhost:PORT` (same as backend default, e.g. 3000 or 5000).

3. **PostgreSQL**
   - `sql/schema.sql`: `CREATE EXTENSION IF NOT EXISTS` if needed; tables with `TIMESTAMPTZ`, explicit PKs, indexes for FKs.
   - Document in comments how to apply: `psql $DATABASE_URL -f sql/schema.sql`.

4. **Root (optional)**
   - Root `README` only if user explicitly wants docs; otherwise skip.

## Conventions

- **Secrets**: only in `.env` (gitignored); never commit credentials.
- **API**: JSON REST under `/api`; use parameterized queries (`pool.query` with `$1`, `$2`).
- **CORS**: restrict `origin` in production; `*` acceptable only for local dev.
- **Errors**: centralize error middleware on Express; avoid leaking stack traces in production.

## Verification

- Backend starts without DB if health route does not query DB; DB routes fail gracefully with clear logs if env missing.
- Frontend `npm run dev` proxies API correctly.
- User can run schema against a local Postgres instance.

## When user already has partial structure

- Extend only missing folders/files; do not replace working `package.json` without reason.
- Prefer the existing module style (`"type": "module"` vs CommonJS) already in the repo.

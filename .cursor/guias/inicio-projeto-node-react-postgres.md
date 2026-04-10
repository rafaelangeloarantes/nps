# Como iniciar um novo projeto (Node.js + React + PostgreSQL)

Guia para consulta quando você não lembrar a ordem dos passos. A stack assumida é a deste repositório: **Express + `pg`** no `backend/`, **Vite + React** no `frontend/`, DDL em `sql/`.

---

## 1. Pré-requisitos

- **Node.js** (LTS recomendado; Node 18+ para `node --watch` no backend).
- **npm** (vem com o Node).
- **PostgreSQL** instalado e acessível (local ou remoto).
- Opcional: **Git** para versionar; **Cursor** com os arquivos desta pasta `.cursor` no projeto.

---

## 2. Duas formas de começar

### A) Pelo Cursor (recomendado se quiser o agente montar arquivos)

1. Abra o projeto na raiz (onde existem ou existirão `backend/` e `frontend/`).
2. Use a paleta de **Custom Commands** e execute **scaffold-node-react-postgres**  
   *(arquivo: `.cursor/commands/scaffold-node-react-postgres.md`)*.
3. Ou diga no chat algo como: *criar estrutura inicial Node, Express, React Vite e PostgreSQL conforme a skill do projeto*.

O agente deve seguir `skills/node-react-postgres-scaffold/SKILL.md` e as regras em `rules/`.

### B) Manual (linha de comando)

Siga a ordem da seção 4 abaixo.

---

## 3. Estrutura de pastas alvo

```
projeto/
├── backend/
│   ├── package.json
│   ├── .env                 ← local, não commitar (use .gitignore)
│   ├── .env.example         ← commitar, só placeholders
│   ├── index.js
│   ├── db.js
│   └── routes/              ← opcional
├── frontend/
│   ├── package.json
│   ├── vite.config.js
│   └── src/
└── sql/
    └── schema.sql
```

---

## 4. Ordem sugerida (manual)

1. **Criar o banco** no PostgreSQL (ex.: `CREATE DATABASE nome_db;`).
2. **Backend**
   - `mkdir backend && cd backend`
   - `npm init -y`
   - `npm install express pg dotenv cors`
   - Criar `db.js` (pool `pg`), `index.js` (Express, CORS, JSON, rotas `/api`), `.env.example`.
   - Copiar `.env.example` para `.env` e preencher credenciais/url.
3. **SQL**
   - `mkdir sql` na raiz (se ainda não existir).
   - Criar `sql/schema.sql` e aplicar:  
     `psql "sua_connection_string" -f sql/schema.sql`  
     (no Windows PowerShell você pode usar variável de ambiente ou `-h -U -d` conforme sua instalação).
4. **Frontend**
   - Na raiz do projeto:  
     `npm create vite@latest frontend -- --template react`
   - `cd frontend && npm install`
   - Em `vite.config.js`, configurar `server.proxy` para enviar `/api` para a URL do backend (ex.: `http://localhost:3000`).
5. **Rodar em desenvolvimento**
   - Terminal 1 — backend: `cd backend && npm run dev` (ou `node --watch index.js` se o script existir).
   - Terminal 2 — frontend: `cd frontend && npm run dev`.
6. **Conferir**
   - Abrir a URL do Vite no navegador.
   - Testar `GET /api/health` (ou rota equivalente) no backend direto ou via proxy `/api/health` no front.

---

## 5. Variáveis de ambiente (backend)

Costuma funcionar **uma** destas abordagens:

- **`DATABASE_URL`** — string completa `postgresql://usuario:senha@host:porta/banco`
- Ou variáveis separadas: `PGHOST`, `PGPORT`, `PGUSER`, `PGPASSWORD`, `PGDATABASE`

Documente sempre em **`.env.example`** sem valores reais. Nunca commite `.env`.

---

## 6. Portas (exemplo)

Defina uma porta fixa no backend (ex.: **3000**) e use a **mesma** no `proxy` do Vite. Anote no `README` pessoal ou no guia da equipe para não conflitar com outros serviços.

---

## 7. Nova tabela ou mudança no banco

- Prefira novos arquivos em `sql/` (ex.: `002_descricao.sql`) em vez de editar só de memória.
- No Cursor, use o comando **add-postgres-migration** para alinhar o padrão.

---

## 8. Onde está cada coisa neste repositório

| Tipo | Caminho |
|------|---------|
| Índice da pasta `.cursor` | `.cursor/README.md` |
| Checklist rápida | `.cursor/guias/checklist-inicio.md` |
| Comandos Cursor | `.cursor/commands/*.md` |
| Regras Cursor | `.cursor/rules/*.mdc` |
| Skill do scaffold | `.cursor/skills/node-react-postgres-scaffold/SKILL.md` |

---

## 9. Projeto totalmente novo (outra pasta)

1. Crie a pasta do projeto.
2. Copie **esta pasta `.cursor`** para dentro dela.
3. Use o comando **scaffold-node-react-postgres** ou siga a seção 4 manualmente.

Assim você mantém guias, regras e comandos iguais aos deste modelo.

---

## 10. Problemas comuns

| Sintoma | O que verificar |
|---------|------------------|
| `ECONNREFUSED` ou erro ao conectar no Postgres | Serviço PostgreSQL ativo; `.env` com host/porta/usuário/senha corretos; banco criado. |
| Frontend chama API e dá 404 ou HTML em vez de JSON | `server.proxy` no Vite apontando para a **porta certa** do Express; URL começando com `/api`. |
| CORS no navegador | Em dev, CORS no Express deve permitir a origem do Vite (ex.: `http://localhost:5173`); em produção, liste origens explícitas. |
| Mudança no `.env` não surte efeito | Reinicie o processo do Node (o servidor não recarrega variáveis sozinho em muitos setups). |

# Pasta `.cursor` — o que existe aqui

Use este índice quando não lembrar **como iniciar** ou **onde está** cada orientação.

## Consulta rápida (você)

| O que preciso | Onde abrir |
|---------------|------------|
| Passo a passo para começar um projeto novo (Node + React + Postgres) | [guias/inicio-projeto-node-react-postgres.md](guias/inicio-projeto-node-react-postgres.md) |
| Checklist mínima em uma página | [guias/checklist-inicio.md](guias/checklist-inicio.md) |

## Cursor — comandos customizados

Arquivos em `commands/` aparecem na paleta de **Custom Commands**:

- **scaffold-node-react-postgres** — pede ao agente para gerar/completar a estrutura base (backend, frontend, `sql/`, `.env.example`).
- **add-postgres-migration** — orienta a adicionar um novo arquivo SQL em `sql/`.

## Cursor — regras (`.mdc`)

Em `rules/`; definem padrões quando você edita certos arquivos:

- `stack-node-react-postgres.mdc` — visão geral do monorepo (**sempre** considerada neste repo).
- `backend-express-pg.mdc` — backend `backend/**/*.js`.
- `frontend-react-vite.mdc` — frontend `frontend/**/*.{jsx,tsx,js,ts}`.
- `sql-postgres.mdc` — `sql/**/*.sql`.

## Cursor — skill do agente

- `skills/node-react-postgres-scaffold/SKILL.md` — instruções para o **agente** montar o scaffold; inclui link para o guia humano.

---

**Dica:** em um projeto novo, copie a pasta `.cursor` inteira para a raiz do repositório para manter os mesmos guias, regras e comandos.

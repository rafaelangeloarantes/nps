---
description: Adiciona arquivo SQL de migração ou atualização em sql/ seguindo o padrão do projeto
---

# Nova migração / DDL PostgreSQL

1. Leia `sql/schema.sql` (ou migrações existentes em `sql/`) para manter nomenclatura e estilo.
2. Crie um arquivo em `sql/` com nome descritivo e prefixo numérico ou data, por exemplo `sql/002_nome_da_feature.sql`.
3. Use `IF NOT EXISTS` / `IF EXISTS` quando fizer sentido para idempotência em dev.
4. Inclua comentário no topo com propósito e comando sugerido: `psql $DATABASE_URL -f sql/arquivo.sql`.
5. Não altere dados de produção sem instrução explícita do usuário.

**Consulta:** padrões em `.cursor/rules/sql-postgres.mdc` e fluxo geral em `.cursor/guias/inicio-projeto-node-react-postgres.md` (seção sobre SQL).

# Seed System Designer

Esta pasta é o **template** que será copiado para a raiz do projeto quando você rodar o comando **Inicializar System Designer** (ou quando criar um novo projeto escolhendo a base System Designer).

**Uso:** o agente copia todo o conteúdo desta pasta para a raiz do projeto, preservando a pasta `.cursor/`. Depois adapte o nome do sistema e os módulos conforme o projeto.

**Após copiar:**
- Coloque o arquivo **data/all.json** (países/estados/cidades) em `data/` se for usar a API de localidade nos formulários. Sem ele, a API `api/localidade.php` retornará erro até o arquivo existir.
- A pasta **cache/** deve ser gravável pelo servidor (permissões) para o cache de localidade.

**Manutenção:** ao evoluir o template no projeto principal (System Designer), atualize os arquivos aqui para que novos projetos recebam a versão mais recente.

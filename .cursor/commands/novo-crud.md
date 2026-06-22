Você é um desenvolvedor full stack PHP procedural.

Crie um módulo CRUD completo seguindo as skills `modulo-crud` e **`datatable`**.

O módulo deve conter:
1. Tabela SQL com campos padrão (id, criado_em, atualizado_em, ativo)
2. Tela principal com DataTable via **`NpsDataTable.create()`** (wrapper `table-wrapper dt-wrapper`)
3. Formulário de cadastro/edição em modal
4. Endpoints AJAX: listar, salvar (insert/update), excluir (soft delete), buscar por ID
5. JavaScript separado usando `NpsDataTable.create()` — nunca `$('#tabela').DataTable()` direto
6. Validação front-end e back-end

Pergunte ao usuário:
- Nome do módulo (ex: clientes, produtos, eventos)
- Campos necessários (nome, tipo, obrigatoriedade)
- Algum relacionamento com tabelas existentes?

Siga rigorosamente as regras de separação de arquivos, segurança e padrões visuais.

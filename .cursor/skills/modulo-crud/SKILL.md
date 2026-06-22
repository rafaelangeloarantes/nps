# Skill: Módulo CRUD Completo

Use esta skill quando o usuário pedir para criar um módulo, cadastro, CRUD ou tela de gestão de dados.

## Estrutura do Módulo

Para cada módulo CRUD (ex: `usuarios`), criar:

```
/modules/usuarios/
  index.php          → tela principal com DataTable
  form.php           → formulário de cadastro/edição
  functions.php      → funções específicas do módulo

/ajax/
  usuarios_listar.php
  usuarios_salvar.php
  usuarios_excluir.php
  usuarios_buscar.php

/sql/structure/
  usuarios.sql       → CREATE TABLE

/js/
  usuarios.js        → scripts específicos do módulo

/css/
  (usar style.css global, adicionar classes específicas se necessário)
```

## Padrão da Tabela SQL

```sql
CREATE TABLE IF NOT EXISTS `usuarios` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nome` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `atualizado_em` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `ativo` TINYINT(1) DEFAULT 1,
    UNIQUE KEY `idx_usuarios_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Padrão do DataTable (tela principal)

**Obrigatório:** ler e seguir a skill `datatable` (`.cursor/skills/datatable/SKILL.md`).

A tela principal deve incluir:
- DataTables com server-side processing via AJAX
- Inicialização via **`NpsDataTable.create()`** (`js/datatable-config.js`)
- Filtro por campo de busca e paginação (toolbar/footer automáticos)
- Botão "Novo" para abrir formulário
- Coluna de ações (Editar / Excluir) com `<th class="dt-no-sort">`

### HTML padrão

```html
<div class="card">
    <div class="card-body">
        <div class="table-wrapper dt-wrapper">
            <table id="tabelaModulo" class="display" width="100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th class="dt-no-sort">Ações</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
```

### JavaScript padrão

```javascript
tabela = NpsDataTable.create('#tabelaModulo', {
    processing: true,
    serverSide: true,
    ajax: 'ajax/admin/modulo_listar.php',
    columns: [ /* ... */ ]
});
```

### Dependências (já no `index.php` do painel)

```html
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="css/style.css">
<link rel="stylesheet" href="css/datatable-override.css">
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="js/datatable-config.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
```

**Não usar** `dataTables.bootstrap5` — o visual é do design system (`style.css` + `datatable-override.css`).

## Padrão AJAX - Listar

```php
<?php
// ajax/usuarios_listar.php
require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$busca = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
$inicio = filter_input(INPUT_GET, 'start', FILTER_VALIDATE_INT) ?? 0;
$limite = filter_input(INPUT_GET, 'length', FILTER_VALIDATE_INT) ?? 10;

// Total geral
$total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM usuarios WHERE ativo = 1"))['total'];

// Query com busca
$sql = "SELECT id, nome, email, criado_em FROM usuarios WHERE ativo = 1";
if (!empty($busca)) {
    $busca_safe = mysqli_real_escape_string($conn, $busca);
    $sql .= " AND (nome LIKE '%{$busca_safe}%' OR email LIKE '%{$busca_safe}%')";
}

// Total filtrado
$total_filtrado = mysqli_fetch_assoc(mysqli_query($conn, str_replace('SELECT id, nome, email, criado_em', 'SELECT COUNT(*) as total', $sql)))['total'];

$sql .= " ORDER BY id DESC LIMIT {$inicio}, {$limite}";
$result = mysqli_query($conn, $sql);

$dados = [];
while ($row = mysqli_fetch_assoc($result)) {
    $dados[] = $row;
}

echo json_encode([
    'draw' => intval($_GET['draw'] ?? 1),
    'recordsTotal' => intval($total),
    'recordsFiltered' => intval($total_filtrado),
    'data' => $dados
]);
exit;
```

## Padrão AJAX - Salvar (Insert/Update)

```php
<?php
// ajax/usuarios_salvar.php
require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$id    = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$nome  = trim(filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$email = trim(filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL) ?? '');

// Validações
if (empty($nome) || empty($email)) {
    echo json_encode(['status' => 'error', 'message' => 'Preencha todos os campos obrigatórios.']);
    exit;
}

if ($id > 0) {
    // UPDATE
    $stmt = mysqli_prepare($conn, "UPDATE usuarios SET nome = ?, email = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'ssi', $nome, $email, $id);
    $msg = 'Registro atualizado com sucesso.';
} else {
    // INSERT
    $stmt = mysqli_prepare($conn, "INSERT INTO usuarios (nome, email) VALUES (?, ?)");
    mysqli_stmt_bind_param($stmt, 'ss', $nome, $email);
    $msg = 'Registro cadastrado com sucesso.';
}

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['status' => 'success', 'message' => $msg]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Erro ao salvar: ' . mysqli_error($conn)]);
}
mysqli_stmt_close($stmt);
exit;
```

## Padrão AJAX - Excluir (Soft Delete)

```php
<?php
// ajax/usuarios_excluir.php
require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'ID inválido.']);
    exit;
}

$stmt = mysqli_prepare($conn, "UPDATE usuarios SET ativo = 0 WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['status' => 'success', 'message' => 'Registro excluído com sucesso.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Erro ao excluir.']);
}
mysqli_stmt_close($stmt);
exit;
```

## Confirmação com SweetAlert2

```javascript
// Padrão de exclusão com confirmação
function excluirRegistro(id) {
    Swal.fire({
        title: 'Tem certeza?',
        text: 'Esta ação não poderá ser desfeita.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Sim, excluir',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('ajax/usuarios_excluir.php', { id: id }, function(response) {
                if (response.status === 'success') {
                    Swal.fire('Excluído!', response.message, 'success');
                    tabela.ajax.reload();
                } else {
                    Swal.fire('Erro', response.message, 'error');
                }
            }, 'json');
        }
    });
}
```

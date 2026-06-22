<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../modules/auth/middleware.php';
require_once __DIR__ . '/../modules/auth/permissoes.php';
require_once __DIR__ . '/../modules/pesquisas/functions.php';

exigir_admin_master();

$id = (int) ($_POST['id'] ?? 0);
if ($id > 0) {
    $row = mysqli_fetch_assoc(mysqli_query($conn, 'SELECT evento_id FROM pesquisas WHERE id = ' . $id . ' LIMIT 1'));
    if ($row) {
        exigir_acesso_evento($conn, (int) $row['evento_id']);
    }
}

$resultado = pesquisa_salvar($conn, $_POST);
json_response($resultado['status'], $resultado['message'], ['id' => $resultado['id'] ?? null]);

<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../modules/auth/middleware.php';
require_once __DIR__ . '/../modules/auth/permissoes.php';
require_once __DIR__ . '/../modules/integracao/pesquisas_sync.php';

exigir_admin_master();

$id = (int) ($_POST['id'] ?? $_POST['pesquisa_id'] ?? 0);
if ($id <= 0) {
    json_response('error', 'ID inválido.');
}

$row = mysqli_fetch_assoc(mysqli_query($conn, 'SELECT evento_id FROM pesquisas WHERE id = ' . $id . ' LIMIT 1'));
if ($row) {
    exigir_acesso_evento($conn, (int) $row['evento_id']);
}

$resultado = pesquisas_sincronizar_por_id($conn, $id);
json_response($resultado['status'], $resultado['message'], $resultado['data'] ?? null);

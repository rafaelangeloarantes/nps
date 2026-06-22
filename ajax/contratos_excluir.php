<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../modules/auth/middleware.php';
require_once __DIR__ . '/../modules/auth/permissoes.php';
require_once __DIR__ . '/../modules/contratos/functions.php';

exigir_admin_master();

$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0) {
    json_response('error', 'ID inválido.');
}
$resultado = contrato_excluir($conn, $id);
json_response($resultado['status'], $resultado['message']);

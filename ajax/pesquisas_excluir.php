<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../modules/auth/middleware.php';
require_once __DIR__ . '/../modules/pesquisas/functions.php';

$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0) {
    json_response('error', 'ID inválido.');
}
$resultado = pesquisa_excluir($conn, $id);
json_response($resultado['status'], $resultado['message']);

<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../modules/auth/middleware.php';
require_once __DIR__ . '/../modules/pesquisas/campos.php';

$pesquisa_id = (int) ($_GET['pesquisa_id'] ?? $_POST['pesquisa_id'] ?? 0);
if ($pesquisa_id <= 0) {
    json_response('error', 'Pesquisa inválida.');
}

$lista = pesquisa_campo_listar($conn, $pesquisa_id);
json_response('success', 'OK', [
    'campos' => $lista,
    'tipos_grafico' => pesquisa_campo_tipos_grafico(),
]);

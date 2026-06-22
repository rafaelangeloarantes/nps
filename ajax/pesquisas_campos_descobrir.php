<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../modules/auth/middleware.php';
require_once __DIR__ . '/../modules/integracao/pesquisas_sync.php';

$pesquisa_id = (int) ($_GET['pesquisa_id'] ?? $_POST['pesquisa_id'] ?? 0);
if ($pesquisa_id <= 0) {
    json_response('error', 'Pesquisa inválida.');
}

$resultado = pesquisa_descobrir_campos_api($conn, $pesquisa_id);
json_response($resultado['status'], $resultado['message'], $resultado['data'] ?? null);

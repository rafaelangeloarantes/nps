<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../modules/auth/middleware.php';
require_once __DIR__ . '/../modules/integracao/pesquisas_sync.php';

$pesquisa_id = (int) ($_GET['pesquisa_id'] ?? 0);
$identificador = trim($_GET['identificador'] ?? '');

if ($pesquisa_id <= 0 && $identificador === '') {
    json_response('error', 'Informe pesquisa_id ou identificador.');
}

if ($pesquisa_id > 0) {
    $resultado = pesquisa_descobrir_campos_api($conn, $pesquisa_id);
    json_response($resultado['status'], $resultado['message'], $resultado['data']['campos'] ?? null);
}

json_response('error', 'Use a tela de mapeamento para descobrir campos (pesquisa_id obrigatório).');

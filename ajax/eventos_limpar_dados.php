<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../modules/auth/middleware.php';
require_once __DIR__ . '/../modules/eventos/limpar_dados.php';

$evento_id = (int) ($_POST['evento_id'] ?? 0);
if ($evento_id <= 0) {
    json_response('error', 'Evento inválido.');
}

$remover_orfaos = !isset($_POST['remover_orfaos']) || (int) $_POST['remover_orfaos'] === 1;

$resultado = evento_limpar_dados_vinculados($conn, $evento_id, (bool) $remover_orfaos);
json_response($resultado['status'], $resultado['message'], $resultado['data'] ?? null);

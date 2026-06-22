<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../modules/auth/middleware.php';
require_once __DIR__ . '/../modules/eventos/limpar_dados.php';

$evento_id = (int) ($_GET['evento_id'] ?? $_POST['evento_id'] ?? 0);
if ($evento_id <= 0) {
    json_response('error', 'Evento inválido.');
}

$resultado = evento_resumo_dados_vinculados($conn, $evento_id);
if ($resultado['status'] === 'success') {
    json_response('success', 'Resumo carregado.', $resultado['data']);
}
json_response($resultado['status'], $resultado['message'] ?? 'Erro ao carregar resumo.');

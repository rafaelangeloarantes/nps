<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../modules/auth/middleware.php';
require_once __DIR__ . '/../modules/auth/permissoes.php';
require_once __DIR__ . '/../modules/participantes/functions.php';

exigir_admin_master();

$id = (int) ($_POST['id'] ?? 0);

$dados = $_POST;
if (isset($_POST['eventos_ids']) && is_string($_POST['eventos_ids'])) {
    $dados['eventos_ids'] = json_decode($_POST['eventos_ids'], true) ?: [];
}

$resultado = participante_salvar($conn, $dados);
participante_recalcular_incompletos($conn);
json_response($resultado['status'], $resultado['message'], ['id' => $resultado['id'] ?? null]);

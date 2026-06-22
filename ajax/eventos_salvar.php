<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../modules/auth/middleware.php';
require_once __DIR__ . '/../modules/auth/permissoes.php';
require_once __DIR__ . '/../modules/eventos/functions.php';

exigir_admin_master();

$id = (int) ($_POST['id'] ?? 0);
if ($id > 0) {
    exigir_acesso_evento($conn, $id);
}
if (!empty($_POST['contrato_id'])) {
    exigir_acesso_contrato($conn, (int) $_POST['contrato_id']);
}

$resultado = evento_salvar($conn, $_POST);
json_response($resultado['status'], $resultado['message'], ['id' => $resultado['id'] ?? null]);

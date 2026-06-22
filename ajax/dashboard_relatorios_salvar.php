<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../modules/auth/middleware.php';
require_once __DIR__ . '/../modules/auth/permissoes.php';
require_once __DIR__ . '/../modules/dashboard/relatorios.php';

if (!eh_admin_master()) {
    json_response('error', 'Apenas Administrador Master pode criar ou editar relatórios.');
}

$evento_id = (int) ($_POST['evento_id'] ?? 0);
if ($evento_id > 0) {
    exigir_acesso_evento($conn, $evento_id);
}

$resultado = dashboard_relatorio_salvar($conn, $_POST);
if ($resultado['status'] === 'success') {
    json_response('success', $resultado['message'], $resultado['data'] ?? null);
}
json_response('error', $resultado['message']);

<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../modules/auth/middleware.php';
require_once __DIR__ . '/../modules/auth/permissoes.php';
require_once __DIR__ . '/../modules/dashboard/relatorios.php';

if (!eh_admin_master()) {
    json_response('error', 'Apenas Administrador Master pode excluir relatórios.');
}

$id = (int) ($_POST['id'] ?? 0);
$rel = dashboard_relatorio_buscar($conn, $id);
if ($rel) {
    exigir_acesso_evento($conn, (int) $rel['evento_id']);
}

$resultado = dashboard_relatorio_excluir($conn, $id);
json_response($resultado['status'], $resultado['message']);

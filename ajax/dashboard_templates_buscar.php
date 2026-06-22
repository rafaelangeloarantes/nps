<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../modules/auth/middleware.php';
require_once __DIR__ . '/../modules/auth/permissoes.php';
require_once __DIR__ . '/../modules/dashboard/templates.php';

exigir_admin_master();

$id = (int) ($_GET['id'] ?? 0);
$row = dashboard_template_buscar($conn, $id);
if (!$row) {
    json_response('error', 'Template não encontrado.');
}

json_response('success', 'OK', [
    'id' => (int) $row['id'],
    'nome' => $row['nome'],
    'descricao' => $row['descricao'],
    'contrato_id' => $row['contrato_id'],
    'widgets' => $row['widgets'],
]);

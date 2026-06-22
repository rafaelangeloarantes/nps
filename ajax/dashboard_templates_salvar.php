<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../modules/auth/middleware.php';
require_once __DIR__ . '/../modules/auth/permissoes.php';
require_once __DIR__ . '/../modules/dashboard/templates.php';

exigir_admin_master();

$payload = $_POST;
if (!empty($_POST['widgets']) && is_string($_POST['widgets'])) {
    $decoded = json_decode($_POST['widgets'], true);
    $payload['widgets'] = is_array($decoded) ? $decoded : [];
}

$resultado = dashboard_template_salvar($conn, $payload);
json_response($resultado['status'], $resultado['message'], ['id' => $resultado['id'] ?? null]);

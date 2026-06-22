<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../modules/auth/middleware.php';
require_once __DIR__ . '/../modules/auth/permissoes.php';
require_once __DIR__ . '/../modules/dashboard/templates.php';

exigir_admin_master();

$id = (int) ($_POST['id'] ?? 0);
$resultado = dashboard_template_excluir($conn, $id);
json_response($resultado['status'], $resultado['message']);

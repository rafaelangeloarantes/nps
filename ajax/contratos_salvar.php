<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../modules/auth/middleware.php';
require_once __DIR__ . '/../modules/auth/permissoes.php';
require_once __DIR__ . '/../modules/contratos/functions.php';

exigir_admin_master();

$resultado = contrato_salvar($conn, $_POST);
json_response($resultado['status'], $resultado['message'], ['id' => $resultado['id'] ?? null]);

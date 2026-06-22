<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../modules/auth/middleware.php';
require_once __DIR__ . '/../modules/auth/permissoes.php';
require_once __DIR__ . '/../modules/credenciamentos/functions.php';

exigir_admin_master();

$id = (int) ($_POST['id'] ?? 0);

$resultado = credenciamento_salvar($conn, $_POST);
json_response($resultado['status'], $resultado['message'], ['id' => $resultado['id'] ?? null]);

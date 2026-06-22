<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../modules/auth/middleware.php';
require_once __DIR__ . '/../modules/auth/permissoes.php';
require_once __DIR__ . '/../modules/campos_padrao/functions.php';

exigir_admin_master();

$id = (int) ($_GET['id'] ?? 0);
$row = campo_padrao_buscar($conn, $id);
if (!$row) {
    json_response('error', 'Campo não encontrado.');
}
json_response('success', 'OK', $row);

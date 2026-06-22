<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../modules/auth/middleware.php';
require_once __DIR__ . '/../modules/auth/permissoes.php';
require_once __DIR__ . '/../modules/usuarios/functions.php';

exigir_admin_master();

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    json_response('error', 'ID inválido.');
}

$row = usuario_buscar_por_id($conn, $id);
if (!$row) {
    json_response('error', 'Usuário não encontrado.');
}

json_response('success', 'OK', $row);

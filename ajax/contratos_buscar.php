<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../modules/auth/middleware.php';
require_once __DIR__ . '/../modules/contratos/functions.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    json_response('error', 'ID inválido.');
}
$row = contrato_buscar_por_id($conn, $id);
if (!$row) {
    json_response('error', 'Contrato não encontrado.');
}
unset($row['senha_acesso']);
json_response('success', 'OK', $row);

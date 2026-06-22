<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../modules/auth/middleware.php';
require_once __DIR__ . '/../modules/eventos/functions.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    json_response('error', 'ID inválido.');
}
$row = evento_buscar_por_id($conn, $id);
if (!$row) {
    json_response('error', 'Evento não encontrado.');
}
unset($row['senha_acesso']);
json_response('success', 'OK', $row);

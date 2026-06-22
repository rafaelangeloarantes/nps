<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../modules/auth/middleware.php';
require_once __DIR__ . '/../modules/integracoes/functions.php';

$id = (int) ($_GET['id'] ?? 0);
$codigo = trim($_GET['codigo'] ?? '');

if ($id > 0) {
    $row = integracao_buscar_por_id($conn, $id);
} elseif ($codigo !== '') {
    $row = integracao_buscar_por_codigo($conn, $codigo);
} else {
    json_response('error', 'Parâmetro inválido.');
}

if (!$row) {
    json_response('error', 'Integração não encontrada.');
}

unset($row['senha_acesso']);
json_response('success', 'OK', $row);

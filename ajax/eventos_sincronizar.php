<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../modules/auth/middleware.php';
require_once __DIR__ . '/../modules/auth/permissoes.php';
require_once __DIR__ . '/../modules/eventos/functions.php';
require_once __DIR__ . '/../modules/log/functions.php';

exigir_admin_master();

$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0) {
    json_response('error', 'ID inválido.');
}
exigir_acesso_evento($conn, $id);

evento_marcar_sincronizacao($conn, $id);

log_sincronizacao(
    $conn,
    'evento',
    $id,
    'manual',
    'sucesso',
    'Sincronização agendada — integração pendente.'
);

json_response('success', 'Sincronização registrada. A integração com API/arquivo será implementada na próxima etapa.');

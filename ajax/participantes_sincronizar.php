<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../modules/auth/middleware.php';
require_once __DIR__ . '/../modules/log/functions.php';

$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0) {
    json_response('error', 'ID inválido.');
}

$stmt = mysqli_prepare($conn, 'UPDATE participantes SET ultima_sincronizacao = NOW() WHERE id = ?');
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

log_sincronizacao(
    $conn,
    'participante',
    $id,
    'manual',
    'sucesso',
    'Sincronização agendada — integração pendente.'
);

json_response('success', 'Sincronização registrada. A integração com API/arquivo será implementada na próxima etapa.');

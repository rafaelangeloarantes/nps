<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../modules/auth/middleware.php';
require_once __DIR__ . '/../modules/participantes/functions.php';
require_once __DIR__ . '/../modules/pesquisas/respostas.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    json_response('error', 'ID inválido.');
}
$row = participante_buscar_por_id($conn, $id);
if (!$row) {
    json_response('error', 'Participante não encontrado.');
}
$row['eventos_ids'] = array_column($row['eventos'], 'id');
$row['pesquisas_respostas'] = pesquisa_resposta_listar_por_participante($conn, $id);
json_response('success', 'OK', $row);

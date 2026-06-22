<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../modules/auth/middleware.php';
require_once __DIR__ . '/../modules/pesquisas/respostas.php';

$participante_id = (int) ($_GET['participante_id'] ?? 0);
$evento_id = (int) ($_GET['evento_id'] ?? 0);

if ($participante_id <= 0) {
    json_response('error', 'Participante inválido.');
}

$lista = pesquisa_resposta_listar_por_participante($conn, $participante_id, $evento_id);
json_response('success', 'OK', ['respostas' => $lista]);

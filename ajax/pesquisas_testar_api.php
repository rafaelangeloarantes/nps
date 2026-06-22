<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../modules/auth/middleware.php';
require_once __DIR__ . '/../modules/pesquisas/functions.php';
require_once __DIR__ . '/../modules/integracao/pesquisas_sync.php';

$pesquisa_id = (int) ($_GET['pesquisa_id'] ?? 0);
if ($pesquisa_id <= 0) {
    json_response('error', 'Pesquisa inválida.');
}

$pesquisa = pesquisa_buscar_por_id($conn, $pesquisa_id);
if (!$pesquisa) {
    json_response('error', 'Pesquisa não encontrada.');
}

$event_id = pesquisa_event_id_api($pesquisa);
if (!$event_id['ok']) {
    json_response('error', $event_id['error']);
}

$auth = inteegra_autenticar_evento($conn, (int) $pesquisa['evento_id']);
if (!$auth['ok']) {
    json_response('error', $auth['error']);
}

$page = inteegra_buscar_guests_pagina(
    $auth['token'],
    $event_id['event_id_api'],
    1,
    1,
    $auth['guests_base'] ?? null
);

if (!$page['ok']) {
    json_response('error', $page['error']);
}

json_response(
    'success',
    'Conexão OK. EventId da pesquisa: ' . $event_id['event_id_api'],
    [
        'auth_base' => $auth['auth_base'],
        'guests_base' => $auth['guests_base'],
        'event_id_api' => $event_id['event_id_api'],
    ]
);

<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../modules/auth/middleware.php';
require_once __DIR__ . '/../modules/integracao/inteegra_client.php';

$evento_id = (int) ($_GET['evento_id'] ?? $_POST['evento_id'] ?? 0);
if ($evento_id <= 0) {
    json_response('error', 'Evento inválido.');
}

$cred = inteegra_credenciais_evento($conn, $evento_id);
if (!$cred['ok']) {
    json_response('error', $cred['error']);
}

$token_res = inteegra_obter_token($cred['login'], $cred['password'], $cred['auth_base']);
if (!$token_res['ok']) {
    json_response('error', $token_res['error']);
}

$page = inteegra_buscar_guests_pagina($token_res['token'], $cred['event_id_api'], 1, 1, $cred['guests_base']);

$total_api = (int) ($page['pagination']['TotalCount'] ?? count($page['guests'] ?? []));
$evento_api = null;
if ($page['ok'] && $total_api <= 0) {
    $evento_api = inteegra_buscar_evento_api(
        $token_res['token'],
        $cred['event_id_api'],
        $cred['guests_base']
    );
}

if ($page['ok'] && $total_api <= 0) {
    $msg_ok = inteegra_mensagem_sem_guests($cred['event_id_api'], $cred['login'], $evento_api);
} else {
    $msg_ok = 'Conexão OK. EventId ' . $cred['event_id_api'] . ' — '
        . $total_api . ' participante(s) na API. Usuário: ' . $cred['login']
        . '. Token: ' . $cred['auth_base']
        . '/api/users/token — Guests: ' . $cred['guests_base'] . '/api/guests';
}

json_response(
    $page['ok'] ? ($total_api > 0 ? 'success' : 'warning') : 'error',
    $page['ok'] ? $msg_ok : $page['error'],
    [
        'auth_base' => $cred['auth_base'],
        'guests_base' => $cred['guests_base'],
        'event_id_api' => $cred['event_id_api'],
        'total_guests_api' => $total_api,
        'evento_api_encontrado' => is_array($evento_api),
        'credencial_origem' => $cred['credencial_origem'] ?? 'integracao',
        'pagination' => $page['pagination'] ?? null,
    ]
);

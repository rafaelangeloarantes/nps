<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../modules/auth/middleware.php';
require_once __DIR__ . '/../modules/integracoes/functions.php';
require_once __DIR__ . '/../modules/integracao/inteegra_client.php';

$codigo = trim($_POST['codigo'] ?? $_GET['codigo'] ?? 'inteegra');

if ($codigo !== 'inteegra') {
    json_response('error', 'Teste disponível apenas para a integração Inteegra.');
}

$cred = integracao_credenciais($conn, $codigo);
if (!$cred['ok']) {
    json_response('error', $cred['error']);
}

$auth_base = inteegra_auth_base($cred['url_auth_base'] ?: null);
$token_res = inteegra_obter_token($cred['login'], $cred['password'], $auth_base);

if (!$token_res['ok']) {
    json_response('error', $token_res['error']);
}

json_response(
    'success',
    'Autenticação OK. Token obtido em ' . $auth_base . '/api/users/token',
    ['auth_base' => $auth_base]
);

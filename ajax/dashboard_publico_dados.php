<?php

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../modules/dashboard/relatorios.php';
require_once __DIR__ . '/../modules/dashboard/render.php';
require_once __DIR__ . '/../modules/dashboard/log.php';
require_once __DIR__ . '/../modules/dashboard/home.php';

dashboard_publico_iniciar_sessao();

header('Content-Type: application/json; charset=utf-8');

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$chave = strtoupper(trim($_POST['chave'] ?? ''));
$apenas_status = !empty($_POST['apenas_status']);

function dashboard_publico_responder(array $payload, $http_code = 200)
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    http_response_code((int) $http_code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($token === '') {
    dashboard_publico_responder(['status' => 'error', 'message' => 'Token inválido.'], 400);
}

$rel = dashboard_relatorio_buscar_por_token($conn, $token);
if (!$rel) {
    auditoria_registrar($conn, 'dashboard_relatorio', 'acesso_negado', null, ['token' => $token]);
    dashboard_publico_responder(['status' => 'error', 'message' => 'Relatório não encontrado.'], 404);
}

$rel_auth = dashboard_relatorio_buscar($conn, (int) $rel['id']);
if (!$rel_auth || empty($rel_auth['chave_hash'])) {
    dashboard_publico_responder([
        'status' => 'error',
        'message' => 'Relatório sem chave de acesso configurada. Contate o organizador.',
    ], 403);
}

$autenticado = dashboard_publico_esta_autenticado($rel_auth);

if (!$autenticado) {
    if ($chave === '') {
        dashboard_publico_responder([
            'status' => 'auth_required',
            'message' => 'Informe a chave de acesso.',
            'data' => [
                'nome' => $rel['nome'],
                'evento_nome' => $rel['evento_nome'],
                'chave_prefixo' => $rel['chave_prefixo'],
            ],
        ]);
    }

    if (!dashboard_relatorio_validar_chave($rel_auth, $chave)) {
        auditoria_registrar($conn, 'dashboard_relatorio', 'chave_invalida', (int) $rel['id']);
        dashboard_publico_responder(['status' => 'error', 'message' => 'Chave de acesso inválida.'], 403);
    }

    dashboard_publico_marcar_autenticado($rel_auth);
    dashboard_relatorio_registrar_acesso_externo($conn, (int) $rel['id']);
    $autenticado = true;
}

if ($apenas_status) {
    dashboard_publico_responder([
        'status' => 'success',
        'message' => 'Autenticado.',
        'data' => [
            'nome' => $rel['nome'],
            'evento_nome' => $rel['evento_nome'],
            'chave_prefixo' => $rel['chave_prefixo'],
            'autenticado' => true,
        ],
    ]);
}

if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

try {
    $dados = dashboard_renderizar_relatorio($conn, (int) $rel['evento_id'], $rel['widgets']);
} catch (Throwable $e) {
    error_log('dashboard_publico_dados render: ' . $e->getMessage());
    dashboard_publico_responder([
        'status' => 'error',
        'message' => 'Erro ao gerar o relatório. Tente novamente.',
    ], 500);
}

$json = json_encode([
    'status' => 'success',
    'message' => 'OK',
    'data' => [
        'relatorio' => array_merge([
            'nome' => $rel['nome'],
            'evento_nome' => $rel['evento_nome'],
            'template_nome' => $rel['template_nome'],
        ], dashboard_evento_meta_resumo($rel)),
        'dashboard' => $dados,
    ],
], JSON_UNESCAPED_UNICODE);

if ($json === false) {
    error_log('dashboard_publico_dados json_encode: ' . json_last_error_msg());
    dashboard_publico_responder([
        'status' => 'error',
        'message' => 'Erro ao serializar o relatório.',
    ], 500);
}

http_response_code(200);
echo $json;
exit;

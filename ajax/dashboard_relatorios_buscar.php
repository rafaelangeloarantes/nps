<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../modules/auth/middleware.php';
require_once __DIR__ . '/../modules/auth/permissoes.php';
require_once __DIR__ . '/../modules/dashboard/relatorios.php';

$id = (int) ($_GET['id'] ?? 0);
$row = dashboard_relatorio_buscar($conn, $id);
if (!$row) {
    json_response('error', 'Relatório não encontrado.');
}

exigir_acesso_evento($conn, (int) $row['evento_id']);

json_response('success', 'OK', [
    'id' => (int) $row['id'],
    'nome' => $row['nome'],
    'template_id' => (int) $row['template_id'],
    'template_nome' => $row['template_nome'],
    'evento_id' => (int) $row['evento_id'],
    'evento_nome' => $row['evento_nome'],
    'token' => $row['token'],
    'url_publica' => $row['url_publica'],
    'chave_acesso' => $row['chave_acesso'] ?? '',
    'chave_prefixo' => $row['chave_prefixo'],
    'widgets' => $row['widgets'],
]);

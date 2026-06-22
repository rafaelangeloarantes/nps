<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../modules/auth/middleware.php';
require_once __DIR__ . '/../modules/auth/permissoes.php';
require_once __DIR__ . '/../modules/dashboard/relatorios.php';
require_once __DIR__ . '/../modules/dashboard/render.php';

$id = (int) ($_GET['id'] ?? 0);
$rel = dashboard_relatorio_buscar($conn, $id);
if (!$rel) {
    json_response('error', 'Relatório não encontrado.');
}

exigir_acesso_evento($conn, (int) $rel['evento_id']);

$dados = dashboard_renderizar_relatorio($conn, (int) $rel['evento_id'], $rel['widgets']);

json_response('success', 'OK', [
    'relatorio' => [
        'id' => (int) $rel['id'],
        'nome' => $rel['nome'],
        'evento_nome' => $rel['evento_nome'],
        'template_nome' => $rel['template_nome'],
    ],
    'dashboard' => $dados,
]);

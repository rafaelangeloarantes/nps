<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../modules/auth/middleware.php';
require_once __DIR__ . '/../modules/auth/permissoes.php';
require_once __DIR__ . '/../modules/dashboard/relatorios.php';
require_once __DIR__ . '/../modules/dashboard/extrato.php';
require_once __DIR__ . '/../includes/xlsx_simple.php';

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$rel = dashboard_relatorio_buscar($conn, $id);
if (!$rel) {
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Relatório não encontrado.';
    exit;
}

exigir_acesso_evento($conn, (int) $rel['evento_id']);

$extrato = dashboard_extrato_montar_linhas($conn, (int) $rel['evento_id']);
dashboard_extrato_registrar_log($conn, $id, (int) $rel['evento_id'], $extrato['total']);

$nome_arquivo = 'extrato_' . preg_replace('/[^a-z0-9]+/i', '_', $rel['nome']) . '_' . date('Y-m-d_His');
xlsx_download($extrato['cabecalho'], $extrato['linhas'], $nome_arquivo);

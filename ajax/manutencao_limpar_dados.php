<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../modules/auth/middleware.php';
require_once __DIR__ . '/../modules/auth/permissoes.php';
require_once __DIR__ . '/../modules/manutencao/limpeza.php';

exigir_admin_master();

$acao = $_GET['acao'] ?? $_POST['acao'] ?? 'resumo';

if ($acao === 'resumo') {
    json_response('success', 'OK', [
        'opcoes' => manutencao_resumo_opcoes_limpeza($conn),
        'sugeridas' => manutencao_opcoes_sugeridas(),
    ]);
}

if ($acao === 'executar' || $acao === 'limpar') {
    $confirmacao = trim($_POST['confirmacao'] ?? '');
    if ($confirmacao !== 'LIMPAR') {
        json_response('error', 'Digite LIMPAR para confirmar a exclusão.');
    }

    $opcoes = $_POST['opcoes'] ?? [];
    if (!is_array($opcoes)) {
        $opcoes = [];
    }

    // Compatibilidade com a ação antiga (limpar tudo).
    if ($acao === 'limpar' && empty($opcoes)) {
        $opcoes = ['tudo_operacional'];
    }

    $opcoes = array_values(array_unique(array_filter(array_map('strval', $opcoes))));
    $resultado = manutencao_executar_limpeza($conn, $opcoes, true);
    json_response($resultado['status'], $resultado['message'], $resultado['data'] ?? null);
}

json_response('error', 'Ação inválida.');

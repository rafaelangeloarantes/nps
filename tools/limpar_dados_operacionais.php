<?php
/**
 * CLI — Limpeza global de dados operacionais
 *
 * Uso:
 *   php tools/limpar_dados_operacionais.php           (apenas resumo)
 *   php tools/limpar_dados_operacionais.php --confirm (executa limpeza)
 */
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../modules/manutencao/limpar_dados.php';

$confirmar = in_array('--confirm', $argv ?? [], true);

echo "=== Limpeza de dados operacionais NPS ===\n\n";

$resumo = manutencao_resumo_dados_operacionais($conn);
echo "Situação atual:\n";
foreach ($resumo as $chave => $qtd) {
    echo sprintf("  %-22s %d\n", $chave . ':', $qtd);
}
echo "\nMantidos: contratos, usuários, campos padrão NPS, templates, integrações.\n";

if (!$confirmar) {
    echo "\nPara executar a limpeza, rode:\n";
    echo "  php tools/limpar_dados_operacionais.php --confirm\n";
    exit(0);
}

echo "\nExecutando limpeza...\n";
$resultado = manutencao_limpar_dados_operacionais($conn, false);

if ($resultado['status'] === 'success') {
    echo "OK: " . $resultado['message'] . "\n";
    exit(0);
}

echo "ERRO: " . $resultado['message'] . "\n";
exit(1);

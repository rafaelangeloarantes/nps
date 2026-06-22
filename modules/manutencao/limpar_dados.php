<?php
/**
 * Limpeza global de dados operacionais (eventos, participantes, credenciamento, pesquisas).
 * Mantém: contratos, usuários, campos padrão NPS, templates de dashboard, integrações.
 */

require_once __DIR__ . '/../dashboard/log.php';

function manutencao_tabelas_operacionais()
{
    return [
        'relatorio_pesquisa_respostas',
        'relatorio_pesquisa_campos',
        'pesquisa_respostas',
        'pesquisa_campos',
        'logs_backup_respostas',
        'dashboard_relatorios',
        'credenciamentos',
        'participante_evento_dados',
        'participante_plotagem',
        'evento_atributo_mapeamento',
        'participante_eventos',
        'pesquisas',
        'participantes',
        'eventos',
        'sincronizacao_log',
    ];
}

/**
 * Contagens antes da limpeza.
 */
function manutencao_resumo_dados_operacionais($conn)
{
    $tabelas = [
        'eventos' => 'SELECT COUNT(*) AS t FROM eventos',
        'participantes' => 'SELECT COUNT(*) AS t FROM participantes',
        'participante_eventos' => 'SELECT COUNT(*) AS t FROM participante_eventos',
        'credenciamentos' => 'SELECT COUNT(*) AS t FROM credenciamentos',
        'pesquisas' => 'SELECT COUNT(*) AS t FROM pesquisas',
        'pesquisa_respostas' => 'SELECT COUNT(*) AS t FROM relatorio_pesquisa_respostas',
        'pesquisa_campos' => 'SELECT COUNT(*) AS t FROM relatorio_pesquisa_campos',
        'dashboard_relatorios' => 'SELECT COUNT(*) AS t FROM dashboard_relatorios',
        'mapeamentos_evento' => 'SELECT COUNT(*) AS t FROM evento_atributo_mapeamento',
        'dados_extras' => 'SELECT COUNT(*) AS t FROM participante_evento_dados',
    ];

    $resumo = [];
    foreach ($tabelas as $chave => $sql) {
        $result = mysqli_query($conn, $sql);
        if (!$result) {
            $resumo[$chave] = 0;
            continue;
        }
        $row = mysqli_fetch_assoc($result);
        $resumo[$chave] = (int) ($row['t'] ?? 0);
    }

    return $resumo;
}

/**
 * Remove todos os dados operacionais do sistema.
 */
function manutencao_limpar_dados_operacionais($conn, $registrar_auditoria = true)
{
    $resumo_antes = manutencao_resumo_dados_operacionais($conn);
    $total = array_sum($resumo_antes);

    if ($total === 0) {
        return [
            'status' => 'success',
            'message' => 'Não há dados operacionais para remover.',
            'data' => ['antes' => $resumo_antes, 'depois' => $resumo_antes],
        ];
    }

    mysqli_query($conn, 'SET FOREIGN_KEY_CHECKS = 0');
    mysqli_begin_transaction($conn);

    try {
        foreach (manutencao_tabelas_operacionais() as $tabela) {
            if (!mysqli_query($conn, "DELETE FROM `{$tabela}`")) {
                throw new RuntimeException('Erro ao limpar ' . $tabela . ': ' . mysqli_error($conn));
            }
        }

        // Reinicia auto_increment para IDs começarem do 1
        foreach (manutencao_tabelas_operacionais() as $tabela) {
            mysqli_query($conn, "ALTER TABLE `{$tabela}` AUTO_INCREMENT = 1");
        }

        mysqli_commit($conn);
        mysqli_query($conn, 'SET FOREIGN_KEY_CHECKS = 1');

        $resumo_depois = manutencao_resumo_dados_operacionais($conn);

        if ($registrar_auditoria) {
            auditoria_registrar($conn, 'manutencao', 'limpar_dados_operacionais', null, [
                'antes' => $resumo_antes,
                'depois' => $resumo_depois,
            ]);
        }

        return [
            'status' => 'success',
            'message' => sprintf(
                'Limpeza concluída: %d evento(s), %d participante(s), %d credenciamento(s), %d pesquisa(s) removidos.',
                $resumo_antes['eventos'],
                $resumo_antes['participantes'],
                $resumo_antes['credenciamentos'],
                $resumo_antes['pesquisas']
            ),
            'data' => [
                'antes' => $resumo_antes,
                'depois' => $resumo_depois,
            ],
        ];
    } catch (Throwable $e) {
        mysqli_rollback($conn);
        mysqli_query($conn, 'SET FOREIGN_KEY_CHECKS = 1');
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}

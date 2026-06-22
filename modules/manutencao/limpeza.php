<?php
/**
 * Limpeza seletiva de dados obsoletos, órfãos ou de eventos inativos.
 */

require_once __DIR__ . '/limpar_dados.php';
require_once __DIR__ . '/../eventos/limpar_dados.php';
require_once __DIR__ . '/../dashboard/log.php';

/**
 * Opções disponíveis na tela de Configurações.
 */
function manutencao_opcoes_limpeza()
{
    return [
        'dados_eventos_inativos' => [
            'grupo' => 'Eventos',
            'label' => 'Dados de eventos inativos',
            'descricao' => 'Remove vínculos, credenciamentos, pesquisas e dados extras vinculados a eventos inativos. O cadastro do evento é mantido.',
            'ordem' => 10,
        ],
        'eventos_inativos' => [
            'grupo' => 'Eventos',
            'label' => 'Eventos inativos (exclusão completa)',
            'descricao' => 'Remove o evento inativo e tudo vinculado a ele: pesquisas, respostas, credenciamentos, relatórios, mapeamentos, plotagem e vínculos. Não é necessário marcar outras opções.',
            'ordem' => 20,
            'completo' => true,
        ],
        'participantes_orfaos' => [
            'grupo' => 'Participantes',
            'label' => 'Participantes órfãos',
            'descricao' => 'Inativa participantes ativos sem vínculo com nenhum evento.',
            'ordem' => 30,
        ],
        'participantes_inativos' => [
            'grupo' => 'Participantes',
            'label' => 'Participantes inativos',
            'descricao' => 'Remove permanentemente participantes já inativos que não possuem vínculos com eventos.',
            'ordem' => 40,
        ],
        'vinculos_orfaos' => [
            'grupo' => 'Participantes',
            'label' => 'Vínculos órfãos',
            'descricao' => 'Remove vínculos participante↔evento cujo cadastro de origem não existe mais.',
            'ordem' => 50,
        ],
        'credenciamentos_inativos' => [
            'grupo' => 'Credenciamento',
            'label' => 'Credenciamentos inativos',
            'descricao' => 'Remove registros de credenciamento já inativados.',
            'ordem' => 60,
        ],
        'pesquisas_inativas' => [
            'grupo' => 'Pesquisas',
            'label' => 'Pesquisas inativas',
            'descricao' => 'Remove pesquisas inativas, campos mapeados e respostas vinculadas.',
            'ordem' => 70,
        ],
        'relatorios_inativos' => [
            'grupo' => 'Relatórios',
            'label' => 'Relatórios inativos',
            'descricao' => 'Remove relatórios de dashboard já inativados.',
            'ordem' => 80,
        ],
        'relatorios_eventos_inativos' => [
            'grupo' => 'Relatórios',
            'label' => 'Relatórios de eventos inativos',
            'descricao' => 'Remove relatórios de dashboard vinculados a eventos inativos.',
            'ordem' => 90,
        ],
        'mapeamentos_eventos_inativos' => [
            'grupo' => 'Eventos',
            'label' => 'Mapeamentos de eventos inativos',
            'descricao' => 'Remove mapeamentos de atributos da API de eventos inativos.',
            'ordem' => 100,
        ],
        'plotagem_eventos_inativos' => [
            'grupo' => 'Eventos',
            'label' => 'Plotagem de eventos inativos',
            'descricao' => 'Remove configurações de gráficos de eventos inativos.',
            'ordem' => 110,
        ],
        'logs_sincronizacao' => [
            'grupo' => 'Sistema',
            'label' => 'Logs de sincronização',
            'descricao' => 'Limpa o histórico de sincronizações com APIs externas.',
            'ordem' => 120,
        ],
        'tudo_operacional' => [
            'grupo' => 'Sistema',
            'label' => 'Todos os dados operacionais',
            'descricao' => 'Remove eventos, participantes, credenciamentos, pesquisas e relatórios. Mantém contratos, usuários, templates e integrações.',
            'ordem' => 999,
            'nuclear' => true,
        ],
    ];
}

/**
 * Ordem segura de execução quando várias opções são selecionadas.
 */
function manutencao_ordem_execucao_limpeza()
{
    return [
        'vinculos_orfaos',
        'dados_eventos_inativos',
        'credenciamentos_inativos',
        'pesquisas_inativas',
        'relatorios_inativos',
        'relatorios_eventos_inativos',
        'mapeamentos_eventos_inativos',
        'plotagem_eventos_inativos',
        'participantes_orfaos',
        'participantes_inativos',
        'eventos_inativos',
        'logs_sincronizacao',
        'tudo_operacional',
    ];
}

/**
 * Opções sugeridas para registros obsoletos do dia a dia.
 */
function manutencao_opcoes_sugeridas()
{
    return [
        'eventos_inativos',
        'participantes_orfaos',
        'vinculos_orfaos',
        'credenciamentos_inativos',
        'logs_sincronizacao',
    ];
}

/**
 * Opções absorvidas automaticamente por "Eventos inativos (exclusão completa)".
 */
function manutencao_opcoes_absorvidas_por_eventos()
{
    return [
        'dados_eventos_inativos',
        'pesquisas_inativas',
        'relatorios_eventos_inativos',
        'mapeamentos_eventos_inativos',
        'plotagem_eventos_inativos',
    ];
}

function manutencao_normalizar_opcoes_limpeza(array $opcoes)
{
    $opcoes_validas = array_keys(manutencao_opcoes_limpeza());
    $selecionadas = array_values(array_unique(array_filter($opcoes, function ($item) use ($opcoes_validas) {
        return in_array($item, $opcoes_validas, true);
    })));

    if (in_array('eventos_inativos', $selecionadas, true)) {
        $selecionadas = array_values(array_diff($selecionadas, manutencao_opcoes_absorvidas_por_eventos()));
    }

    return $selecionadas;
}

function manutencao_resumo_opcoes_limpeza($conn)
{
    $opcoes = manutencao_opcoes_limpeza();
    $resumo = [];

    foreach ($opcoes as $chave => $meta) {
        $resumo[$chave] = [
            'label' => $meta['label'],
            'grupo' => $meta['grupo'],
            'descricao' => $meta['descricao'],
            'ordem' => $meta['ordem'],
            'nuclear' => !empty($meta['nuclear']),
            'total' => manutencao_contar_opcao_limpeza($conn, $chave),
        ];
    }

    return $resumo;
}

function manutencao_contar_opcao_limpeza($conn, $opcao)
{
    switch ($opcao) {
        case 'dados_eventos_inativos':
            return manutencao_contar_eventos_inativos_com_dados($conn);

        case 'eventos_inativos':
            return manutencao_scalar($conn, 'SELECT COUNT(*) AS t FROM eventos WHERE ativo = 0');

        case 'participantes_orfaos':
            return manutencao_scalar($conn,
                'SELECT COUNT(*) AS t FROM participantes p
                 WHERE p.ativo = 1
                 AND NOT EXISTS (
                     SELECT 1 FROM participante_eventos pe WHERE pe.participante_id = p.id
                 )'
            );

        case 'participantes_inativos':
            return manutencao_scalar($conn,
                'SELECT COUNT(*) AS t FROM participantes p
                 WHERE p.ativo = 0
                 AND NOT EXISTS (
                     SELECT 1 FROM participante_eventos pe WHERE pe.participante_id = p.id
                 )'
            );

        case 'vinculos_orfaos':
            return manutencao_scalar($conn,
                'SELECT COUNT(*) AS t
                 FROM participante_eventos pe
                 LEFT JOIN eventos e ON e.id = pe.evento_id
                 LEFT JOIN participantes p ON p.id = pe.participante_id
                 WHERE e.id IS NULL OR p.id IS NULL'
            );

        case 'credenciamentos_inativos':
            return manutencao_scalar($conn, 'SELECT COUNT(*) AS t FROM credenciamentos WHERE ativo = 0');

        case 'pesquisas_inativas':
            return manutencao_scalar($conn, 'SELECT COUNT(*) AS t FROM pesquisas WHERE ativo = 0');

        case 'relatorios_inativos':
            return manutencao_scalar($conn, 'SELECT COUNT(*) AS t FROM dashboard_relatorios WHERE ativo = 0');

        case 'relatorios_eventos_inativos':
            return manutencao_scalar($conn,
                'SELECT COUNT(*) AS t
                 FROM dashboard_relatorios dr
                 INNER JOIN eventos e ON e.id = dr.evento_id AND e.ativo = 0
                 WHERE dr.ativo = 1'
            );

        case 'mapeamentos_eventos_inativos':
            return manutencao_scalar($conn,
                'SELECT COUNT(*) AS t
                 FROM evento_atributo_mapeamento m
                 INNER JOIN eventos e ON e.id = m.evento_id AND e.ativo = 0'
            );

        case 'plotagem_eventos_inativos':
            return manutencao_scalar($conn,
                'SELECT COUNT(*) AS t
                 FROM participante_plotagem pp
                 INNER JOIN eventos e ON e.id = pp.evento_id AND e.ativo = 0'
            );

        case 'logs_sincronizacao':
            return manutencao_scalar($conn, 'SELECT COUNT(*) AS t FROM sincronizacao_log');

        case 'tudo_operacional':
            $geral = manutencao_resumo_dados_operacionais($conn);
            return (int) array_sum($geral);

        default:
            return 0;
    }
}

function manutencao_contar_eventos_inativos_com_dados($conn)
{
    return manutencao_scalar($conn,
        'SELECT COUNT(DISTINCT e.id) AS t
         FROM eventos e
         WHERE e.ativo = 0
         AND (
             EXISTS (SELECT 1 FROM participante_eventos pe WHERE pe.evento_id = e.id)
             OR EXISTS (SELECT 1 FROM credenciamentos c WHERE c.evento_id = e.id AND c.ativo = 1)
             OR EXISTS (SELECT 1 FROM pesquisas p WHERE p.evento_id = e.id AND p.ativo = 1)
             OR EXISTS (SELECT 1 FROM participante_evento_dados ped WHERE ped.evento_id = e.id)
         )'
    );
}

function manutencao_executar_limpeza($conn, array $opcoes, $registrar_auditoria = true)
{
    $selecionadas = manutencao_normalizar_opcoes_limpeza($opcoes);

    if (empty($selecionadas)) {
        return ['status' => 'error', 'message' => 'Selecione ao menos uma opção de limpeza.'];
    }

    if (in_array('tudo_operacional', $selecionadas, true)) {
        return manutencao_limpar_dados_operacionais($conn, $registrar_auditoria);
    }

    $ordem = manutencao_ordem_execucao_limpeza();
    usort($selecionadas, function ($a, $b) use ($ordem) {
        return array_search($a, $ordem, true) <=> array_search($b, $ordem, true);
    });

    $resumo_antes = [];
    foreach ($selecionadas as $opcao) {
        $resumo_antes[$opcao] = manutencao_contar_opcao_limpeza($conn, $opcao);
    }

    if (array_sum($resumo_antes) === 0) {
        return [
            'status' => 'success',
            'message' => 'Não há registros para remover nas opções selecionadas.',
            'data' => ['antes' => $resumo_antes, 'depois' => $resumo_antes, 'opcoes' => $selecionadas],
        ];
    }

    mysqli_begin_transaction($conn);

    try {
        $resultados = [];

        foreach ($selecionadas as $opcao) {
            $resultados[$opcao] = manutencao_executar_opcao_limpeza($conn, $opcao);
        }

        mysqli_commit($conn);

        $resumo_depois = [];
        foreach ($selecionadas as $opcao) {
            $resumo_depois[$opcao] = manutencao_contar_opcao_limpeza($conn, $opcao);
        }

        if ($registrar_auditoria) {
            auditoria_registrar($conn, 'manutencao', 'limpeza_seletiva', null, [
                'opcoes' => $selecionadas,
                'antes' => $resumo_antes,
                'depois' => $resumo_depois,
                'resultados' => $resultados,
            ]);
        }

        return [
            'status' => 'success',
            'message' => manutencao_montar_mensagem_limpeza($selecionadas, $resumo_antes, $resumo_depois),
            'data' => [
                'opcoes' => $selecionadas,
                'antes' => $resumo_antes,
                'depois' => $resumo_depois,
                'resultados' => $resultados,
            ],
        ];
    } catch (Throwable $e) {
        mysqli_rollback($conn);
        return ['status' => 'error', 'message' => 'Erro na limpeza: ' . $e->getMessage()];
    }
}

function manutencao_executar_opcao_limpeza($conn, $opcao)
{
    switch ($opcao) {
        case 'dados_eventos_inativos':
            return manutencao_limpar_dados_eventos_inativos($conn);

        case 'eventos_inativos':
            return evento_excluir_inativos_completo($conn);

        case 'participantes_orfaos':
            return ['inativados' => manutencao_inativar_participantes_orfaos($conn)];

        case 'participantes_inativos':
            return ['removidos' => manutencao_remover_participantes_inativos($conn)];

        case 'vinculos_orfaos':
            return ['removidos' => manutencao_delete_query($conn,
                'DELETE pe FROM participante_eventos pe
                 LEFT JOIN eventos e ON e.id = pe.evento_id
                 LEFT JOIN participantes p ON p.id = pe.participante_id
                 WHERE e.id IS NULL OR p.id IS NULL'
            )];

        case 'credenciamentos_inativos':
            return ['removidos' => manutencao_delete_query($conn, 'DELETE FROM credenciamentos WHERE ativo = 0')];

        case 'pesquisas_inativas':
            return evento_remover_pesquisas_inativas($conn);

        case 'relatorios_inativos':
            return ['removidos' => manutencao_delete_query($conn, 'DELETE FROM dashboard_relatorios WHERE ativo = 0')];

        case 'relatorios_eventos_inativos':
            return ['removidos' => manutencao_delete_query($conn,
                'DELETE dr FROM dashboard_relatorios dr
                 INNER JOIN eventos e ON e.id = dr.evento_id AND e.ativo = 0
                 WHERE dr.ativo = 1'
            )];

        case 'mapeamentos_eventos_inativos':
            return ['removidos' => manutencao_delete_query($conn,
                'DELETE m FROM evento_atributo_mapeamento m
                 INNER JOIN eventos e ON e.id = m.evento_id AND e.ativo = 0'
            )];

        case 'plotagem_eventos_inativos':
            return ['removidos' => manutencao_delete_query($conn,
                'DELETE pp FROM participante_plotagem pp
                 INNER JOIN eventos e ON e.id = pp.evento_id AND e.ativo = 0'
            )];

        case 'logs_sincronizacao':
            return ['removidos' => manutencao_delete_query($conn, 'DELETE FROM sincronizacao_log')];

        default:
            throw new RuntimeException('Opção de limpeza inválida: ' . $opcao);
    }
}

function manutencao_limpar_dados_eventos_inativos($conn)
{
    $ids = [];
    $result = mysqli_query($conn, 'SELECT id FROM eventos WHERE ativo = 0 ORDER BY id ASC');
    while ($row = mysqli_fetch_assoc($result)) {
        $ids[] = (int) $row['id'];
    }

    $eventos = 0;
    $vinculos = 0;
    $credenciamentos = 0;
    $pesquisas = 0;
    $participantes_inativados = 0;

    foreach ($ids as $evento_id) {
        $resumo = evento_resumo_dados_vinculados($conn, $evento_id);
        if (($resumo['data']['total_registros'] ?? 0) === 0) {
            continue;
        }

        $resultado = evento_limpar_dados_vinculados($conn, $evento_id, true, false);
        if ($resultado['status'] !== 'success') {
            throw new RuntimeException($resultado['message'] ?? 'Falha ao limpar evento ' . $evento_id);
        }

        $dados = $resultado['data'] ?? [];
        $eventos++;
        $vinculos += (int) ($dados['participantes_vinculados'] ?? 0);
        $credenciamentos += (int) ($dados['credenciamentos'] ?? 0);
        $pesquisas += (int) ($dados['pesquisas'] ?? 0);
        $participantes_inativados += (int) ($dados['participantes_inativados'] ?? 0);
    }

    return [
        'eventos' => $eventos,
        'vinculos' => $vinculos,
        'credenciamentos' => $credenciamentos,
        'pesquisas' => $pesquisas,
        'participantes_inativados' => $participantes_inativados,
    ];
}

function manutencao_inativar_participantes_orfaos($conn)
{
    $ids = [];
    $result = mysqli_query($conn,
        'SELECT p.id
         FROM participantes p
         WHERE p.ativo = 1
         AND NOT EXISTS (
             SELECT 1 FROM participante_eventos pe WHERE pe.participante_id = p.id
         )'
    );

    while ($row = mysqli_fetch_assoc($result)) {
        $ids[] = (int) $row['id'];
    }

    if (empty($ids)) {
        return 0;
    }

    return evento_soft_delete_participantes($conn, $ids);
}

function manutencao_remover_participantes_inativos($conn)
{
    return manutencao_delete_query($conn,
        'DELETE p FROM participantes p
         WHERE p.ativo = 0
         AND NOT EXISTS (
             SELECT 1 FROM participante_eventos pe WHERE pe.participante_id = p.id
         )'
    );
}

function manutencao_montar_mensagem_limpeza(array $opcoes, array $antes, array $depois)
{
    $partes = [];
    $labels = manutencao_opcoes_limpeza();

    foreach ($opcoes as $opcao) {
        $removidos = max(0, (int) ($antes[$opcao] ?? 0) - (int) ($depois[$opcao] ?? 0));
        if ($removidos === 0 && ($antes[$opcao] ?? 0) > 0 && in_array($opcao, ['dados_eventos_inativos', 'eventos_inativos'], true)) {
            $removidos = (int) ($antes[$opcao] ?? 0);
        }
        if ($removidos > 0 || ($antes[$opcao] ?? 0) > 0) {
            $label = $labels[$opcao]['label'] ?? $opcao;
            $partes[] = $label . ': ' . max($removidos, (int) ($antes[$opcao] ?? 0) - (int) ($depois[$opcao] ?? 0));
        }
    }

    if (empty($partes)) {
        return 'Limpeza concluída. Nenhum registro removido nas opções selecionadas.';
    }

    return 'Limpeza concluída — ' . implode('; ', $partes) . '.';
}

function manutencao_scalar($conn, $sql)
{
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        return 0;
    }
    $row = mysqli_fetch_assoc($result);
    return (int) ($row['t'] ?? 0);
}

function manutencao_delete_query($conn, $sql)
{
    if (!mysqli_query($conn, $sql)) {
        throw new RuntimeException(mysqli_error($conn));
    }
    return mysqli_affected_rows($conn);
}

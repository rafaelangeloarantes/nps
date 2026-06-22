<?php
/**
 * Limpeza de dados vinculados a um evento (participantes, credenciamento, pesquisas).
 */

function evento_obter_basico($conn, $evento_id)
{
    $evento_id = (int) $evento_id;
    $stmt = mysqli_prepare($conn, 'SELECT id, nome FROM eventos WHERE id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $evento_id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    return $row ?: null;
}

/**
 * Contagens para confirmação antes da exclusão.
 */
function evento_resumo_dados_vinculados($conn, $evento_id)
{
    $evento_id = (int) $evento_id;
    $evento = evento_obter_basico($conn, $evento_id);
    if (!$evento) {
        return ['status' => 'error', 'message' => 'Evento não encontrado.'];
    }

    $participantes_vinculados = evento_contar_query(
        $conn,
        'SELECT COUNT(*) AS total FROM participante_eventos WHERE evento_id = ?',
        $evento_id
    );

    $participantes_orfaos = evento_contar_query(
        $conn,
        'SELECT COUNT(*) AS total
         FROM participante_eventos pe
         INNER JOIN participantes p ON p.id = pe.participante_id AND p.ativo = 1
         WHERE pe.evento_id = ?
         AND (SELECT COUNT(*) FROM participante_eventos pe2 WHERE pe2.participante_id = pe.participante_id) = 1',
        $evento_id
    );

    $credenciamentos = evento_contar_query(
        $conn,
        'SELECT COUNT(*) AS total FROM credenciamentos WHERE evento_id = ? AND ativo = 1',
        $evento_id
    );

    $pesquisas = evento_contar_query(
        $conn,
        'SELECT COUNT(*) AS total FROM pesquisas WHERE evento_id = ? AND ativo = 1',
        $evento_id
    );

    $dados_extras = evento_contar_query(
        $conn,
        'SELECT COUNT(*) AS total FROM participante_evento_dados WHERE evento_id = ?',
        $evento_id
    );

    return [
        'status' => 'success',
        'data' => [
            'evento_id' => $evento_id,
            'evento_nome' => $evento['nome'],
            'participantes_vinculados' => $participantes_vinculados,
            'participantes_orfaos' => $participantes_orfaos,
            'participantes_mantidos' => max(0, $participantes_vinculados - $participantes_orfaos),
            'credenciamentos' => $credenciamentos,
            'pesquisas' => $pesquisas,
            'dados_extras' => $dados_extras,
            'total_registros' => $participantes_vinculados + $credenciamentos + $pesquisas + $dados_extras,
        ],
    ];
}

function evento_contar_query($conn, $sql, $evento_id)
{
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $evento_id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    return (int) ($row['total'] ?? 0);
}

/**
 * Remove vínculos e registros do evento. Participantes globais permanecem se vinculados a outros eventos.
 */
function evento_limpar_dados_vinculados($conn, $evento_id, $remover_orfaos = true, $usar_transacao = true)
{
    $evento_id = (int) $evento_id;
    $evento = evento_obter_basico($conn, $evento_id);
    if (!$evento) {
        return ['status' => 'error', 'message' => 'Evento não encontrado.'];
    }

    $resumo_antes = evento_resumo_dados_vinculados($conn, $evento_id);
    if ($resumo_antes['status'] !== 'success') {
        return $resumo_antes;
    }

    $totais = $resumo_antes['data'];
    if ($totais['total_registros'] === 0) {
        return ['status' => 'success', 'message' => 'Não há dados vinculados a este evento para remover.', 'data' => $totais];
    }

    $ids_orfaos = [];
    if ($remover_orfaos) {
        $ids_orfaos = evento_listar_participantes_orfaos($conn, $evento_id);
    }

    if ($usar_transacao) {
        mysqli_begin_transaction($conn);
    }

    try {
        evento_executar_stmt(
            $conn,
            'UPDATE credenciamentos SET ativo = 0 WHERE evento_id = ? AND ativo = 1',
            $evento_id
        );

        evento_executar_stmt(
            $conn,
            'UPDATE pesquisas SET ativo = 0 WHERE evento_id = ? AND ativo = 1',
            $evento_id
        );

        evento_executar_stmt(
            $conn,
            'DELETE FROM participante_evento_dados WHERE evento_id = ?',
            $evento_id
        );

        evento_executar_stmt(
            $conn,
            'DELETE FROM participante_eventos WHERE evento_id = ?',
            $evento_id
        );

        $participantes_removidos = 0;
        if ($remover_orfaos && !empty($ids_orfaos)) {
            $participantes_removidos = evento_soft_delete_participantes($conn, $ids_orfaos);
        }

        evento_executar_stmt(
            $conn,
            'UPDATE eventos SET ultima_sincronizacao = NULL WHERE id = ?',
            $evento_id
        );

        if ($usar_transacao) {
            mysqli_commit($conn);
        }

        $msg = sprintf(
            'Dados do evento removidos: %d vínculo(s) de participante, %d credenciamento(s), %d pesquisa(s).',
            $totais['participantes_vinculados'],
            $totais['credenciamentos'],
            $totais['pesquisas']
        );

        if ($remover_orfaos && $participantes_removidos > 0) {
            $msg .= sprintf(' %d participante(s) exclusivo(s) do evento foram inativados.', $participantes_removidos);
        } elseif ($totais['participantes_mantidos'] > 0) {
            $msg .= sprintf(
                ' %d participante(s) permanecem no cadastro (vinculados a outros eventos).',
                $totais['participantes_mantidos']
            );
        }

        return [
            'status' => 'success',
            'message' => $msg,
            'data' => array_merge($totais, [
                'participantes_inativados' => $participantes_removidos,
            ]),
        ];
    } catch (Throwable $e) {
        if ($usar_transacao) {
            mysqli_rollback($conn);
        }
        return ['status' => 'error', 'message' => 'Erro ao limpar dados: ' . $e->getMessage()];
    }
}

function evento_listar_participantes_orfaos($conn, $evento_id)
{
    $evento_id = (int) $evento_id;
    $stmt = mysqli_prepare(
        $conn,
        'SELECT pe.participante_id
         FROM participante_eventos pe
         INNER JOIN participantes p ON p.id = pe.participante_id AND p.ativo = 1
         WHERE pe.evento_id = ?
         AND (SELECT COUNT(*) FROM participante_eventos pe2 WHERE pe2.participante_id = pe.participante_id) = 1'
    );
    mysqli_stmt_bind_param($stmt, 'i', $evento_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $ids = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $ids[] = (int) $row['participante_id'];
    }
    mysqli_stmt_close($stmt);
    return $ids;
}

function evento_soft_delete_participantes($conn, array $ids)
{
    $ids = array_values(array_filter(array_map('intval', $ids)));
    if (empty($ids)) {
        return 0;
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));
    $sql = "UPDATE participantes SET ativo = 0 WHERE id IN ({$placeholders}) AND ativo = 1";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$ids);
    mysqli_stmt_execute($stmt);
    $afetados = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);

    return $afetados;
}

function evento_executar_stmt($conn, $sql, $evento_id)
{
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        throw new RuntimeException(mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmt, 'i', $evento_id);
    if (!mysqli_stmt_execute($stmt)) {
        $erro = mysqli_error($conn);
        mysqli_stmt_close($stmt);
        throw new RuntimeException($erro);
    }
    mysqli_stmt_close($stmt);
}

/**
 * Remove dependências físicas de eventos inativos antes de excluir os cadastros.
 * Cobre tabelas atuais e legadas que ainda possam existir no banco.
 */
function evento_remover_dependencias_inativos($conn)
{
    return evento_remover_dependencias_por_condicao($conn, 'e.ativo = 0');
}

/**
 * Remove todas as dependências de eventos que atendem à condição informada.
 *
 * @param string $condicao_evento Ex.: "e.ativo = 0" ou "e.id = 5"
 */
function evento_remover_dependencias_por_condicao($conn, $condicao_evento)
{
    $condicao_evento = trim($condicao_evento);
    if ($condicao_evento === '') {
        throw new RuntimeException('Condição de evento inválida para limpeza.');
    }

    $join_evento = "INNER JOIN eventos e ON e.id = p.evento_id AND {$condicao_evento}";
    $contagens = [];

    $contagens['pesquisas_filhas'] = evento_remover_filhas_pesquisas_por_evento($conn, $condicao_evento);
    $contagens['pesquisas'] = evento_delete_join(
        $conn,
        "DELETE p FROM pesquisas p INNER JOIN eventos e ON e.id = p.evento_id AND {$condicao_evento}"
    );

    $contagens['relatorios_dashboard'] = evento_delete_join(
        $conn,
        "DELETE dr FROM dashboard_relatorios dr
         INNER JOIN eventos e ON e.id = dr.evento_id AND {$condicao_evento}"
    );

    $contagens['credenciamentos'] = evento_delete_join(
        $conn,
        "DELETE c FROM credenciamentos c
         INNER JOIN eventos e ON e.id = c.evento_id AND {$condicao_evento}"
    );

    $contagens['dados_extras'] = evento_delete_join(
        $conn,
        "DELETE ped FROM participante_evento_dados ped
         INNER JOIN eventos e ON e.id = ped.evento_id AND {$condicao_evento}"
    );

    $contagens['vinculos'] = evento_delete_join(
        $conn,
        "DELETE pe FROM participante_eventos pe
         INNER JOIN eventos e ON e.id = pe.evento_id AND {$condicao_evento}"
    );

    $contagens['plotagem'] = evento_delete_join(
        $conn,
        "DELETE pp FROM participante_plotagem pp
         INNER JOIN eventos e ON e.id = pp.evento_id AND {$condicao_evento}"
    );

    $contagens['mapeamentos'] = evento_delete_join(
        $conn,
        "DELETE m FROM evento_atributo_mapeamento m
         INNER JOIN eventos e ON e.id = m.evento_id AND {$condicao_evento}"
    );

    if (evento_tabela_existe($conn, 'sincronizacao_log')) {
        $contagens['logs_sincronizacao'] = evento_delete_join(
            $conn,
            "DELETE sl FROM sincronizacao_log sl
             INNER JOIN eventos e ON e.id = sl.entidade_id AND sl.entidade = 'evento' AND {$condicao_evento}"
        );
    }

    return $contagens;
}

/**
 * Exclui eventos inativos e tudo que depende deles (operação única e completa).
 */
function evento_excluir_inativos_completo($conn)
{
    $ids = [];
    $result = mysqli_query($conn, 'SELECT id FROM eventos WHERE ativo = 0 ORDER BY id ASC');
    while ($row = mysqli_fetch_assoc($result)) {
        $ids[] = (int) $row['id'];
    }

    if (empty($ids)) {
        return [
            'eventos' => 0,
            'dependencias' => [],
            'participantes_inativados' => 0,
        ];
    }

    $dependencias = evento_remover_dependencias_inativos($conn);
    $participantes_inativados = evento_inativar_participantes_orfaos_apos_limpeza($conn);

    $removidos = evento_delete_join($conn, 'DELETE FROM eventos WHERE ativo = 0');

    return [
        'eventos' => $removidos,
        'dependencias' => $dependencias,
        'participantes_inativados' => $participantes_inativados,
        'ids' => $ids,
    ];
}

function evento_inativar_participantes_orfaos_apos_limpeza($conn)
{
    $ids = [];
    $result = mysqli_query(
        $conn,
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

    return evento_soft_delete_participantes($conn, $ids);
}

function evento_tabela_existe($conn, $tabela)
{
    static $cache = [];
    $tabela = trim((string) $tabela);
    if ($tabela === '') {
        return false;
    }
    if (array_key_exists($tabela, $cache)) {
        return $cache[$tabela];
    }

    $safe = mysqli_real_escape_string($conn, $tabela);
    $result = mysqli_query($conn, "SHOW TABLES LIKE '{$safe}'");
    $cache[$tabela] = $result && mysqli_num_rows($result) > 0;

    return $cache[$tabela];
}

/**
 * Remove registros filhos de pesquisas vinculadas a eventos que atendem à condição.
 */
function evento_remover_filhas_pesquisas_por_evento($conn, $condicao_evento)
{
    $total = 0;

    foreach (evento_tabelas_filhas_pesquisa($conn) as $tabela) {
        $total += evento_delete_join(
            $conn,
            "DELETE filho FROM `{$tabela}` filho
             INNER JOIN pesquisas p ON p.id = filho.pesquisa_id
             INNER JOIN eventos e ON e.id = p.evento_id AND {$condicao_evento}"
        );
    }

    return $total;
}

/**
 * Remove registros filhos de pesquisas inativas.
 */
function evento_remover_filhas_pesquisas_inativas($conn)
{
    $total = 0;

    foreach (evento_tabelas_filhas_pesquisa($conn) as $tabela) {
        $total += evento_delete_join(
            $conn,
            "DELETE filho FROM `{$tabela}` filho
             INNER JOIN pesquisas p ON p.id = filho.pesquisa_id AND p.ativo = 0"
        );
    }

    return $total;
}

function evento_tabelas_filhas_pesquisa($conn)
{
    static $cache = [];
    $key = spl_object_hash($conn);
    if (isset($cache[$key])) {
        return $cache[$key];
    }

    $candidatas = [
        'logs_backup_respostas',
        'relatorio_pesquisa_respostas',
        'relatorio_pesquisa_campos',
        'pesquisa_respostas',
        'pesquisa_campos',
    ];
    $cache[$key] = [];
    foreach ($candidatas as $tabela) {
        if (evento_tabela_existe($conn, $tabela)) {
            $cache[$key][] = $tabela;
        }
    }

    return $cache[$key];
}

function evento_delete_join($conn, $sql)
{
    if (!mysqli_query($conn, $sql)) {
        throw new RuntimeException(mysqli_error($conn));
    }
    return mysqli_affected_rows($conn);
}

/**
 * Exclui fisicamente pesquisas inativas e todos os registros filhos (inclui tabelas legadas).
 */
function evento_remover_pesquisas_inativas($conn)
{
    $filhas = evento_remover_filhas_pesquisas_inativas($conn);
    $pesquisas = evento_delete_join($conn, 'DELETE FROM pesquisas WHERE ativo = 0');

    return [
        'filhas' => $filhas,
        'pesquisas' => $pesquisas,
    ];
}

<?php
/**
 * Sincronização de pesquisas — mesma API Guests dos participantes
 */

require_once __DIR__ . '/inteegra_client.php';
require_once __DIR__ . '/pesquisas_parser.php';
require_once __DIR__ . '/../pesquisas/functions.php';
require_once __DIR__ . '/../pesquisas/campos.php';
require_once __DIR__ . '/../pesquisas/respostas.php';
require_once __DIR__ . '/participantes_sync.php';

/**
 * EventId da consulta Guests — vem da pesquisa, não do evento vinculado
 */
function pesquisa_event_id_api(array $pesquisa)
{
    $id = trim($pesquisa['identificador_integracao'] ?? '');
    if ($id === '') {
        return ['ok' => false, 'error' => 'Configure o identificador de integração na pesquisa.'];
    }
    return ['ok' => true, 'event_id_api' => $id];
}

function pesquisa_descobrir_campos_api($conn, $pesquisa_id)
{
    $pesquisa = pesquisa_buscar_por_id($conn, $pesquisa_id);
    if (!$pesquisa) {
        return ['status' => 'error', 'message' => 'Pesquisa não encontrada.'];
    }

    $event_id = pesquisa_event_id_api($pesquisa);
    if (!$event_id['ok']) {
        return ['status' => 'error', 'message' => $event_id['error']];
    }

    $auth = inteegra_autenticar_evento($conn, (int) $pesquisa['evento_id']);
    if (!$auth['ok']) {
        return ['status' => 'error', 'message' => $auth['error']];
    }

    $page = inteegra_buscar_guests_pagina(
        $auth['token'],
        $event_id['event_id_api'],
        1,
        50,
        $auth['guests_base'] ?? null
    );

    if (!$page['ok']) {
        return ['status' => 'error', 'message' => $page['error']];
    }

    $descobertos = pesquisa_parser_descobrir_campos($page['guests']);
    if (empty($descobertos)) {
        return ['status' => 'error', 'message' => 'Nenhum campo encontrado na amostra de guests da API.'];
    }

    $salvos = pesquisa_campo_listar($conn, $pesquisa_id);
    $mesclado = pesquisa_campo_mesclar_descoberta($conn, $descobertos, $salvos);

    return [
        'status' => 'success',
        'message' => count($descobertos) . ' campos encontrados na API (Guests).',
        'data' => [
            'campos' => $mesclado,
            'total_respostas_amostra' => count($page['guests']),
            'pagination' => $page['pagination'],
        ],
    ];
}

function pesquisas_sincronizar_por_id($conn, $pesquisa_id)
{
    $pesquisa_id = (int) $pesquisa_id;
    $pesquisa = pesquisa_buscar_por_id($conn, $pesquisa_id);
    if (!$pesquisa) {
        return ['status' => 'error', 'message' => 'Pesquisa não encontrada.'];
    }

    if (!pesquisa_campo_tem_mapeamento($conn, $pesquisa_id)) {
        return [
            'status' => 'error',
            'message' => 'Configure o mapeamento de campos antes de sincronizar.',
        ];
    }

    $event_id = pesquisa_event_id_api($pesquisa);
    if (!$event_id['ok']) {
        return ['status' => 'error', 'message' => $event_id['error']];
    }

    $mapeamentos = pesquisa_campo_listar($conn, $pesquisa_id);
    $auth = inteegra_autenticar_evento($conn, (int) $pesquisa['evento_id']);
    if (!$auth['ok']) {
        return ['status' => 'error', 'message' => $auth['error']];
    }

    $fetch = inteegra_buscar_todos_guests(
        $auth['token'],
        $event_id['event_id_api'],
        50,
        $auth['guests_base'] ?? null
    );

    if (!$fetch['ok']) {
        sincronizacao_registrar_log($conn, 'pesquisa', $pesquisa_id, 'api', 'erro', $fetch['error']);
        return ['status' => 'error', 'message' => $fetch['error']];
    }

    mysqli_query($conn, 'DELETE FROM relatorio_pesquisa_respostas WHERE pesquisa_id = ' . $pesquisa_id);

    $evento_id = (int) $pesquisa['evento_id'];
    $stats = [
        'total_api' => count($fetch['guests']),
        'importadas' => 0,
        'vinculados' => 0,
        'novos_participantes' => 0,
        'sem_email' => 0,
        'erros' => 0,
    ];

    foreach ($fetch['guests'] as $guest) {
        if (!is_array($guest)) {
            $stats['erros']++;
            continue;
        }

        try {
            $parsed = pesquisa_parser_aplicar_mapeamento($guest, $mapeamentos);
            $email_raw = $parsed['email'];
            $email = $email_raw !== '' ? $email_raw : 'sem-email-' . md5(json_encode($parsed['bruto']));
            $nome_guest = trim((string) ($guest['Name'] ?? ''));

            $participante_id = null;
            if ($email_raw !== '') {
                $vinculo = pesquisa_resposta_resolver_participante(
                    $conn,
                    $evento_id,
                    $email_raw,
                    $nome_guest,
                    (int) ($parsed['guest_id'] ?? 0)
                );
                if ($vinculo['ok'] && !empty($vinculo['participante_id'])) {
                    $participante_id = (int) $vinculo['participante_id'];
                    $stats['vinculados']++;
                    if (!empty($vinculo['criado'])) {
                        $stats['novos_participantes']++;
                    }
                } else {
                    $stats['sem_email']++;
                }
            } else {
                $stats['sem_email']++;
            }

            $dados_json = json_encode([
                'guest_id' => $parsed['guest_id'],
                'campos' => $parsed['dados'],
                'bruto' => $parsed['bruto'],
                'sincronizado_em' => date('c'),
            ], JSON_UNESCAPED_UNICODE);

            $stmt = mysqli_prepare(
                $conn,
                'INSERT INTO relatorio_pesquisa_respostas (pesquisa_id, evento_id, email_participante, participante_id, dados_json)
                 VALUES (?, ?, ?, ?, ?)'
            );

            if (!$stmt) {
                $stats['erros']++;
                continue;
            }

            $pid = $participante_id > 0 ? $participante_id : null;
            mysqli_stmt_bind_param($stmt, 'iisis', $pesquisa_id, $evento_id, $email, $pid, $dados_json);
            if (mysqli_stmt_execute($stmt)) {
                $stats['importadas']++;
            } else {
                $stats['erros']++;
            }
            mysqli_stmt_close($stmt);
        } catch (Throwable $e) {
            $stats['erros']++;
            error_log('Sync pesquisa erro: ' . $e->getMessage());
        }
    }

    pesquisa_resposta_reconciliar_vinculos($conn, $pesquisa_id);

    $stmt = mysqli_prepare($conn, 'UPDATE pesquisas SET ultima_sincronizacao = NOW() WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $pesquisa_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $msg = sprintf(
        'Sincronização concluída: %d guests — %d respostas, %d vinculados a participantes (%d novos), %d sem e-mail.',
        $stats['total_api'],
        $stats['importadas'],
        $stats['vinculados'],
        $stats['novos_participantes'],
        $stats['sem_email']
    );

    sincronizacao_registrar_log(
        $conn,
        'pesquisa',
        $pesquisa_id,
        'api',
        $stats['erros'] > 0 ? 'parcial' : 'sucesso',
        $msg,
        $stats['importadas']
    );

    return [
        'status' => 'success',
        'message' => $msg,
        'data' => $stats,
    ];
}

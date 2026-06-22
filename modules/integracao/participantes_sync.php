<?php
/**
 * Sincronização de participantes via API Inteegra Guests
 */

require_once __DIR__ . '/inteegra_client.php';
require_once __DIR__ . '/inteegra_parser.php';
require_once __DIR__ . '/../participantes/functions.php';
require_once __DIR__ . '/../eventos/atributos.php';
require_once __DIR__ . '/../eventos/functions.php';
require_once __DIR__ . '/../credenciamentos/functions.php';
require_once __DIR__ . '/../log/functions.php';

/**
 * Sincroniza todos os guests de um evento
 */
function participantes_sincronizar_por_evento($conn, $evento_id)
{
    $evento_id = (int) $evento_id;
    $mapeamentos = evento_atributo_listar($conn, $evento_id);

    if (empty($mapeamentos)) {
        log_erro(
            $conn,
            'evento',
            'sync_sem_mapeamento',
            'Configure o mapeamento de atributos antes de sincronizar.',
            $evento_id
        );
        return [
            'status' => 'error',
            'message' => 'Configure o mapeamento de atributos antes de sincronizar.',
        ];
    }

    $auth = inteegra_autenticar_evento($conn, $evento_id);
    if (!$auth['ok']) {
        log_erro($conn, 'evento', 'integracao_auth', $auth['error'], $evento_id);
        return ['status' => 'error', 'message' => $auth['error']];
    }

    $fetch = inteegra_buscar_todos_guests($auth['token'], $auth['event_id_api'], 50, $auth['guests_base'] ?? null);
    if (!$fetch['ok']) {
        sincronizacao_registrar_log($conn, 'evento', $evento_id, 'api', 'erro', $fetch['error']);
        return ['status' => 'error', 'message' => $fetch['error']];
    }

    $guests = $fetch['guests'];
    $stats = [
        'total_api' => count($guests),
        'inseridos' => 0,
        'atualizados' => 0,
        'vinculados' => 0,
        'incompletos' => 0,
        'credenciamentos_show' => 0,
        'credenciamentos_noshow' => 0,
        'credenciamentos_sem_status' => 0,
        'emails_fake' => 0,
        'emails_sequenciais' => 0,
        'erros' => 0,
    ];

    $uso_email_batch = [];
    $emails_reservados = [];

    foreach ($guests as $guest) {
        if (!is_array($guest)) {
            $stats['erros']++;
            continue;
        }

        try {
            $parsed = inteegra_parse_guest_participante($guest, $mapeamentos);
            $res = participante_upsert_de_api($conn, $evento_id, $parsed, $uso_email_batch, $emails_reservados);
            if ($res['acao'] === 'insert') {
                $stats['inseridos']++;
            } else {
                $stats['atualizados']++;
            }
            $stats['vinculados']++;
            if (!empty($res['incompleto'])) {
                $stats['incompletos']++;
            }
            if (($res['tipo_ajuste_email'] ?? '') === 'fake') {
                $stats['emails_fake']++;
            } elseif (($res['tipo_ajuste_email'] ?? '') === 'sequencial') {
                $stats['emails_sequenciais']++;
            }

            $cred_status = $parsed['credenciamento_status'] ?? null;
            if ($cred_status === 'SHOW') {
                $stats['credenciamentos_show']++;
            } elseif ($cred_status === 'NOSHOW') {
                $stats['credenciamentos_noshow']++;
            } else {
                $stats['credenciamentos_sem_status']++;
            }
        } catch (Throwable $e) {
            $stats['erros']++;
            error_log('Sync guest erro: ' . $e->getMessage());
        }
    }

    evento_marcar_sincronizacao($conn, $evento_id);

    $msg = sprintf(
        'Sincronização concluída: %d da API — %d novos, %d atualizados, %d incompletos (%d e-mail fake, %d e-mail sequencial). Credenciamento: %d SHOW, %d NOSHOW.',
        $stats['total_api'],
        $stats['inseridos'],
        $stats['atualizados'],
        $stats['incompletos'],
        $stats['emails_fake'],
        $stats['emails_sequenciais'],
        $stats['credenciamentos_show'],
        $stats['credenciamentos_noshow']
    );

    sincronizacao_registrar_log(
        $conn,
        'evento',
        $evento_id,
        'api',
        $stats['erros'] > 0 ? 'parcial' : 'sucesso',
        $msg,
        $stats['total_api']
    );

    return [
        'status' => 'success',
        'message' => $msg,
        'data' => $stats,
    ];
}

/**
 * Insere ou atualiza participante, vincula guest_id e credenciamento SHOW/NOSHOW
 */
function participante_upsert_de_api($conn, $evento_id, array $parsed, array &$uso_email_batch, array &$emails_reservados)
{
    $p = $parsed['participante'];
    $nome = trim($p['nome_completo'] ?? '');
    $guest_id = (int) ($parsed['guest_id'] ?? 0);
    $email_api = trim(strtolower($parsed['email_original_api'] ?? $p['email'] ?? ''));

    // Chave legado: guest_id no evento (não depende do e-mail)
    $participante_id = 0;
    if ($guest_id > 0) {
        $participante_id = participante_buscar_id_por_guest_evento($conn, $evento_id, $guest_id);
    }

    $email_res = participante_resolver_email_importacao(
        $conn,
        $email_api,
        $nome,
        $evento_id,
        $guest_id,
        $participante_id,
        $uso_email_batch,
        $emails_reservados
    );

    $email = $email_res['email'];
    $dado_incompleto = (int) $email_res['dado_incompleto'];
    $motivo_incompleto = $email_res['motivo'];
    $acao = 'update';

    if ($participante_id <= 0) {
        $participante_id = participante_buscar_id_por_email($conn, $email, false);
    }

    if ($participante_id <= 0) {
        $acao = 'insert';
        $stmt = mysqli_prepare(
            $conn,
            'INSERT INTO participantes (nome_completo, email, telefone, cargo, empresa, estado, cidade,
             data_nascimento, linkedin, dado_incompleto, motivo_incompleto, ultima_sincronizacao)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
        );
        mysqli_stmt_bind_param(
            $stmt,
            'sssssssssis',
            $nome,
            $email,
            $p['telefone'],
            $p['cargo'],
            $p['empresa'],
            $p['estado'],
            $p['cidade'],
            $p['data_nascimento'],
            $p['linkedin'],
            $dado_incompleto,
            $motivo_incompleto
        );
        mysqli_stmt_execute($stmt);
        $participante_id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);
    } else {
        $stmt = mysqli_prepare(
            $conn,
            'UPDATE participantes SET nome_completo=?, email=?, telefone=?, cargo=?, empresa=?, estado=?, cidade=?,
             data_nascimento=?, linkedin=?, dado_incompleto=?, motivo_incompleto=?, ultima_sincronizacao=NOW(), ativo=1
             WHERE id=?'
        );
        mysqli_stmt_bind_param(
            $stmt,
            'sssssssssisi',
            $nome,
            $email,
            $p['telefone'],
            $p['cargo'],
            $p['empresa'],
            $p['estado'],
            $p['cidade'],
            $p['data_nascimento'],
            $p['linkedin'],
            $dado_incompleto,
            $motivo_incompleto,
            $participante_id
        );
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    $parsed['email_resolvido'] = $email;
    $parsed['email_tipo_ajuste'] = $email_res['tipo_ajuste'];

    participante_vincular_evento_api($conn, $participante_id, $evento_id, $guest_id, $parsed);

    $cred_status = $parsed['credenciamento_status'] ?? null;
    if ($cred_status !== null) {
        credenciamento_sincronizar_de_api($conn, $evento_id, $participante_id, $cred_status);
    } else {
        credenciamento_limpar_sync_api($conn, $evento_id, $participante_id);
    }

    return [
        'acao' => $acao,
        'participante_id' => $participante_id,
        'guest_id' => $guest_id,
        'incompleto' => $dado_incompleto === 1,
        'tipo_ajuste_email' => $email_res['tipo_ajuste'],
        'email' => $email,
    ];
}

function participante_vincular_evento_api($conn, $participante_id, $evento_id, $guest_id, array $parsed)
{
    $dados_json = json_encode([
        'guest_id' => $guest_id,
        'email_original_api' => $parsed['email_original_api'] ?? '',
        'email_resolvido' => $parsed['email_resolvido'] ?? '',
        'email_tipo_ajuste' => $parsed['email_tipo_ajuste'] ?? null,
        'atributos' => $parsed['extras'] ?? [],
        'atributos_brutos' => $parsed['atributos_brutos'] ?? [],
        'confirmation_status' => $parsed['confirmation_status'] ?? '',
        'credenciamento_status' => $parsed['credenciamento_status'] ?? null,
        'sincronizado_em' => date('c'),
    ], JSON_UNESCAPED_UNICODE);

    $confirmation_status = $parsed['confirmation_status'] ?? '';
    $stmt = mysqli_prepare(
        $conn,
        'INSERT INTO participante_eventos (participante_id, evento_id, guest_id_api, confirmation_status_api)
         VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE guest_id_api = VALUES(guest_id_api),
             confirmation_status_api = VALUES(confirmation_status_api)'
    );
    $guest_null = $guest_id > 0 ? $guest_id : null;
    mysqli_stmt_bind_param($stmt, 'iiis', $participante_id, $evento_id, $guest_null, $confirmation_status);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare(
        $conn,
        'INSERT INTO participante_evento_dados (participante_id, evento_id, dados_json)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE dados_json = VALUES(dados_json)'
    );
    mysqli_stmt_bind_param($stmt, 'iis', $participante_id, $evento_id, $dados_json);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

function sincronizacao_registrar_log($conn, $entidade, $entidade_id, $tipo, $status, $mensagem, $registros = 0)
{
    log_sincronizacao($conn, $entidade, $entidade_id, $tipo, $status, $mensagem, $registros);
}

/**
 * Descobre atributos na API para o evento
 */
function evento_descobrir_atributos_api($conn, $evento_id)
{
    $auth = inteegra_autenticar_evento($conn, $evento_id);
    if (!$auth['ok']) {
        return ['status' => 'error', 'message' => $auth['error']];
    }

    $page = inteegra_buscar_guests_pagina($auth['token'], $auth['event_id_api'], 1, 50, $auth['guests_base'] ?? null);
    if (!$page['ok']) {
        return ['status' => 'error', 'message' => $page['error']];
    }

    $descobertos = inteegra_descobrir_atributos($page['guests']);
    $salvos = evento_atributo_listar($conn, $evento_id);
    $mesclado = evento_atributo_mesclar_descoberta($conn, $descobertos, $salvos);

    $total_api = (int) ($page['pagination']['TotalCount'] ?? count($page['guests']));
    $amostra = count($page['guests']);

    if ($total_api <= 0 && $amostra === 0) {
        $evento_api = inteegra_buscar_evento_api(
            $auth['token'],
            $auth['event_id_api'],
            $auth['guests_base'] ?? null
        );

        return [
            'status' => 'warning',
            'message' => inteegra_mensagem_sem_guests(
                $auth['event_id_api'],
                $auth['login'] ?? '',
                $evento_api
            ),
            'data' => [
                'atributos' => $mesclado,
                'total_guests_amostra' => 0,
                'total_guests_api' => 0,
                'event_id_api' => $auth['event_id_api'],
                'evento_api_encontrado' => is_array($evento_api),
                'evento_api_nome' => is_array($evento_api) ? ($evento_api['Name'] ?? '') : '',
                'credencial_origem' => $auth['credencial_origem'] ?? 'integracao',
                'pagination' => $page['pagination'],
            ],
        ];
    }

    return [
        'status' => 'success',
        'message' => count($descobertos) . ' atributos encontrados na API (' . $total_api . ' participantes).',
        'data' => [
            'atributos' => $mesclado,
            'total_guests_amostra' => $amostra,
            'total_guests_api' => $total_api,
            'event_id_api' => $auth['event_id_api'],
            'pagination' => $page['pagination'],
        ],
    ];
}

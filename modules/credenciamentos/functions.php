<?php
/**
 * Funções do módulo Credenciamentos — SHOW/NOSHOW por evento + participante
 */

require_once __DIR__ . '/../log/functions.php';

function credenciamento_status_validos()
{
    return ['SHOW', 'NOSHOW'];
}

function credenciamento_badge_html($status)
{
    return $status === 'SHOW'
        ? '<span class="badge badge-active">SHOW</span>'
        : '<span class="badge badge-pending">NOSHOW</span>';
}

/**
 * Status exibido na listagem de participantes por evento.
 * Pendente = não SHOW e sem confirmação CN na API.
 */
function credenciamento_resolver_status_exibicao($cred_status, $confirmation_status_api)
{
    $cred = strtoupper(trim((string) $cred_status));
    if ($cred === 'SHOW') {
        return 'SHOW';
    }
    if ($cred === 'NOSHOW') {
        return 'NOSHOW';
    }

    $conf = strtoupper(trim((string) $confirmation_status_api));
    if ($conf !== '' && strpos($conf, 'CN') === 0) {
        return 'CONVIDADO';
    }

    return 'PENDENTE';
}

function credenciamento_status_badge_html($status)
{
    switch (strtoupper(trim((string) $status))) {
        case 'SHOW':
            return '<span class="badge badge-active">SHOW</span>';
        case 'NOSHOW':
            return '<span class="badge badge-error">NOSHOW</span>';
        case 'CONVIDADO':
            return '<span class="badge badge-ready">CONVIDADO</span>';
        case 'PENDENTE':
        default:
            return '<span class="badge badge-pending">Pendente</span>';
    }
}

function credenciamento_buscar_por_id($conn, $id)
{
    $stmt = mysqli_prepare(
        $conn,
        'SELECT cr.*, e.nome AS evento_nome, p.nome_completo, p.email
         FROM credenciamentos cr
         INNER JOIN eventos e ON e.id = cr.evento_id
         INNER JOIN participantes p ON p.id = cr.participante_id
         WHERE cr.id = ? AND cr.ativo = 1 LIMIT 1'
    );
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    return $row ?: null;
}

function credenciamento_buscar_por_evento_participante($conn, $evento_id, $participante_id)
{
    $evento_id = (int) $evento_id;
    $participante_id = (int) $participante_id;
    if ($evento_id <= 0 || $participante_id <= 0) {
        return null;
    }

    $stmt = mysqli_prepare(
        $conn,
        'SELECT * FROM credenciamentos
         WHERE evento_id = ? AND participante_id = ? AND ativo = 1 LIMIT 1'
    );
    mysqli_stmt_bind_param($stmt, 'ii', $evento_id, $participante_id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    return $row ?: null;
}

function credenciamento_salvar($conn, $dados)
{
    $id = (int) ($dados['id'] ?? 0);
    $evento_id = (int) ($dados['evento_id'] ?? 0);
    $participante_id = (int) ($dados['participante_id'] ?? 0);
    $status = strtoupper(trim($dados['status'] ?? 'NOSHOW'));

    if (!in_array($status, ['SHOW', 'NOSHOW'], true)) {
        $status = 'NOSHOW';
    }

    if ($evento_id <= 0 || $participante_id <= 0) {
        return ['status' => 'error', 'message' => 'Evento e participante são obrigatórios.'];
    }

    if ($id > 0) {
        $stmt = mysqli_prepare(
            $conn,
            'UPDATE credenciamentos SET evento_id=?, participante_id=?, status=?, ultima_sincronizacao=NULL WHERE id=?'
        );
        mysqli_stmt_bind_param($stmt, 'iisi', $evento_id, $participante_id, $status, $id);
        $msg = 'Credenciamento atualizado com sucesso.';
    } else {
        $stmt = mysqli_prepare(
            $conn,
            'INSERT INTO credenciamentos (evento_id, participante_id, status, ultima_sincronizacao)
             VALUES (?, ?, ?, NULL)
             ON DUPLICATE KEY UPDATE status = VALUES(status), ultima_sincronizacao = NULL, ativo = 1'
        );
        mysqli_stmt_bind_param($stmt, 'iis', $evento_id, $participante_id, $status);
        $msg = 'Credenciamento registrado com sucesso.';
    }

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        $novo_id = $id > 0 ? $id : mysqli_insert_id($conn);
        log_acao($conn, 'credenciamentos', $id > 0 ? 'editar' : 'criar', $novo_id, ['status' => $status]);
        return ['status' => 'success', 'message' => $msg, 'id' => $novo_id];
    }

    $erro = mysqli_error($conn);
    mysqli_stmt_close($stmt);
    return ['status' => 'error', 'message' => 'Erro ao salvar: ' . $erro];
}

function credenciamento_excluir($conn, $id)
{
    $stmt = mysqli_prepare($conn, 'UPDATE credenciamentos SET ativo = 0 WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if ($ok) {
        log_acao($conn, 'credenciamentos', 'excluir', $id);
    }

    return $ok
        ? ['status' => 'success', 'message' => 'Credenciamento excluído com sucesso.']
        : ['status' => 'error', 'message' => 'Erro ao excluir credenciamento.'];
}

/**
 * Grava ou atualiza SHOW/NOSHOW vindo da API (AttendDate / ConfirmationStatus)
 */
function credenciamento_sincronizar_de_api($conn, $evento_id, $participante_id, $status)
{
    $evento_id = (int) $evento_id;
    $participante_id = (int) $participante_id;
    $status = strtoupper(trim((string) $status));

    if ($evento_id <= 0 || $participante_id <= 0) {
        return ['ok' => false, 'skipped' => true, 'motivo' => 'evento_ou_participante_invalido'];
    }

    if (!in_array($status, credenciamento_status_validos(), true)) {
        return ['ok' => false, 'skipped' => true, 'motivo' => 'status_ausente_na_api'];
    }

    $stmt = mysqli_prepare(
        $conn,
        'INSERT INTO credenciamentos (evento_id, participante_id, status, ultima_sincronizacao)
         VALUES (?, ?, ?, NOW())
         ON DUPLICATE KEY UPDATE status = VALUES(status), ultima_sincronizacao = NOW(), ativo = 1'
    );
    if (!$stmt) {
        return ['ok' => false, 'error' => mysqli_error($conn)];
    }

    mysqli_stmt_bind_param($stmt, 'iis', $evento_id, $participante_id, $status);
    $ok = mysqli_stmt_execute($stmt);
    $erro = $ok ? '' : mysqli_error($conn);
    $id = $ok ? (int) mysqli_insert_id($conn) : 0;
    mysqli_stmt_close($stmt);

    if (!$ok) {
        return ['ok' => false, 'error' => $erro];
    }

    if ($id === 0) {
        $row = credenciamento_buscar_por_evento_participante($conn, $evento_id, $participante_id);
        $id = (int) ($row['id'] ?? 0);
    }

    return [
        'ok' => true,
        'id' => $id,
        'status' => $status,
        'acao' => $id > 0 ? 'upsert' : 'ok',
    ];
}

/**
 * Remove credenciamento quando o guest ainda não tem status (ex.: pendente NE)
 */
function credenciamento_limpar_sync_api($conn, $evento_id, $participante_id)
{
    $evento_id = (int) $evento_id;
    $participante_id = (int) $participante_id;
    if ($evento_id <= 0 || $participante_id <= 0) {
        return ['ok' => false, 'skipped' => true];
    }

    $stmt = mysqli_prepare(
        $conn,
        'UPDATE credenciamentos SET ativo = 0 WHERE evento_id = ? AND participante_id = ? AND ativo = 1'
    );
    if (!$stmt) {
        return ['ok' => false, 'error' => mysqli_error($conn)];
    }

    mysqli_stmt_bind_param($stmt, 'ii', $evento_id, $participante_id);
    $ok = mysqli_stmt_execute($stmt);
    $afetados = $ok ? mysqli_stmt_affected_rows($stmt) : 0;
    mysqli_stmt_close($stmt);

    return ['ok' => $ok, 'removidos' => $afetados];
}

function credenciamento_calcular_adesao($conn, $evento_id)
{
    $evento_id = (int) $evento_id;
    $sql = "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'SHOW' THEN 1 ELSE 0 END) AS show_total,
                SUM(CASE WHEN status = 'NOSHOW' THEN 1 ELSE 0 END) AS noshow_total
            FROM credenciamentos
            WHERE evento_id = {$evento_id} AND ativo = 1";
    $row = mysqli_fetch_assoc(mysqli_query($conn, $sql));
    $total = (int) ($row['total'] ?? 0);
    $show = (int) ($row['show_total'] ?? 0);
    $percentual = $total > 0 ? round(($show / $total) * 100, 1) : 0;

    return [
        'total' => $total,
        'show' => $show,
        'noshow' => (int) ($row['noshow_total'] ?? 0),
        'adesao_percentual' => $percentual,
    ];
}

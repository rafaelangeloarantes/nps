<?php
/**
 * Módulo de Log do Sistema — ações de usuários, integrações e erros
 */

function log_garantir_estrutura($conn)
{
    static $ok = false;
    if ($ok) {
        return;
    }

    mysqli_query(
        $conn,
        "CREATE TABLE IF NOT EXISTS `sistema_auditoria` (
            `id` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `tipo` ENUM('acao','integracao','erro') NOT NULL DEFAULT 'acao',
            `nivel` ENUM('info','aviso','erro') NOT NULL DEFAULT 'info',
            `modulo` VARCHAR(50) NOT NULL,
            `acao` VARCHAR(50) NOT NULL,
            `mensagem` TEXT NULL,
            `entidade_id` INT NULL,
            `usuario_id` INT NULL,
            `ip` VARCHAR(45) NULL,
            `user_agent` VARCHAR(500) NULL,
            `detalhes_json` JSON NULL,
            `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY `idx_sa_modulo` (`modulo`, `entidade_id`),
            KEY `idx_sa_usuario` (`usuario_id`),
            KEY `idx_sa_criado` (`criado_em`),
            KEY `idx_sa_tipo` (`tipo`),
            KEY `idx_sa_nivel` (`nivel`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $colunas = [];
    $res = mysqli_query($conn, "SHOW COLUMNS FROM sistema_auditoria LIKE 'tipo'");
    if ($res && mysqli_num_rows($res) === 0) {
        mysqli_query($conn, "ALTER TABLE sistema_auditoria ADD COLUMN tipo ENUM('acao','integracao','erro') NOT NULL DEFAULT 'acao' AFTER id");
    }
    $res = mysqli_query($conn, "SHOW COLUMNS FROM sistema_auditoria LIKE 'nivel'");
    if ($res && mysqli_num_rows($res) === 0) {
        mysqli_query($conn, "ALTER TABLE sistema_auditoria ADD COLUMN nivel ENUM('info','aviso','erro') NOT NULL DEFAULT 'info' AFTER tipo");
    }
    $res = mysqli_query($conn, "SHOW COLUMNS FROM sistema_auditoria LIKE 'mensagem'");
    if ($res && mysqli_num_rows($res) === 0) {
        mysqli_query($conn, 'ALTER TABLE sistema_auditoria ADD COLUMN mensagem TEXT NULL AFTER acao');
    }

    $ok = true;
}

function log_obter_ip()
{
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
    if (strpos($ip, ',') !== false) {
        $ip = trim(explode(',', $ip)[0]);
    }
    return mb_substr((string) $ip, 0, 45, 'UTF-8');
}

function log_obter_user_agent()
{
    return mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500, 'UTF-8');
}

/**
 * Registro base de log no sistema.
 */
function log_registrar($conn, array $opts)
{
    log_garantir_estrutura($conn);

    $tipo = in_array($opts['tipo'] ?? '', ['acao', 'integracao', 'erro'], true)
        ? $opts['tipo']
        : 'acao';
    $nivel = in_array($opts['nivel'] ?? '', ['info', 'aviso', 'erro'], true)
        ? $opts['nivel']
        : 'info';
    $modulo = mb_substr(trim((string) ($opts['modulo'] ?? 'sistema')), 0, 50, 'UTF-8');
    $acao = mb_substr(trim((string) ($opts['acao'] ?? 'registro')), 0, 50, 'UTF-8');
    $mensagem = isset($opts['mensagem']) && $opts['mensagem'] !== ''
        ? mb_substr(trim((string) $opts['mensagem']), 0, 5000, 'UTF-8')
        : null;
    $entidade_id = isset($opts['entidade_id']) && $opts['entidade_id'] !== null && $opts['entidade_id'] !== ''
        ? (int) $opts['entidade_id']
        : 0;
    $usuario_id = isset($opts['usuario_id']) && $opts['usuario_id'] !== null && $opts['usuario_id'] !== ''
        ? (int) $opts['usuario_id']
        : (!empty($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0);
    $ip = $opts['ip'] ?? log_obter_ip();
    $user_agent = $opts['user_agent'] ?? log_obter_user_agent();
    $detalhes = $opts['detalhes'] ?? [];
    $json = !empty($detalhes) ? json_encode($detalhes, JSON_UNESCAPED_UNICODE) : null;

    $stmt = mysqli_prepare(
        $conn,
        'INSERT INTO sistema_auditoria (tipo, nivel, modulo, acao, mensagem, entidade_id, usuario_id, ip, user_agent, detalhes_json)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    if (!$stmt) {
        error_log('log_registrar: ' . mysqli_error($conn));
        return false;
    }

    mysqli_stmt_bind_param(
        $stmt,
        'sssssiisss',
        $tipo,
        $nivel,
        $modulo,
        $acao,
        $mensagem,
        $entidade_id,
        $usuario_id,
        $ip,
        $user_agent,
        $json
    );
    $ok = mysqli_stmt_execute($stmt);
    if (!$ok) {
        error_log('log_registrar execute: ' . mysqli_error($conn));
    }
    mysqli_stmt_close($stmt);

    return $ok;
}

/** Ação realizada por usuário autenticado. */
function log_acao($conn, $modulo, $acao, $entidade_id = null, array $detalhes = [])
{
    return log_registrar($conn, [
        'tipo' => 'acao',
        'nivel' => 'info',
        'modulo' => $modulo,
        'acao' => $acao,
        'entidade_id' => $entidade_id,
        'detalhes' => $detalhes,
    ]);
}

/** Evento de integração (API, arquivo, sincronização). */
function log_integracao($conn, $modulo, $acao, $entidade_id, $status, $mensagem, array $detalhes = [])
{
    $status = (string) $status;
    $nivel = 'info';
    if ($status === 'erro') {
        $nivel = 'erro';
    } elseif ($status === 'parcial') {
        $nivel = 'aviso';
    }

    return log_registrar($conn, [
        'tipo' => 'integracao',
        'nivel' => $nivel,
        'modulo' => $modulo,
        'acao' => $acao,
        'entidade_id' => $entidade_id,
        'mensagem' => $mensagem,
        'detalhes' => array_merge(['status' => $status], $detalhes),
    ]);
}

/** Erro de sistema ou integração. */
function log_erro($conn, $modulo, $acao, $mensagem, $entidade_id = null, array $detalhes = [])
{
    return log_registrar($conn, [
        'tipo' => 'erro',
        'nivel' => 'erro',
        'modulo' => $modulo,
        'acao' => $acao,
        'entidade_id' => $entidade_id,
        'mensagem' => $mensagem,
        'detalhes' => $detalhes,
    ]);
}

/** Labels amigáveis para exibição. */
function log_tipo_label($tipo)
{
    $labels = [
        'acao' => 'Ação',
        'integracao' => 'Integração',
        'erro' => 'Erro',
    ];
    return $labels[$tipo] ?? ucfirst((string) $tipo);
}

function log_nivel_label($nivel)
{
    $labels = [
        'info' => 'Info',
        'aviso' => 'Aviso',
        'erro' => 'Erro',
    ];
    return $labels[$nivel] ?? ucfirst((string) $nivel);
}

function log_badge_tipo($tipo)
{
    $classes = [
        'acao' => 'badge-active',
        'integracao' => 'badge-pending',
        'erro' => 'badge-inactive',
    ];
    $classe = $classes[$tipo] ?? 'badge-pending';
    return '<span class="badge ' . $classe . '">' . h(log_tipo_label($tipo)) . '</span>';
}

function log_badge_nivel($nivel)
{
    $classes = [
        'info' => 'badge-active',
        'aviso' => 'badge-pending',
        'erro' => 'badge-inactive',
    ];
    $classe = $classes[$nivel] ?? 'badge-pending';
    return '<span class="badge ' . $classe . '">' . h(log_nivel_label($nivel)) . '</span>';
}

/**
 * Monta subquery unificada: auditoria + logs legados de sincronização.
 */
function log_sql_unificado()
{
    return "(
        SELECT CONCAT('sa-', a.id) AS chave, a.id, 'auditoria' AS origem,
               COALESCE(a.tipo, 'acao') AS tipo, COALESCE(a.nivel, 'info') AS nivel,
               a.modulo, a.acao, a.mensagem, a.entidade_id, a.usuario_id,
               a.ip, a.user_agent, a.detalhes_json, a.criado_em
        FROM sistema_auditoria a
        UNION ALL
        SELECT CONCAT('sl-', sl.id) AS chave, sl.id, 'sincronizacao' AS origem,
               'integracao' AS tipo,
               CASE sl.status WHEN 'erro' THEN 'erro' WHEN 'parcial' THEN 'aviso' ELSE 'info' END AS nivel,
               sl.entidade AS modulo, sl.tipo AS acao, sl.mensagem, sl.entidade_id, NULL AS usuario_id,
               NULL AS ip, NULL AS user_agent,
               JSON_OBJECT('status', sl.status, 'registros_processados', sl.registros_processados) AS detalhes_json,
               sl.criado_em
        FROM sincronizacao_log sl
    ) AS logs";
}

function log_montar_filtros($conn, array $params)
{
    $where = ['1=1'];

    $tipo = trim($params['tipo'] ?? '');
    if ($tipo !== '' && in_array($tipo, ['acao', 'integracao', 'erro'], true)) {
        $where[] = "logs.tipo = '" . $tipo . "'";
    }

    $nivel = trim($params['nivel'] ?? '');
    if ($nivel !== '' && in_array($nivel, ['info', 'aviso', 'erro'], true)) {
        $where[] = "logs.nivel = '" . $nivel . "'";
    }

    $modulo = trim($params['modulo'] ?? '');
    if ($modulo !== '') {
        $modulo_safe = mysqli_real_escape_string($conn, $modulo);
        $where[] = "logs.modulo LIKE '%{$modulo_safe}%'";
    }

    $usuario_id = (int) ($params['usuario_id'] ?? 0);
    if ($usuario_id > 0) {
        $where[] = 'logs.usuario_id = ' . $usuario_id;
    }

    $data_inicio = trim($params['data_inicio'] ?? '');
    if ($data_inicio !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_inicio)) {
        $where[] = "logs.criado_em >= '{$data_inicio} 00:00:00'";
    }

    $data_fim = trim($params['data_fim'] ?? '');
    if ($data_fim !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_fim)) {
        $where[] = "logs.criado_em <= '{$data_fim} 23:59:59'";
    }

    return implode(' AND ', $where);
}

function log_buscar_por_chave($conn, $chave)
{
    log_garantir_estrutura($conn);

    if (!preg_match('/^(sa|sl)-(\d+)$/', (string) $chave, $m)) {
        return null;
    }

    $origem = $m[1] === 'sa' ? 'auditoria' : 'sincronizacao';
    $id = (int) $m[2];

    if ($origem === 'auditoria') {
        $stmt = mysqli_prepare(
            $conn,
            'SELECT id, tipo, nivel, modulo, acao, mensagem, entidade_id, usuario_id, ip, user_agent, detalhes_json, criado_em
             FROM sistema_auditoria WHERE id = ? LIMIT 1'
        );
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        if (!$row) {
            return null;
        }
        $row['chave'] = 'sa-' . $row['id'];
        $row['origem'] = 'auditoria';
        return $row;
    }

    $stmt = mysqli_prepare(
        $conn,
        'SELECT id, entidade AS modulo, tipo AS acao, status, mensagem, entidade_id, registros_processados, criado_em
         FROM sincronizacao_log WHERE id = ? LIMIT 1'
    );
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    if (!$row) {
        return null;
    }

    $status = $row['status'] ?? 'info';
    return [
        'chave' => 'sl-' . $row['id'],
        'origem' => 'sincronizacao',
        'id' => (int) $row['id'],
        'tipo' => 'integracao',
        'nivel' => $status === 'erro' ? 'erro' : ($status === 'parcial' ? 'aviso' : 'info'),
        'modulo' => $row['modulo'],
        'acao' => $row['acao'],
        'mensagem' => $row['mensagem'],
        'entidade_id' => (int) $row['entidade_id'],
        'usuario_id' => null,
        'ip' => null,
        'user_agent' => null,
        'detalhes_json' => json_encode([
            'status' => $status,
            'registros_processados' => (int) ($row['registros_processados'] ?? 0),
        ], JSON_UNESCAPED_UNICODE),
        'criado_em' => $row['criado_em'],
    ];
}

function log_listar_usuarios_filtro($conn)
{
    $result = mysqli_query(
        $conn,
        'SELECT DISTINCT u.id, u.nome
         FROM usuarios u
         INNER JOIN sistema_auditoria a ON a.usuario_id = u.id
         ORDER BY u.nome ASC'
    );
    $lista = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $lista[] = $row;
        }
    }
    return $lista;
}

/**
 * Registra sincronização/integração (tabela legada + auditoria unificada).
 */
function log_sincronizacao($conn, $entidade, $entidade_id, $tipo, $status, $mensagem, $registros = 0)
{
    $entidade_id = (int) $entidade_id;
    $registros = (int) $registros;

    $stmt = mysqli_prepare(
        $conn,
        'INSERT INTO sincronizacao_log (entidade, entidade_id, tipo, status, mensagem, registros_processados)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'sisssi', $entidade, $entidade_id, $tipo, $status, $mensagem, $registros);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    log_integracao($conn, $entidade, 'sincronizar_' . $tipo, $entidade_id, $status, $mensagem, [
        'registros_processados' => $registros,
    ]);

    if ($status === 'erro') {
        log_erro($conn, $entidade, 'integracao_falha', $mensagem, $entidade_id, [
            'tipo_integracao' => $tipo,
            'registros_processados' => $registros,
        ]);
    }
}

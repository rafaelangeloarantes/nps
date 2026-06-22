<?php
/**
 * Relatórios de dashboard instanciados (template + evento + link público)
 */

require_once __DIR__ . '/templates.php';
require_once __DIR__ . '/log.php';

function dashboard_relatorio_garantir_estrutura($conn)
{
    static $ok = false;
    if ($ok) {
        return;
    }

    dashboard_template_garantir_estrutura($conn);

    mysqli_query(
        $conn,
        "CREATE TABLE IF NOT EXISTS `dashboard_relatorios` (
            `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `template_id` INT NOT NULL,
            `evento_id` INT NOT NULL,
            `nome` VARCHAR(255) NOT NULL,
            `token` VARCHAR(64) NOT NULL,
            `chave_hash` VARCHAR(255) NOT NULL,
            `chave_cripto` TEXT NULL,
            `chave_prefixo` VARCHAR(8) NULL,
            `criado_por` INT NULL,
            `ultimo_acesso_externo` DATETIME NULL,
            `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `ativo` TINYINT(1) NOT NULL DEFAULT 1,
            UNIQUE KEY `idx_dr_token` (`token`),
            KEY `idx_dr_template` (`template_id`),
            KEY `idx_dr_evento` (`evento_id`),
            KEY `idx_dr_ativo` (`ativo`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $col = mysqli_query($conn, "SHOW COLUMNS FROM dashboard_relatorios LIKE 'chave_cripto'");
    if ($col && mysqli_num_rows($col) === 0) {
        mysqli_query($conn, 'ALTER TABLE dashboard_relatorios ADD COLUMN chave_cripto TEXT NULL AFTER chave_hash');
    }

    $ok = true;
}

function dashboard_relatorio_gerar_token()
{
    return bin2hex(random_bytes(24));
}

function dashboard_relatorio_gerar_chave($tamanho = 8)
{
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $chave = '';
    $max = strlen($chars) - 1;
    for ($i = 0; $i < $tamanho; $i++) {
        $chave .= $chars[random_int(0, $max)];
    }
    return $chave;
}

function dashboard_relatorio_armazenar_chave($chave_plana)
{
    return [
        'hash' => password_hash($chave_plana, PASSWORD_BCRYPT, ['cost' => 10]),
        'cripto' => criptografar_texto($chave_plana),
        'prefixo' => substr($chave_plana, 0, 4),
    ];
}

/**
 * Recupera a chave em texto para exibição no painel (somente admin autenticado).
 */
function dashboard_relatorio_obter_chave_admin(array $relatorio)
{
    $cripto = trim((string) ($relatorio['chave_cripto'] ?? ''));
    if ($cripto === '') {
        return '';
    }

    return trim(descriptografar_texto($cripto));
}

function dashboard_relatorio_buscar($conn, $id)
{
    dashboard_relatorio_garantir_estrutura($conn);
    $id = (int) $id;
    if ($id <= 0) {
        return null;
    }

    $stmt = mysqli_prepare(
        $conn,
        'SELECT r.*, t.nome AS template_nome, e.nome AS evento_nome, e.contrato_id,
                e.data_inicio, e.data_fim, e.endereco, e.clima
         FROM dashboard_relatorios r
         INNER JOIN dashboard_templates t ON t.id = r.template_id AND t.ativo = 1
         INNER JOIN eventos e ON e.id = r.evento_id AND e.ativo = 1
         WHERE r.id = ? AND r.ativo = 1
         LIMIT 1'
    );
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if ($row) {
        $template = dashboard_template_buscar($conn, (int) $row['template_id']);
        $row['widgets'] = $template['widgets'] ?? [];
        $row['url_publica'] = dashboard_relatorio_url_publica($row['token']);
        $row['chave_acesso'] = dashboard_relatorio_obter_chave_admin($row);
    }

    return $row;
}

function dashboard_relatorio_buscar_por_token($conn, $token)
{
    dashboard_relatorio_garantir_estrutura($conn);
    $token = trim((string) $token);
    if ($token === '') {
        return null;
    }

    $stmt = mysqli_prepare(
        $conn,
        'SELECT r.*, t.nome AS template_nome, t.layout_json, e.nome AS evento_nome, e.contrato_id,
                e.data_inicio, e.data_fim, e.endereco, e.clima
         FROM dashboard_relatorios r
         INNER JOIN dashboard_templates t ON t.id = r.template_id AND t.ativo = 1
         INNER JOIN eventos e ON e.id = r.evento_id AND e.ativo = 1
         WHERE r.token = ? AND r.ativo = 1
         LIMIT 1'
    );
    mysqli_stmt_bind_param($stmt, 's', $token);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if ($row) {
        $row['widgets'] = dashboard_template_decodificar_layout($row['layout_json'] ?? '[]');
        unset($row['layout_json'], $row['chave_hash']);
        $row['url_publica'] = dashboard_relatorio_url_publica($row['token']);
    }

    return $row;
}

function dashboard_relatorio_url_publica($token)
{
    return app_base_url() . '/relatorio_publico.php?t=' . urlencode((string) $token);
}

function dashboard_relatorio_validar_chave($relatorio, $chave_informada)
{
    if (!$relatorio || empty($relatorio['chave_hash'])) {
        return false;
    }
    return password_verify((string) $chave_informada, $relatorio['chave_hash']);
}

/** Nome do cookie de sessão exclusivo do acesso público (isolado do painel admin). */
define('DASHBOARD_PUBLICO_SESS_NAME', 'NPS_REL_PUB');

/** Tempo máximo da sessão pública autenticada (8 horas). */
define('DASHBOARD_PUBLICO_SESS_TTL', 28800);

function dashboard_publico_iniciar_sessao()
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        if (session_name() === DASHBOARD_PUBLICO_SESS_NAME) {
            return;
        }
        session_write_close();
    }

    session_name(DASHBOARD_PUBLICO_SESS_NAME);
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function dashboard_publico_sessao_chave($token)
{
    return 'relatorio_publico_' . trim((string) $token);
}

function dashboard_publico_marcar_autenticado(array $relatorio)
{
    $token = trim((string) ($relatorio['token'] ?? ''));
    if ($token === '') {
        return;
    }

    $_SESSION[dashboard_publico_sessao_chave($token)] = [
        'em' => time(),
        'chave_ver' => substr((string) ($relatorio['chave_hash'] ?? ''), -12),
    ];
}

function dashboard_publico_esta_autenticado(array $relatorio)
{
    $token = trim((string) ($relatorio['token'] ?? ''));
    if ($token === '' || empty($relatorio['chave_hash'])) {
        return false;
    }

    $sess = $_SESSION[dashboard_publico_sessao_chave($token)] ?? null;
    if ($sess === null) {
        return false;
    }

    $autenticado_em = is_array($sess) ? (int) ($sess['em'] ?? 0) : (int) $sess;
    if ($autenticado_em <= 0 || (time() - $autenticado_em) > DASHBOARD_PUBLICO_SESS_TTL) {
        return false;
    }

    if (!is_array($sess)) {
        return true;
    }

    $chave_ver_atual = substr((string) $relatorio['chave_hash'], -12);
    return ($sess['chave_ver'] ?? '') === $chave_ver_atual;
}

function dashboard_relatorio_salvar($conn, array $dados)
{
    dashboard_relatorio_garantir_estrutura($conn);

    $id = (int) ($dados['id'] ?? 0);
    $template_id = (int) ($dados['template_id'] ?? 0);
    $evento_id = (int) ($dados['evento_id'] ?? 0);
    $nome = trim($dados['nome'] ?? '');
    $regenerar_chave = !empty($dados['regenerar_chave']);

    if ($template_id <= 0 || $evento_id <= 0) {
        return ['status' => 'error', 'message' => 'Template e evento são obrigatórios.'];
    }
    if ($nome === '') {
        return ['status' => 'error', 'message' => 'Nome do relatório é obrigatório.'];
    }

    $template = dashboard_template_buscar($conn, $template_id);
    if (!$template) {
        return ['status' => 'error', 'message' => 'Template não encontrado.'];
    }

    $chave_plana = null;
    $criado_por = !empty($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;

    if ($id > 0) {
        $atual = dashboard_relatorio_buscar($conn, $id);
        if (!$atual) {
            return ['status' => 'error', 'message' => 'Relatório não encontrado.'];
        }

        $token = $atual['token'];
        $chave_hash = $atual['chave_hash'];
        $chave_cripto = $atual['chave_cripto'] ?? '';
        $chave_prefixo = $atual['chave_prefixo'];

        if ($regenerar_chave) {
            $chave_plana = dashboard_relatorio_gerar_chave();
            $armazenada = dashboard_relatorio_armazenar_chave($chave_plana);
            $chave_hash = $armazenada['hash'];
            $chave_cripto = $armazenada['cripto'];
            $chave_prefixo = $armazenada['prefixo'];
        }

        $stmt = mysqli_prepare(
            $conn,
            'UPDATE dashboard_relatorios SET template_id = ?, evento_id = ?, nome = ?, chave_hash = ?, chave_cripto = ?, chave_prefixo = ? WHERE id = ?'
        );
        mysqli_stmt_bind_param($stmt, 'iissssi', $template_id, $evento_id, $nome, $chave_hash, $chave_cripto, $chave_prefixo, $id);
        $msg = 'Relatório atualizado com sucesso.';
        $acao = 'atualizar';
        $relatorio_id = $id;
    } else {
        $token = dashboard_relatorio_gerar_token();
        $chave_plana = dashboard_relatorio_gerar_chave();
        $armazenada = dashboard_relatorio_armazenar_chave($chave_plana);
        $chave_hash = $armazenada['hash'];
        $chave_cripto = $armazenada['cripto'];
        $chave_prefixo = $armazenada['prefixo'];

        $stmt = mysqli_prepare(
            $conn,
            'INSERT INTO dashboard_relatorios (template_id, evento_id, nome, token, chave_hash, chave_cripto, chave_prefixo, criado_por)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        mysqli_stmt_bind_param($stmt, 'iisssssi', $template_id, $evento_id, $nome, $token, $chave_hash, $chave_cripto, $chave_prefixo, $criado_por);
        $msg = 'Relatório criado com sucesso.';
        $acao = 'criar';
    }

    if (!mysqli_stmt_execute($stmt)) {
        $erro = mysqli_error($conn);
        mysqli_stmt_close($stmt);
        return ['status' => 'error', 'message' => 'Erro ao salvar relatório: ' . $erro];
    }
    mysqli_stmt_close($stmt);

    if ($id <= 0) {
        $relatorio_id = (int) mysqli_insert_id($conn);
    }

    auditoria_registrar($conn, 'dashboard_relatorio', $acao, $relatorio_id, [
        'nome' => $nome,
        'template_id' => $template_id,
        'evento_id' => $evento_id,
        'regenerou_chave' => $regenerar_chave,
    ]);

    $relatorio = dashboard_relatorio_buscar($conn, $relatorio_id);
    $chave_admin = dashboard_relatorio_obter_chave_admin($relatorio ?: []);
    if ($chave_plana === null && $chave_admin !== '') {
        $chave_plana = $chave_admin;
    }

    $extra = [
        'id' => $relatorio_id,
        'token' => $relatorio['token'] ?? $token,
        'url_publica' => $relatorio['url_publica'] ?? dashboard_relatorio_url_publica($token),
        'chave_prefixo' => $relatorio['chave_prefixo'] ?? $chave_prefixo,
    ];

    if ($chave_plana !== null && $chave_plana !== '') {
        $extra['chave_acesso'] = $chave_plana;
    }

    return ['status' => 'success', 'message' => $msg, 'data' => $extra];
}

function dashboard_relatorio_excluir($conn, $id)
{
    dashboard_relatorio_garantir_estrutura($conn);
    $id = (int) $id;
    if ($id <= 0) {
        return ['status' => 'error', 'message' => 'ID inválido.'];
    }

    $stmt = mysqli_prepare($conn, 'UPDATE dashboard_relatorios SET ativo = 0 WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return ['status' => 'error', 'message' => 'Erro ao excluir relatório.'];
    }
    mysqli_stmt_close($stmt);

    auditoria_registrar($conn, 'dashboard_relatorio', 'excluir', $id);
    return ['status' => 'success', 'message' => 'Relatório excluído com sucesso.'];
}

function dashboard_relatorio_registrar_acesso_externo($conn, $relatorio_id)
{
    $relatorio_id = (int) $relatorio_id;
    $stmt = mysqli_prepare($conn, 'UPDATE dashboard_relatorios SET ultimo_acesso_externo = NOW() WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $relatorio_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    auditoria_registrar($conn, 'dashboard_relatorio', 'acesso_externo', $relatorio_id);
}

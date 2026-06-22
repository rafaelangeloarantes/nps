<?php
/**
 * Funções do módulo Integrações (configurações centralizadas)
 */

define('INTEGRACAO_DOC_INTEEGRA', 'https://documenter.getpostman.com/view/25025078/2s93XtzjTG#intro');

function integracao_documentacao_padrao($codigo)
{
    $mapa = [
        'inteegra' => INTEGRACAO_DOC_INTEEGRA,
    ];
    return $mapa[$codigo] ?? '';
}

function integracao_garantir_estrutura($conn)
{
    static $ok = false;
    if ($ok) {
        return;
    }

    mysqli_query(
        $conn,
        'CREATE TABLE IF NOT EXISTS `integracoes` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `codigo` VARCHAR(50) NOT NULL,
            `nome` VARCHAR(255) NOT NULL,
            `descricao` TEXT NULL,
            `url_auth_base` VARCHAR(500) NULL,
            `url_api_base` VARCHAR(500) NULL,
            `url_documentacao` VARCHAR(500) NULL,
            `usuario_acesso` VARCHAR(255) NULL,
            `senha_acesso` TEXT NULL,
            `config_json` JSON NULL,
            `ativo` TINYINT(1) NOT NULL DEFAULT 1,
            `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `idx_integracoes_codigo` (`codigo`),
            KEY `idx_integracoes_ativo` (`ativo`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $col = mysqli_query($conn, "SHOW COLUMNS FROM integracoes LIKE 'url_documentacao'");
    if ($col && mysqli_num_rows($col) === 0) {
        mysqli_query(
            $conn,
            'ALTER TABLE integracoes ADD COLUMN url_documentacao VARCHAR(500) NULL AFTER url_api_base'
        );
    }

    $ok = true;
}

function integracao_garantir_padroes($conn)
{
    static $executado = false;
    if ($executado) {
        return;
    }

    integracao_garantir_estrutura($conn);
    $stmt = mysqli_prepare(
        $conn,
        'SELECT id FROM integracoes WHERE codigo = ? LIMIT 1'
    );
    $codigo = 'inteegra';
    mysqli_stmt_bind_param($stmt, 's', $codigo);
    mysqli_stmt_execute($stmt);
    $existe = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if ($existe) {
        integracao_atualizar_documentacao_padrao($conn);
        integracao_migrar_credenciais_legadas($conn);
        $executado = true;
        return;
    }

    $nome = 'Inteegra API';
    $descricao = 'Integração com a API externa Inteegra para importação de participantes (guests).';
    $url_auth = 'https://api-externa.inteegra.com.br/security/security';
    $url_api = 'https://api-externa.inteegra.com.br/public';
    $url_doc = integracao_documentacao_padrao('inteegra');
    $ativo = 1;

    $stmt = mysqli_prepare(
        $conn,
        'INSERT INTO integracoes (codigo, nome, descricao, url_auth_base, url_api_base, url_documentacao, ativo)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    mysqli_stmt_bind_param($stmt, 'ssssssi', $codigo, $nome, $descricao, $url_auth, $url_api, $url_doc, $ativo);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    integracao_migrar_credenciais_legadas($conn);
    $executado = true;
}

/**
 * Preenche URL de documentação padrão quando ainda não configurada.
 */
function integracao_atualizar_documentacao_padrao($conn)
{
    $padroes = [
        'inteegra' => integracao_documentacao_padrao('inteegra'),
    ];

    foreach ($padroes as $codigo => $url) {
        if ($url === '') {
            continue;
        }
        $stmt = mysqli_prepare(
            $conn,
            'UPDATE integracoes SET url_documentacao = ?
             WHERE codigo = ? AND (url_documentacao IS NULL OR url_documentacao = \'\')'
        );
        mysqli_stmt_bind_param($stmt, 'ss', $url, $codigo);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

/**
 * Copia credenciais antigas de contratos/eventos para a config central (uma vez).
 */
function integracao_migrar_credenciais_legadas($conn)
{
    $stmt = mysqli_prepare(
        $conn,
        'SELECT id, usuario_acesso FROM integracoes WHERE codigo = ? LIMIT 1'
    );
    $codigo = 'inteegra';
    mysqli_stmt_bind_param($stmt, 's', $codigo);
    mysqli_stmt_execute($stmt);
    $integracao = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$integracao || trim($integracao['usuario_acesso'] ?? '') !== '') {
        return;
    }

    $origem = null;
    foreach (['contratos', 'eventos'] as $tabela) {
        $result = mysqli_query(
            $conn,
            "SELECT usuario_acesso, senha_acesso FROM {$tabela}
             WHERE usuario_acesso IS NOT NULL AND usuario_acesso != ''
             AND senha_acesso IS NOT NULL AND senha_acesso != ''
             LIMIT 1"
        );
        if ($result && ($row = mysqli_fetch_assoc($result))) {
            $origem = $row;
            break;
        }
    }

    if (!$origem) {
        return;
    }

    $usuario = trim($origem['usuario_acesso']);
    $senha = $origem['senha_acesso'];
    $id = (int) $integracao['id'];

    $stmt = mysqli_prepare(
        $conn,
        'UPDATE integracoes SET usuario_acesso = ?, senha_acesso = ? WHERE id = ?'
    );
    mysqli_stmt_bind_param($stmt, 'ssi', $usuario, $senha, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

function integracao_listar($conn, $apenas_ativas = false)
{
    integracao_garantir_padroes($conn);

    $sql = 'SELECT id, codigo, nome, descricao, url_auth_base, url_api_base, url_documentacao, usuario_acesso, ativo, atualizado_em
            FROM integracoes';
    if ($apenas_ativas) {
        $sql .= ' WHERE ativo = 1';
    }
    $sql .= ' ORDER BY nome ASC';

    $result = mysqli_query($conn, $sql);
    $lista = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $lista[] = $row;
    }
    return $lista;
}

function integracao_buscar_por_codigo($conn, $codigo)
{
    integracao_garantir_padroes($conn);

    $stmt = mysqli_prepare(
        $conn,
        'SELECT * FROM integracoes WHERE codigo = ? LIMIT 1'
    );
    mysqli_stmt_bind_param($stmt, 's', $codigo);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    return $row ?: null;
}

function integracao_buscar_por_id($conn, $id)
{
    $stmt = mysqli_prepare($conn, 'SELECT * FROM integracoes WHERE id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    return $row ?: null;
}

function integracao_credenciais($conn, $codigo)
{
    $integracao = integracao_buscar_por_codigo($conn, $codigo);
    if (!$integracao) {
        return ['ok' => false, 'error' => 'Integração não encontrada: ' . $codigo];
    }

    if ((int) $integracao['ativo'] !== 1) {
        return ['ok' => false, 'error' => 'Integração inativa: ' . $integracao['nome']];
    }

    $login = trim($integracao['usuario_acesso'] ?? '');
    $senha_cripto = $integracao['senha_acesso'] ?? '';
    $senha = descriptografar_texto($senha_cripto);

    if ($login === '') {
        return [
            'ok' => false,
            'error' => 'Usuário de acesso não configurado em Configurações > ' . $integracao['nome'] . '.',
        ];
    }

    if ($senha === '' && $senha_cripto !== '') {
        return [
            'ok' => false,
            'error' => 'Não foi possível ler a senha salva. Recadastre a senha em Configurações.',
        ];
    }

    if ($senha === '') {
        return [
            'ok' => false,
            'error' => 'Senha de acesso não configurada em Configurações > ' . $integracao['nome'] . '.',
        ];
    }

    return [
        'ok' => true,
        'integracao' => $integracao,
        'login' => $login,
        'password' => $senha,
        'url_auth_base' => trim($integracao['url_auth_base'] ?? ''),
        'url_api_base' => trim($integracao['url_api_base'] ?? ''),
    ];
}

function integracao_salvar($conn, $dados)
{
    $id = (int) ($dados['id'] ?? 0);
    $nome = trim($dados['nome'] ?? '');
    $descricao = trim($dados['descricao'] ?? '');
    $url_auth = trim($dados['url_auth_base'] ?? '');
    $url_api = trim($dados['url_api_base'] ?? '');
    $url_doc = trim($dados['url_documentacao'] ?? '');
    $usuario = trim($dados['usuario_acesso'] ?? '');
    $senha = $dados['senha_acesso'] ?? '';
    $ativo = isset($dados['ativo']) ? (int) $dados['ativo'] : 1;

    if ($id <= 0) {
        return ['status' => 'error', 'message' => 'Integração inválida.'];
    }

    $atual = integracao_buscar_por_id($conn, $id);
    if (!$atual) {
        return ['status' => 'error', 'message' => 'Integração não encontrada.'];
    }

    if ($nome === '') {
        $nome = $atual['nome'];
    }
    if ($descricao === '') {
        $descricao = $atual['descricao'] ?? '';
    }

    if ($senha !== '') {
        $senha_cripto = criptografar_texto($senha);
        $stmt = mysqli_prepare(
            $conn,
            'UPDATE integracoes SET nome=?, descricao=?, url_auth_base=?, url_api_base=?, url_documentacao=?,
             usuario_acesso=?, senha_acesso=?, ativo=? WHERE id=?'
        );
        mysqli_stmt_bind_param(
            $stmt,
            'sssssssii',
            $nome,
            $descricao,
            $url_auth,
            $url_api,
            $url_doc,
            $usuario,
            $senha_cripto,
            $ativo,
            $id
        );
    } else {
        $stmt = mysqli_prepare(
            $conn,
            'UPDATE integracoes SET nome=?, descricao=?, url_auth_base=?, url_api_base=?, url_documentacao=?,
             usuario_acesso=?, ativo=? WHERE id=?'
        );
        mysqli_stmt_bind_param(
            $stmt,
            'ssssssii',
            $nome,
            $descricao,
            $url_auth,
            $url_api,
            $url_doc,
            $usuario,
            $ativo,
            $id
        );
    }

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return ['status' => 'success', 'message' => 'Integração salva com sucesso.', 'id' => $id];
    }

    $erro = mysqli_error($conn);
    mysqli_stmt_close($stmt);
    return ['status' => 'error', 'message' => 'Erro ao salvar: ' . $erro];
}

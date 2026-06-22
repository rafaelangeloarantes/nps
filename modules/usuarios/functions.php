<?php
/**
 * Funções do módulo Usuários e Governança
 */

require_once __DIR__ . '/../auth/permissoes.php';
require_once __DIR__ . '/../log/functions.php';

function usuario_buscar_por_id($conn, $id)
{
    $stmt = mysqli_prepare(
        $conn,
        'SELECT u.id, u.nome, u.email, u.perfil, u.contrato_id, c.nome AS contrato_nome,
                u.ultimo_login, u.ativo, u.criado_em
         FROM usuarios u
         LEFT JOIN contratos c ON c.id = u.contrato_id
         WHERE u.id = ? AND u.ativo = 1
         LIMIT 1'
    );
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    return $row ?: null;
}

function usuario_email_em_uso($conn, $email, $excluir_id = 0)
{
    $stmt = mysqli_prepare(
        $conn,
        'SELECT id FROM usuarios WHERE email = ? AND ativo = 1 AND id != ? LIMIT 1'
    );
    mysqli_stmt_bind_param($stmt, 'si', $email, $excluir_id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    return (bool) $row;
}

function usuario_contar_masters_ativos($conn, $excluir_id = 0)
{
    $excluir_id = (int) $excluir_id;
    $sql = "SELECT COUNT(*) AS t FROM usuarios WHERE ativo = 1 AND perfil = 'admin_master'";
    if ($excluir_id > 0) {
        $sql .= ' AND id != ' . $excluir_id;
    }
    return (int) mysqli_fetch_assoc(mysqli_query($conn, $sql))['t'];
}

function usuario_salvar($conn, $dados)
{
    $id = (int) ($dados['id'] ?? 0);
    $nome = trim($dados['nome'] ?? '');
    $email = filter_var(trim($dados['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $perfil = trim($dados['perfil'] ?? '');
    $senha = $dados['senha'] ?? '';
    $ativo = isset($dados['ativo']) ? (int) $dados['ativo'] : 1;
    $contrato_id = isset($dados['contrato_id']) && $dados['contrato_id'] !== ''
        ? (int) $dados['contrato_id']
        : null;

    $perfis = array_keys(perfis_disponiveis());
    if ($nome === '') {
        return ['status' => 'error', 'message' => 'Nome é obrigatório.'];
    }
    if (!$email) {
        return ['status' => 'error', 'message' => 'E-mail inválido.'];
    }
    if (!in_array($perfil, $perfis, true)) {
        return ['status' => 'error', 'message' => 'Perfil inválido.'];
    }
    if (usuario_email_em_uso($conn, $email, $id)) {
        return ['status' => 'error', 'message' => 'Este e-mail já está em uso.'];
    }

    if ($perfil === 'usuario' && (!$contrato_id || $contrato_id <= 0)) {
        return ['status' => 'error', 'message' => 'Usuário deve estar vinculado a um contrato.'];
    }
    if ($perfil === 'admin_master') {
        $contrato_id = null;
    }

    if ($id > 0) {
        $atual = usuario_buscar_por_id($conn, $id);
        if (!$atual) {
            return ['status' => 'error', 'message' => 'Usuário não encontrado.'];
        }
        if ($atual['perfil'] === 'admin_master' && $perfil !== 'admin_master' && usuario_contar_masters_ativos($conn, $id) === 0) {
            return ['status' => 'error', 'message' => 'Não é possível alterar o perfil do último Administrador Master ativo.'];
        }
        if ((int) $id === (int) ($_SESSION['user_id'] ?? 0) && $ativo === 0) {
            return ['status' => 'error', 'message' => 'Você não pode inativar seu próprio usuário.'];
        }

        if ($senha !== '') {
            $erro_senha = validar_politica_senha($senha);
            if ($erro_senha) {
                return ['status' => 'error', 'message' => $erro_senha];
            }
            $hash = criar_hash_senha($senha);
            $stmt = mysqli_prepare(
                $conn,
                'UPDATE usuarios SET nome=?, email=?, senha_hash=?, perfil=?, contrato_id=?, ativo=?
                 WHERE id=?'
            );
            mysqli_stmt_bind_param(
                $stmt,
                'ssssiii',
                $nome,
                $email,
                $hash,
                $perfil,
                $contrato_id,
                $ativo,
                $id
            );
        } else {
            $stmt = mysqli_prepare(
                $conn,
                'UPDATE usuarios SET nome=?, email=?, perfil=?, contrato_id=?, ativo=?
                 WHERE id=?'
            );
            mysqli_stmt_bind_param(
                $stmt,
                'sssiii',
                $nome,
                $email,
                $perfil,
                $contrato_id,
                $ativo,
                $id
            );
        }
        $msg = 'Usuário atualizado com sucesso.';
    } else {
        if ($senha === '') {
            return ['status' => 'error', 'message' => 'Senha é obrigatória para novo usuário.'];
        }
        $erro_senha = validar_politica_senha($senha);
        if ($erro_senha) {
            return ['status' => 'error', 'message' => $erro_senha];
        }
        $hash = criar_hash_senha($senha);
        $stmt = mysqli_prepare(
            $conn,
            'INSERT INTO usuarios (nome, email, senha_hash, perfil, contrato_id, ativo)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        mysqli_stmt_bind_param(
            $stmt,
            'ssssii',
            $nome,
            $email,
            $hash,
            $perfil,
            $contrato_id,
            $ativo
        );
        $msg = 'Usuário cadastrado com sucesso.';
    }

    if (mysqli_stmt_execute($stmt)) {
        $novo_id = $id > 0 ? $id : mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);
        log_acao($conn, 'usuarios', $id > 0 ? 'editar' : 'criar', $novo_id, ['email' => $email, 'perfil' => $perfil]);
        return ['status' => 'success', 'message' => $msg, 'id' => $novo_id];
    }

    $erro = mysqli_error($conn);
    mysqli_stmt_close($stmt);
    return ['status' => 'error', 'message' => 'Erro ao salvar: ' . $erro];
}

function usuario_excluir($conn, $id)
{
    $id = (int) $id;
    if ($id <= 0) {
        return ['status' => 'error', 'message' => 'ID inválido.'];
    }
    if ($id === (int) ($_SESSION['user_id'] ?? 0)) {
        return ['status' => 'error', 'message' => 'Você não pode excluir seu próprio usuário.'];
    }

    $user = usuario_buscar_por_id($conn, $id);
    if (!$user) {
        return ['status' => 'error', 'message' => 'Usuário não encontrado.'];
    }
    if ($user['perfil'] === 'admin_master' && usuario_contar_masters_ativos($conn, $id) === 0) {
        return ['status' => 'error', 'message' => 'Não é possível excluir o último Administrador Master ativo.'];
    }

    $stmt = mysqli_prepare($conn, 'UPDATE usuarios SET ativo = 0 WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if ($ok) {
        log_acao($conn, 'usuarios', 'excluir', $id, ['email' => $user['email'] ?? '']);
    }

    return $ok
        ? ['status' => 'success', 'message' => 'Usuário excluído com sucesso.']
        : ['status' => 'error', 'message' => 'Erro ao excluir usuário.'];
}

/**
 * Solicita recuperação de senha — gera token e envia e-mail.
 */
function usuario_solicitar_recuperacao_senha($conn, $email)
{
    $email = filter_var(trim($email), FILTER_VALIDATE_EMAIL);
    if (!$email) {
        return ['status' => 'error', 'message' => 'Informe um e-mail válido.'];
    }

    $stmt = mysqli_prepare($conn, 'SELECT id, nome, email, ativo FROM usuarios WHERE email = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    // Resposta genérica por segurança (não revelar se e-mail existe)
    $msg_ok = 'Se o e-mail estiver cadastrado, você receberá instruções para redefinir a senha.';

    if (!$user || !(int) $user['ativo']) {
        return ['status' => 'success', 'message' => $msg_ok];
    }

    $token = bin2hex(random_bytes(32));
    $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));

    $stmt = mysqli_prepare(
        $conn,
        'UPDATE usuarios SET token_reset = ?, token_reset_expira = ? WHERE id = ?'
    );
    mysqli_stmt_bind_param($stmt, 'ssi', $token, $expira, $user['id']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    require_once __DIR__ . '/../email/functions.php';
    $base_url = rtrim($_ENV['APP_URL'] ?? '', '/');
    if ($base_url === '') {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $base = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        $base_url = $scheme . '://' . $host . rtrim(str_replace('\\', '/', $base), '/');
    }

    if (!enviar_email_recuperacao_senha($conn, $user, $token, $base_url)) {
        if (!sendgrid_configurado()) {
            return ['status' => 'error', 'message' => 'Serviço de e-mail não configurado. Configure SENDGRID_API_KEY no .env.'];
        }
        return ['status' => 'error', 'message' => 'Não foi possível enviar o e-mail. Tente novamente mais tarde.'];
    }

    return ['status' => 'success', 'message' => $msg_ok];
}

/**
 * Redefine senha com token válido.
 */
function usuario_redefinir_senha_token($conn, $token, $senha, $senha_confirmacao)
{
    $token = trim($token);
    if ($token === '' || strlen($token) !== 64) {
        return ['status' => 'error', 'message' => 'Link inválido ou expirado.'];
    }
    if ($senha !== $senha_confirmacao) {
        return ['status' => 'error', 'message' => 'As senhas não conferem.'];
    }
    $erro_senha = validar_politica_senha($senha);
    if ($erro_senha) {
        return ['status' => 'error', 'message' => $erro_senha];
    }

    $stmt = mysqli_prepare(
        $conn,
        'SELECT id FROM usuarios
         WHERE token_reset = ? AND token_reset_expira > NOW() AND ativo = 1
         LIMIT 1'
    );
    mysqli_stmt_bind_param($stmt, 's', $token);
    mysqli_stmt_execute($stmt);
    $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$user) {
        return ['status' => 'error', 'message' => 'Link inválido ou expirado.'];
    }

    $hash = criar_hash_senha($senha);
    $uid = (int) $user['id'];
    $stmt = mysqli_prepare(
        $conn,
        'UPDATE usuarios SET senha_hash = ?, token_reset = NULL, token_reset_expira = NULL,
         tentativas_login = 0, bloqueado_ate = NULL WHERE id = ?'
    );
    mysqli_stmt_bind_param($stmt, 'si', $hash, $uid);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $ok
        ? ['status' => 'success', 'message' => 'Senha redefinida com sucesso. Faça login com a nova senha.']
        : ['status' => 'error', 'message' => 'Erro ao redefinir senha.'];
}

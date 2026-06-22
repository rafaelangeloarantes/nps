<?php
/**
 * Funções de autenticação administrativa
 */

require_once __DIR__ . '/permissoes.php';
require_once __DIR__ . '/../log/functions.php';

function autenticar_usuario($conn, $email, $senha)
{
    $email = filter_var(trim($email), FILTER_VALIDATE_EMAIL);
    if (!$email) {
        return ['status' => 'error', 'message' => 'E-mail inválido.'];
    }

    $stmt = mysqli_prepare(
        $conn,
        'SELECT id, nome, email, senha_hash, perfil, contrato_id,
                tentativas_login, bloqueado_ate, ativo
         FROM usuarios WHERE email = ? LIMIT 1'
    );
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$user || !(int) $user['ativo']) {
        log_acao($conn, 'auth', 'login_falha', 0, ['email' => $email, 'motivo' => 'credenciais_invalidas']);
        return ['status' => 'error', 'message' => 'Credenciais inválidas.'];
    }

    if ($user['bloqueado_ate'] && strtotime($user['bloqueado_ate']) > time()) {
        log_acao($conn, 'auth', 'login_falha', (int) $user['id'], ['email' => $email, 'motivo' => 'conta_bloqueada']);
        return ['status' => 'error', 'message' => 'Conta bloqueada. Tente novamente mais tarde.'];
    }

    if (!password_verify($senha, $user['senha_hash'])) {
        registrar_tentativa_falha($conn, (int) $user['id'], (int) $user['tentativas_login']);
        log_acao($conn, 'auth', 'login_falha', (int) $user['id'], ['email' => $email, 'motivo' => 'senha_incorreta']);
        return ['status' => 'error', 'message' => 'Credenciais inválidas.'];
    }

    if ($user['perfil'] === 'usuario' && empty($user['contrato_id'])) {
        log_acao($conn, 'auth', 'login_falha', (int) $user['id'], ['email' => $email, 'motivo' => 'sem_contrato']);
        return ['status' => 'error', 'message' => 'Usuário sem contrato vinculado. Contate o administrador.'];
    }

    resetar_tentativas($conn, (int) $user['id']);
    iniciar_sessao_usuario($user);
    carregar_dados_usuario_sessao($conn, (int) $user['id']);
    log_acao($conn, 'auth', 'login', (int) $user['id'], ['email' => $user['email']]);

    return ['status' => 'success', 'message' => 'Login realizado com sucesso.'];
}

function iniciar_sessao_usuario($user)
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['user_nome'] = $user['nome'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_perfil'] = $user['perfil'];
    $_SESSION['login_time'] = time();
}

function verificar_sessao()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
    if (time() - ($_SESSION['login_time'] ?? 0) > 7200) {
        session_destroy();
        header('Location: login.php?msg=sessao_expirada');
        exit;
    }
}

function tem_permissao($perfil_necessario)
{
    if ($perfil_necessario === 'admin' || $perfil_necessario === 'admin_master') {
        return eh_admin_master();
    }
    return true;
}

function registrar_tentativa_falha($conn, $user_id, $tentativas)
{
    $tentativas++;
    $bloqueio = $tentativas >= 5 ? date('Y-m-d H:i:s', strtotime('+15 minutes')) : null;
    $stmt = mysqli_prepare($conn, 'UPDATE usuarios SET tentativas_login = ?, bloqueado_ate = ? WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'isi', $tentativas, $bloqueio, $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

function resetar_tentativas($conn, $user_id)
{
    $stmt = mysqli_prepare($conn, 'UPDATE usuarios SET tentativas_login = 0, bloqueado_ate = NULL, ultimo_login = NOW() WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

function json_response($status, $message, $data = null)
{
    header('Content-Type: application/json; charset=utf-8');
    $out = ['status' => $status, 'message' => $message];
    if ($data !== null) {
        $out['data'] = $data;
    }
    echo json_encode($out);
    exit;
}

function badge_ativo($ativo)
{
    return (int) $ativo
        ? '<span class="badge badge-active">Ativo</span>'
        : '<span class="badge badge-inactive">Inativo</span>';
}

function badge_incompleto($dado_incompleto, $motivo)
{
    if (!(int) $dado_incompleto) {
        return '<span class="badge badge-active">Completo</span>';
    }
    $labels = [
        'sem_email' => 'Sem e-mail',
        'email_duplicado' => 'E-mail duplicado',
    ];
    $label = $labels[$motivo] ?? 'Incompleto';
    return '<span class="badge badge-pending">' . h($label) . '</span>';
}

function formatar_data_hora($data)
{
    if (empty($data)) {
        return '—';
    }
    return date('d/m/Y H:i', strtotime($data));
}

function formatar_data($data)
{
    if (empty($data)) {
        return '—';
    }
    return date('d/m/Y', strtotime($data));
}

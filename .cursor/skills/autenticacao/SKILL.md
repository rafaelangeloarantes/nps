# Skill: Módulo de Autenticação

Use esta skill quando o usuário pedir login, autenticação, controle de acesso ou gestão de sessões.

## Estrutura

```
/modules/auth/
  login.php           → tela de login
  logout.php          → encerrar sessão
  functions.php       → funções de autenticação
  middleware.php      → verificação de sessão (include no topo das páginas protegidas)

/ajax/
  auth_login.php      → processar login via AJAX
  auth_verificar.php  → verificar se sessão está ativa

/sql/structure/
  usuarios_auth.sql   → tabela com campos de autenticação
```

## Tabela SQL

```sql
CREATE TABLE IF NOT EXISTS `usuarios` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nome` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `senha_hash` VARCHAR(255) NOT NULL,
    `perfil` ENUM('admin','editor','viewer') DEFAULT 'viewer',
    `ultimo_login` DATETIME NULL,
    `tentativas_login` INT DEFAULT 0,
    `bloqueado_ate` DATETIME NULL,
    `token_reset` VARCHAR(64) NULL,
    `token_reset_expira` DATETIME NULL,
    `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `atualizado_em` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `ativo` TINYINT(1) DEFAULT 1,
    UNIQUE KEY `idx_usuarios_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Funções de Autenticação

```php
<?php
// modules/auth/functions.php

function autenticar_usuario($conn, $email, $senha) {
    $email = filter_var($email, FILTER_VALIDATE_EMAIL);
    if (!$email) return ['status' => 'error', 'message' => 'E-mail inválido.'];

    $stmt = mysqli_prepare($conn, "SELECT id, nome, email, senha_hash, perfil, tentativas_login, bloqueado_ate, ativo FROM usuarios WHERE email = ?");
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$user || !$user['ativo']) {
        return ['status' => 'error', 'message' => 'Credenciais inválidas.'];
    }

    // Verificar bloqueio por tentativas
    if ($user['bloqueado_ate'] && strtotime($user['bloqueado_ate']) > time()) {
        return ['status' => 'error', 'message' => 'Conta bloqueada. Tente novamente mais tarde.'];
    }

    if (!password_verify($senha, $user['senha_hash'])) {
        registrar_tentativa_falha($conn, $user['id'], $user['tentativas_login']);
        return ['status' => 'error', 'message' => 'Credenciais inválidas.'];
    }

    // Login OK - resetar tentativas e registrar
    resetar_tentativas($conn, $user['id']);
    iniciar_sessao($user);

    return ['status' => 'success', 'message' => 'Login realizado.'];
}

function iniciar_sessao($user) {
    session_regenerate_id(true);
    $_SESSION['user_id']    = $user['id'];
    $_SESSION['user_nome']  = $user['nome'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_perfil'] = $user['perfil'];
    $_SESSION['login_time'] = time();
}

function verificar_sessao() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['user_id'])) {
        header('Location: /modules/auth/login.php');
        exit;
    }
    // Timeout de 2 horas
    if (time() - ($_SESSION['login_time'] ?? 0) > 7200) {
        session_destroy();
        header('Location: /modules/auth/login.php?msg=sessao_expirada');
        exit;
    }
}

function tem_permissao($perfil_necessario) {
    $hierarquia = ['viewer' => 1, 'editor' => 2, 'admin' => 3];
    $perfil_atual = $_SESSION['user_perfil'] ?? 'viewer';
    return ($hierarquia[$perfil_atual] ?? 0) >= ($hierarquia[$perfil_necessario] ?? 0);
}

function registrar_tentativa_falha($conn, $user_id, $tentativas) {
    $tentativas++;
    $bloqueio = $tentativas >= 5 ? date('Y-m-d H:i:s', strtotime('+15 minutes')) : null;
    $stmt = mysqli_prepare($conn, "UPDATE usuarios SET tentativas_login = ?, bloqueado_ate = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'isi', $tentativas, $bloqueio, $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

function resetar_tentativas($conn, $user_id) {
    $stmt = mysqli_prepare($conn, "UPDATE usuarios SET tentativas_login = 0, bloqueado_ate = NULL, ultimo_login = NOW() WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

function criar_hash_senha($senha) {
    return password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]);
}
```

## Middleware (incluir no topo de páginas protegidas)

```php
<?php
// modules/auth/middleware.php
require_once __DIR__ . '/functions.php';
verificar_sessao();
```

## Uso nas páginas

```php
<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/modules/auth/middleware.php';

// Verificar perfil específico
if (!tem_permissao('admin')) {
    header('HTTP/1.1 403 Forbidden');
    die('Acesso negado.');
}

// ... resto da página
```

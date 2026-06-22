<?php
require_once __DIR__ . '/bootstrap.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$token = trim($_GET['token'] ?? '');
$app_name = $_ENV['APP_NAME'] ?? 'NPS Relatórios';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir senha — <?= h($app_name) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= h(asset('css/style.css')) ?>">
    <link rel="stylesheet" href="<?= h(asset('css/form.css')) ?>">
</head>
<body class="login-page">
<div class="login-wrapper">
    <div class="login-card">
        <div class="login-brand">
            <div class="brand-icon"><i class="bi bi-key"></i></div>
            <h1>Nova senha</h1>
            <p>Defina uma senha forte para sua conta</p>
        </div>

        <div id="alertRedefinir" class="alert-placeholder"></div>

        <?php if ($token === '' || strlen($token) !== 64): ?>
            <div class="alert alert-danger">Link inválido. Solicite uma nova recuperação de senha.</div>
            <p class="login-footer-link"><a href="recuperar_senha.php">Solicitar novo link</a></p>
        <?php else: ?>
            <form id="formRedefinirSenha" class="form-login form-model" novalidate>
                <input type="hidden" id="token" name="token" value="<?= h($token) ?>">
                <div class="form-group">
                    <label class="form-label" for="senha">Nova senha</label>
                    <input type="password" id="senha" name="senha" class="form-control" required autocomplete="new-password">
                    <small class="form-hint">Mín. 8 caracteres, maiúscula, minúscula, número e caractere especial.</small>
                </div>
                <div class="form-group">
                    <label class="form-label" for="senha_confirmacao">Confirmar senha</label>
                    <input type="password" id="senha_confirmacao" name="senha_confirmacao" class="form-control" required autocomplete="new-password">
                </div>
                <button type="submit" class="btn-primary btn-block">
                    <i class="bi bi-check-lg"></i> Redefinir senha
                </button>
            </form>
        <?php endif; ?>

        <p class="login-footer-link">
            <a href="login.php"><i class="bi bi-arrow-left"></i> Voltar ao login</a>
        </p>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="<?= h(asset('js/main.js')) ?>"></script>
<script src="<?= h(asset('js/auth-recuperar.js')) ?>"></script>
</body>
</html>

<?php
require_once __DIR__ . '/bootstrap.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$app_name = $_ENV['APP_NAME'] ?? 'NPS Relatórios';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar senha — <?= h($app_name) ?></title>
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
            <div class="brand-icon"><i class="bi bi-envelope"></i></div>
            <h1>Recuperar senha</h1>
            <p>Informe seu e-mail para receber o link de redefinição</p>
        </div>

        <div id="alertRecuperar" class="alert-placeholder"></div>

        <form id="formEsqueciSenha" class="form-login form-model" novalidate>
            <div class="form-group">
                <label class="form-label" for="email_recuperar">E-mail</label>
                <input type="email" id="email_recuperar" name="email" class="form-control" required autocomplete="username" placeholder="seu@email.com">
            </div>
            <button type="submit" class="btn-primary btn-block">
                <i class="bi bi-send"></i> Enviar link
            </button>
        </form>

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

<?php
require_once __DIR__ . '/bootstrap.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$msg = '';
if (isset($_GET['msg']) && $_GET['msg'] === 'sessao_expirada') {
    $msg = 'Sua sessão expirou. Faça login novamente.';
}

$app_name = $_ENV['APP_NAME'] ?? 'NPS Relatórios';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — <?= h($app_name) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= h(asset('css/style.css')) ?>">
    <link rel="stylesheet" href="<?= h(asset('css/form.css')) ?>">
    <link rel="stylesheet" href="<?= h(asset('css/loading.css')) ?>">
</head>
<body class="login-page">
<div class="login-wrapper">
    <div class="login-card">
        <div class="login-brand">
            <img src="<?= h(asset('logotipo.png')) ?>" alt="<?= h($app_name) ?>" class="brand-logo brand-logo-login">
            <p>Painel Administrativo de Relatórios</p>
        </div>

        <?php if ($msg): ?>
            <div class="alert alert-warning"><?= h($msg) ?></div>
        <?php endif; ?>

        <div id="alertLogin" class="alert-placeholder"></div>

        <form id="formLogin" class="form-login form-model" novalidate>
            <div class="form-group">
                <label class="form-label" for="email">E-mail</label>
                <input type="email" id="email" name="email" class="form-control" required autocomplete="username" placeholder="seu@email.com">
            </div>
            <div class="form-group">
                <label class="form-label" for="senha">Senha</label>
                <input type="password" id="senha" name="senha" class="form-control" required autocomplete="current-password" placeholder="••••••••">
            </div>
            <p class="login-forgot"><a href="recuperar_senha.php">Esqueci minha senha</a></p>
            <button type="submit" class="btn-primary btn-block">
                <i class="bi bi-box-arrow-in-right"></i> Entrar
            </button>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="<?= h(asset('js/loading.js')) ?>"></script>
<script src="<?= h(asset('js/main.js')) ?>"></script>
<script src="<?= h(asset('js/auth.js')) ?>"></script>
</body>
</html>

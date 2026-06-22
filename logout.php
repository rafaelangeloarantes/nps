<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/modules/auth/functions.php';
require_once __DIR__ . '/modules/log/functions.php';

session_start();

if (!empty($_SESSION['user_id'])) {
    log_acao($conn, 'auth', 'logout', (int) $_SESSION['user_id'], [
        'email' => $_SESSION['user_email'] ?? '',
    ]);
}

session_destroy();
header('Location: login.php');
exit;

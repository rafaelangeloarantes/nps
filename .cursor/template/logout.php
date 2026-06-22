<?php
/**
 * Logout — System Designer
 * Destrói a sessão e redireciona para o index.
 */
session_start();
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();
header('Location: index.php', true, 302);
exit;

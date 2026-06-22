<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../modules/usuarios/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$email = trim($_POST['email'] ?? '');
$resultado = usuario_solicitar_recuperacao_senha($conn, $email);
json_response($resultado['status'], $resultado['message']);

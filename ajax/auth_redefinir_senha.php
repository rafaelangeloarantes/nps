<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../modules/usuarios/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$token = trim($_POST['token'] ?? '');
$senha = $_POST['senha'] ?? '';
$senha_confirmacao = $_POST['senha_confirmacao'] ?? '';

$resultado = usuario_redefinir_senha_token($conn, $token, $senha, $senha_confirmacao);
json_response($resultado['status'], $resultado['message']);

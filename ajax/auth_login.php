<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../modules/auth/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$email = trim($_POST['email'] ?? '');
$senha = $_POST['senha'] ?? '';

if ($email === '' || $senha === '') {
    json_response('error', 'Preencha e-mail e senha.');
}

$resultado = autenticar_usuario($conn, $email, $senha);
json_response($resultado['status'], $resultado['message']);

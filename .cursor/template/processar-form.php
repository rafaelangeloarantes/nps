<?php
/**
 * Processamento do formulário modelo — System Designer
 * CSRF validado; session_start único no topo.
 */
session_start();
header('Content-Type: text/html; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php?p=formularios');
    exit;
}

$tokenPost = isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : '';
$tokenSess = isset($_SESSION['csrf_token']) ? (string) $_SESSION['csrf_token'] : '';
if ($tokenSess === '' || !hash_equals($tokenSess, $tokenPost)) {
    $_SESSION['form_erros'] = ['Sessão inválida ou expirada. Envie o formulário novamente.'];
    header('Location: index.php?p=formularios&erro=1');
    exit;
}

$nome         = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_SPECIAL_CHARS);
$nome         = $nome !== null ? trim($nome) : '';
$erros        = [];
if ($nome === '') {
    $erros[] = 'Nome é obrigatório.';
}

if (count($erros) > 0) {
    $_SESSION['form_erros'] = $erros;
    $_SESSION['form_data']  = $_POST;
    header('Location: index.php?p=formularios&erro=1');
    exit;
}

$_SESSION['csrf_token']   = bin2hex(random_bytes(16));
$_SESSION['form_success'] = true;
$_SESSION['form_data']   = ['nome' => $nome];
header('Location: index.php?p=formularios&ok=1');
exit;

<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/functions.php';
verificar_sessao();

global $conn;
if (!empty($_SESSION['user_id']) && !array_key_exists('user_contrato_id', $_SESSION)) {
    carregar_dados_usuario_sessao($conn, (int) $_SESSION['user_id']);
}

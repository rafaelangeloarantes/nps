<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../modules/auth/middleware.php';
require_once __DIR__ . '/../modules/campos_padrao/functions.php';

$categorias = null;
if (!empty($_GET['categoria'])) {
    $categorias = array_map('trim', explode(',', $_GET['categoria']));
}

$lista = campo_padrao_listar_opcoes($conn, $categorias);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['status' => 'success', 'data' => $lista]);

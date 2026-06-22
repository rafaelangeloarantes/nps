<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../modules/auth/middleware.php';
require_once __DIR__ . '/../modules/eventos/functions.php';

$contrato_id = (int) ($_GET['contrato_id'] ?? 0);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['data' => evento_listar_opcoes($conn, $contrato_id ?: null)]);

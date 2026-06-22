<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../modules/auth/middleware.php';
require_once __DIR__ . '/../modules/contratos/functions.php';

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['data' => contrato_listar_opcoes($conn)]);

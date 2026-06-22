<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../modules/auth/middleware.php';
require_once __DIR__ . '/../modules/integracao/participantes_sync.php';

$evento_id = (int) ($_GET['evento_id'] ?? $_POST['evento_id'] ?? 0);
if ($evento_id <= 0) {
    json_response('error', 'Evento inválido.');
}

$resultado = evento_descobrir_atributos_api($conn, $evento_id);
json_response($resultado['status'], $resultado['message'], $resultado['data'] ?? null);

<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../modules/auth/middleware.php';
require_once __DIR__ . '/../modules/eventos/atributos.php';

$evento_id = (int) ($_GET['evento_id'] ?? 0);
if ($evento_id <= 0) {
    json_response('error', 'Evento inválido.');
}

$lista = evento_atributo_listar($conn, $evento_id);
json_response('success', 'OK', [
    'atributos' => $lista,
    'campos_destino' => evento_atributo_campos_destino(),
    'tipos_grafico' => evento_atributo_tipos_grafico(),
]);

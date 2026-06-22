<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../modules/auth/middleware.php';
require_once __DIR__ . '/../modules/auth/permissoes.php';
require_once __DIR__ . '/../modules/dashboard/templates.php';

$evento_id = (int) ($_GET['evento_id'] ?? 0);
if ($evento_id <= 0) {
    json_response('error', 'Informe o ID do evento para carregar os campos disponíveis.');
}

exigir_acesso_evento($conn, $evento_id);

$catalogo = dashboard_campos_disponiveis_evento($conn, $evento_id);
$tipos_grafico = dashboard_tipos_grafico();
$tipos_bloco = dashboard_tipos_bloco();
$fontes = dashboard_fontes_campo();

json_response('success', 'OK', [
    'catalogo' => $catalogo,
    'tipos_grafico' => $tipos_grafico,
    'tipos_bloco' => $tipos_bloco,
    'fontes' => $fontes,
]);

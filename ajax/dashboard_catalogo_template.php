<?php

require_once __DIR__ . '/../bootstrap.php';

require_once __DIR__ . '/../modules/auth/middleware.php';

require_once __DIR__ . '/../modules/auth/permissoes.php';

require_once __DIR__ . '/../modules/dashboard/templates.php';



exigir_admin_master();



$catalogo = dashboard_catalogo_template($conn);



json_response('success', 'OK', [

    'catalogo' => $catalogo,

    'tipos_bloco' => dashboard_tipos_bloco(),

    'tipos_grafico' => dashboard_tipos_grafico(),

    'tipos_metrica' => dashboard_tipos_metrica(),

    'tamanhos_bloco' => dashboard_tamanhos_bloco(),
    'colunas_linha' => dashboard_colunas_linha_opcoes(),
    'fontes' => dashboard_fontes_campo(),
]);


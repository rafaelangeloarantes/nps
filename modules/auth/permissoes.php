<?php

/**

 * Governança de perfis

 *

 * admin_master — acesso total ao sistema

 * usuario      — relatórios de todos os eventos do contrato vinculado (visualizar e exportar)

 */



function perfis_disponiveis()

{

    return [

        'admin_master' => 'Administrador Master',

        'usuario' => 'Usuário',

    ];

}



function perfil_label($perfil)

{

    $labels = perfis_disponiveis();

    return $labels[$perfil] ?? ucfirst((string) $perfil);

}



function badge_perfil($perfil)

{

    $label = h(perfil_label($perfil));

    $classe = $perfil === 'admin_master' ? 'badge-active' : 'badge-pending';

    return '<span class="badge ' . $classe . '">' . $label . '</span>';

}



function eh_admin_master()

{

    return ($_SESSION['user_perfil'] ?? '') === 'admin_master';

}



function eh_usuario_contrato()

{

    return ($_SESSION['user_perfil'] ?? '') === 'usuario';

}



function obter_contrato_usuario()

{

    if (eh_admin_master()) {

        return null;

    }

    $id = (int) ($_SESSION['user_contrato_id'] ?? 0);

    return $id > 0 ? $id : null;

}



/**

 * Carrega contrato na sessão após login.

 */

function carregar_dados_usuario_sessao($conn, $user_id)

{

    $stmt = mysqli_prepare(

        $conn,

        'SELECT contrato_id FROM usuarios WHERE id = ? LIMIT 1'

    );

    mysqli_stmt_bind_param($stmt, 'i', $user_id);

    mysqli_stmt_execute($stmt);

    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    mysqli_stmt_close($stmt);



    if (!$row) {

        $_SESSION['user_contrato_id'] = null;

        return;

    }



    $_SESSION['user_contrato_id'] = $row['contrato_id'] ? (int) $row['contrato_id'] : null;

}



function usuario_pode_criar($modulo)

{

    return eh_admin_master();

}



function usuario_pode_ver_relatorios()

{

    return eh_admin_master() || eh_usuario_contrato();

}



function usuario_pode_exportar_relatorio()

{

    return usuario_pode_ver_relatorios();

}



function exigir_admin_master()

{

    if (!eh_admin_master()) {

        json_response('error', 'Acesso negado. Apenas Administrador Master.');

    }

}



function exigir_pode_criar($modulo)

{

    if (!usuario_pode_criar($modulo)) {

        json_response('error', 'Você não tem permissão para criar registros neste módulo.');

    }

}



function exigir_acesso_relatorio($conn, $evento_id)

{

    if (!usuario_tem_acesso_evento($conn, (int) $evento_id)) {

        json_response('error', 'Acesso negado a este relatório.');

    }

}



/**

 * SQL AND para filtrar listagens pelo contrato do usuário logado.

 */

function sql_filtro_contrato($coluna = 'contrato_id')

{

    if (eh_admin_master()) {

        return '';

    }

    $cid = obter_contrato_usuario();

    if (!$cid) {

        return ' AND 1=0';

    }

    return ' AND ' . $coluna . ' = ' . (int) $cid;

}



function usuario_tem_acesso_contrato($conn, $contrato_id)

{

    if (eh_admin_master()) {

        return true;

    }

    $cid = obter_contrato_usuario();

    return $cid && (int) $contrato_id === (int) $cid;

}



function usuario_tem_acesso_evento($conn, $evento_id)

{

    if (eh_admin_master()) {

        return true;

    }

    $evento_id = (int) $evento_id;

    if ($evento_id <= 0) {

        return false;

    }

    $stmt = mysqli_prepare($conn, 'SELECT contrato_id FROM eventos WHERE id = ? AND ativo = 1 LIMIT 1');

    mysqli_stmt_bind_param($stmt, 'i', $evento_id);

    mysqli_stmt_execute($stmt);

    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    mysqli_stmt_close($stmt);

    if (!$row) {

        return false;

    }

    return usuario_tem_acesso_contrato($conn, (int) $row['contrato_id']);

}



function exigir_acesso_evento($conn, $evento_id)

{

    exigir_acesso_relatorio($conn, $evento_id);

}



function exigir_acesso_contrato($conn, $contrato_id)

{

    if (!usuario_tem_acesso_contrato($conn, (int) $contrato_id)) {

        json_response('error', 'Acesso negado a este contrato.');

    }

}



/**

 * Política de senha: mín. 8 caracteres, maiúscula, minúscula, número e caractere especial.

 */

function validar_politica_senha($senha)

{

    $senha = (string) $senha;

    if (strlen($senha) < 8) {

        return 'A senha deve ter no mínimo 8 caracteres.';

    }

    if (!preg_match('/[A-Z]/', $senha)) {

        return 'A senha deve conter ao menos uma letra maiúscula.';

    }

    if (!preg_match('/[a-z]/', $senha)) {

        return 'A senha deve conter ao menos uma letra minúscula.';

    }

    if (!preg_match('/[0-9]/', $senha)) {

        return 'A senha deve conter ao menos um número.';

    }

    if (!preg_match('/[^A-Za-z0-9]/', $senha)) {

        return 'A senha deve conter ao menos um caractere especial.';

    }

    return null;

}



function criar_hash_senha($senha)

{

    return password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]);

}



/**

 * Páginas permitidas por perfil (roteamento no index.php).

 */

function paginas_permitidas_usuario()

{

    if (eh_admin_master()) {

        return null;

    }

    return [

        'dashboard',

        'dashboard_relatorios',

        'dashboard_relatorio_view',

    ];

}



function pagina_inicial_usuario()

{

    return 'dashboard';

}



function usuario_pode_acessar_pagina($pagina)

{

    if (eh_admin_master()) {

        return true;

    }

    $permitidas = paginas_permitidas_usuario();

    return in_array($pagina, $permitidas, true);

}



function auth_contexto_js()

{

    return [

        'perfil' => $_SESSION['user_perfil'] ?? '',

        'contrato_id' => $_SESSION['user_contrato_id'] ?? null,

        'eh_master' => eh_admin_master(),

        'pode_criar' => eh_admin_master(),

        'pode_relatorios' => usuario_pode_ver_relatorios(),

        'pode_exportar' => usuario_pode_exportar_relatorio(),

    ];

}



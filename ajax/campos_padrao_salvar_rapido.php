<?php

require_once __DIR__ . '/../bootstrap.php';

require_once __DIR__ . '/../modules/auth/middleware.php';

require_once __DIR__ . '/../modules/auth/permissoes.php';

require_once __DIR__ . '/../modules/campos_padrao/functions.php';

exigir_admin_master();

$resultado = campo_padrao_salvar($conn, $_POST);

json_response($resultado['status'], $resultado['message'], [

    'id' => $resultado['id'] ?? null,

    'slug' => $resultado['slug'] ?? null,

    'nome' => trim($_POST['nome'] ?? ''),

]);

